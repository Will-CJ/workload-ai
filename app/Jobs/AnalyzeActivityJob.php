<?php

namespace App\Jobs;

use App\Ai\Agents\ActivityAnalyzer;
use App\Enums\AnalysisStatus;
use App\Models\AiAnalysis;
use App\Services\AiClient;
use App\Services\AiOutputValidator;
use App\Services\AnalysisPipeline;
use App\Services\PromptRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Str;
use Throwable;

class AnalyzeActivityJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 20;
    public int $maxExceptions = 3;
    public array $backoff = [10, 30, 90];
    public int $timeout = 180;

    public function __construct(public int $analysisId) {}

    public function middleware(): array
    {
        return [new RateLimited('ai')];
    }

    public function handle(AiClient $ai, PromptRepository $prompts, AiOutputValidator $validator, AnalysisPipeline $pipeline): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $analysis = AiAnalysis::find($this->analysisId);
        if (! $analysis || $analysis->status === AnalysisStatus::Success) {
            return; // sudah ada di cache
        }

        $analysis->update([
            'status' => AnalysisStatus::Processing,
            'attempts' => $analysis->attempts + 1,
            'error' => null,
        ]);

        $message = json_encode([
            'activity' => $pipeline->representative($analysis),
            'group_code' => $analysis->group_code ?? 'other',
            'reference_examples' => $pipeline->anchors($analysis),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $agent = new ActivityAnalyzer($prompts->render('activity_analysis'));
        $keys = ['automation_level', 'reason', 'recommendation'];

        $output = $ai->prompt($agent, $message, $keys);
        $errors = $validator->analysis($output);

        if ($errors !== []) { // 1x kesempatan koreksi
            $output = $ai->prompt(
                $agent,
                $message."\n\nJawaban sebelumnya tidak valid: ".implode('; ', $errors).'. Perbaiki sesuai format.',
                $keys,
            );
            $errors = $validator->analysis($output);
        }

        if ($errors !== []) {
            $analysis->update([
                'status' => AnalysisStatus::Failed,
                'error' => 'Output AI tidak valid: '.implode('; ', $errors),
                'raw_response' => $output,
            ]);

            return;
        }

        $analysis->update([
            'automation_level' => $output['automation_level'],
            'reason' => trim($output['reason']),
            'recommendation' => trim($output['recommendation']),
            'status' => AnalysisStatus::Success,
            'error' => null,
            'model' => config('workload.ai_model'),
            'prompt_version' => $prompts->version(),
            'raw_response' => $output,
            'analyzed_at' => now(),
        ]);

        $pipeline->syncActivities($analysis);
    }

    /** Dipanggil setelah 3 error (timeout, rate limit, 5xx, simulasi). */
    public function failed(?Throwable $e): void
    {
        AiAnalysis::whereKey($this->analysisId)->update([
            'status' => AnalysisStatus::Failed->value,
            'error' => Str::limit($e?->getMessage() ?? 'Unknown error', 1000),
        ]);
    }
}