<?php

namespace App\Jobs;

use App\Ai\Agents\ActivityGrouper;
use App\Models\ActivityGroup;
use App\Models\AiAnalysis;
use App\Services\AiClient;
use App\Services\AiOutputValidator;
use App\Services\AnalysisPipeline;
use App\Services\PromptRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Throwable;

class GroupActivitiesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 20;           // release karena rate limit tidak dihitung sebagai error
    public int $maxExceptions = 3;    // maksimal 3 error sungguhan
    public array $backoff = [10, 30, 90];
    public int $timeout = 180;

    /** @param list<int> $analysisIds */
    public function __construct(public array $analysisIds) {}

    public function middleware(): array
    {
        return [new RateLimited('ai')];
    }

    public function handle(AiClient $ai, PromptRepository $prompts, AiOutputValidator $validator, AnalysisPipeline $pipeline): void
    {
        $codes = ActivityGroup::codes();
        $groups = ActivityGroup::orderBy('id')->get(['code', 'label', 'description'])->toArray();
        $agent = new ActivityGrouper($prompts->render('grouping', ['groups' => $groups]), $codes);

        // hanya yang belum punya grup -> aman diulang
        $analyses = AiAnalysis::whereIn('id', $this->analysisIds)->whereNull('group_code')->get();

        foreach ($analyses->chunk((int) config('workload.grouping_chunk_size')) as $chunk) {
            $items = $chunk->map(fn (AiAnalysis $a) => ['key' => (string) $a->id] + $pipeline->representative($a))
                ->values()->all();

            $output = $ai->prompt($agent, json_encode(['activities' => $items], JSON_UNESCAPED_UNICODE), ['assignments']);
            $map = $validator->grouping($output['assignments'] ?? [], array_column($items, 'key'), $codes);

            foreach ($chunk as $analysis) {
                $analysis->update(['group_code' => $map[(string) $analysis->id] ?? 'other']);
            }
        }

        $pipeline->dispatchAnalysis($this->analysisIds);
    }

    /** Grouping gagal total: tetap lanjut analisis (grup jadi 'other'), tidak menghentikan pipeline. */
    public function failed(?Throwable $e): void
    {
        Log::warning('Grouping AI gagal, lanjut analisis tanpa grup', ['error' => $e?->getMessage()]);
        app(AnalysisPipeline::class)->dispatchAnalysis($this->analysisIds);
    }
}