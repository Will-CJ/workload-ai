<?php

namespace App\Jobs;

use App\Ai\Agents\SummaryWriter;
use App\Enums\AnalysisStatus;
use App\Enums\SummaryScope;
use App\Models\AiSummary;
use App\Services\AiClient;
use App\Services\AiOutputValidator;
use App\Services\PromptRepository;
use App\Services\SummaryService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Str;
use Throwable;

class GenerateSummaryJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 20;
    public int $maxExceptions = 3;
    public array $backoff = [10, 30, 90];
    public int $timeout = 180;

    public function __construct(public SummaryScope $scope, public int $scopeId = 0) {}

    public function middleware(): array
    {
        return [new RateLimited('ai')];
    }

    public function handle(AiClient $ai, PromptRepository $prompts, AiOutputValidator $validator, SummaryService $summaries): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $input = $summaries->input($this->scope, $this->scopeId);
        $hash = $summaries->inputHash($input);

        $summary = AiSummary::firstOrNew(['scope' => $this->scope, 'scope_id' => $this->scopeId]);

        // Cache: angka tidak berubah dan sudah sukses -> tidak perlu memanggil AI
        if ($summary->exists && $summary->input_hash === $hash && $summary->status === AnalysisStatus::Success) {
            return;
        }

        $summary->fill([
            'input_hash' => $hash,
            'status' => AnalysisStatus::Processing,
            'attempts' => ($summary->attempts ?? 0) + 1,
            'error' => null,
        ])->save();

        $agent = new SummaryWriter($prompts->render('summary'));
        $message = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $keys = ['summary', 'priority_rationales'];

        $output = $ai->prompt($agent, $message, $keys);
        $errors = $validator->summary($output);

        if ($errors !== []) {
            $output = $ai->prompt($agent, $message."\n\nJawaban sebelumnya tidak valid: ".implode('; ', $errors).'. Perbaiki sesuai format.', $keys);
            $errors = $validator->summary($output);
        }

        if ($errors !== []) {
            $summary->update(['status' => AnalysisStatus::Failed, 'error' => 'Output AI tidak valid: '.implode('; ', $errors)]);

            return;
        }

        // Urutan & angka dari sistem, AI hanya menambahkan alasan
        $rationales = $validator->rationales($output['priority_rationales'] ?? [], array_column($input['priorities'], 'activity_id'));
        $priorities = array_map(
            fn (array $p) => $p + ['rationale' => $rationales[$p['activity_id']] ?? null],
            $input['priorities'],
        );

        $summary->update([
            'summary' => trim($output['summary']),
            'priorities' => $priorities,
            'status' => AnalysisStatus::Success,
            'error' => null,
            'model' => config('workload.ai_model'),
            'prompt_version' => $prompts->version(),
            'generated_at' => now(),
        ]);
    }

    public function failed(?Throwable $e): void
    {
        AiSummary::where('scope', $this->scope->value)->where('scope_id', $this->scopeId)->update([
            'status' => AnalysisStatus::Failed->value,
            'error' => Str::limit($e?->getMessage() ?? 'Unknown error', 1000),
        ]);
    }
}