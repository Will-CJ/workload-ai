<?php

namespace App\Services;

use App\Enums\AnalysisStatus;
use App\Jobs\AnalyzeActivityJob;
use App\Jobs\GroupActivitiesJob;
use App\Models\Activity;
use App\Models\ActivityGroup;
use App\Models\AiAnalysis;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalysisPipeline
{
    public function __construct(
        private TextNormalizer $normalizer,
        private FteCalculator $calculator,
        private SummaryService $summaries,
    ) {}

    /**
     * Menjalankan analisis. Konten yang sudah sukses diambil dari cache (tidak memanggil AI).
     * Dipanggil ulang = otomatis me-retry yang pending/failed.
     *
     * @return array{total: int, cached: int, queued: int}
     */
    public function run(bool $force = false): array
    {
        $this->refreshHashes();

        $hashes = Activity::query()->distinct()->pluck('content_hash');

        foreach ($hashes as $hash) {
            AiAnalysis::firstOrCreate(['content_hash' => $hash], ['status' => AnalysisStatus::Pending]);
        }

        if ($force) {
            AiAnalysis::whereIn('content_hash', $hashes)->update([
                'group_code' => null, 'automation_level' => null, 'reason' => null,
                'recommendation' => null, 'status' => AnalysisStatus::Pending->value,
                'attempts' => 0, 'error' => null,
            ]);
        }

        // Cache hit: hasil lama langsung disinkronkan ke activities tanpa memanggil AI
        AiAnalysis::whereIn('content_hash', $hashes)->successful()->get()
            ->each(fn (AiAnalysis $analysis) => $this->syncActivities($analysis));

        $pendingIds = AiAnalysis::whereIn('content_hash', $hashes)
            ->where('status', '!=', AnalysisStatus::Success->value)
            ->pluck('id')->all();

        if ($pendingIds !== []) {
            AiAnalysis::whereIn('id', $pendingIds)->update(['status' => AnalysisStatus::Pending->value, 'error' => null]);
            GroupActivitiesJob::dispatch($pendingIds);
        } else {
            $this->summaries->dispatchAll(); // semua dari cache -> langsung buat ringkasan
        }

        return [
            'total' => $hashes->count(),
            'cached' => $hashes->count() - count($pendingIds),
            'queued' => count($pendingIds),
        ];
    }

    /** Dipanggil setelah grouping selesai (atau gagal). */
    public function dispatchAnalysis(array $analysisIds): void
    {
        if ($analysisIds === []) {
            return;
        }

        $jobs = array_map(fn (int $id) => new AnalyzeActivityJob($id), $analysisIds);

        $batch = Bus::batch($jobs)
        ->name('workload-analysis')
        ->allowFailures()
        ->finally(static function () {
            app(SummaryService::class)->dispatchAll(); 
        })
        ->dispatch();

        Cache::put('workload:analysis_batch_id', $batch->id, now()->addDay());
    }

    /** [K] Menyalin hasil AI ke semua aktivitas dengan konten sama + menghitung saving. */
    public function syncActivities(AiAnalysis $analysis): void
    {
        $groupId = ActivityGroup::where('code', $analysis->group_code ?? 'other')->value('id');
        $level = $analysis->automation_level;

        Activity::where('content_hash', $analysis->content_hash)->get()
            ->each(fn (Activity $activity) => $activity->update([
                'activity_group_id' => $groupId,
                'estimated_fte_saving' => $level ? $this->calculator->saving((float) $activity->fte, $level) : null,
            ]));
    }

    /** Contoh aktivitas untuk dikirim ke AI (aktivitas pertama dengan hash ini). */
    public function representative(AiAnalysis $analysis): array
    {
        $activity = Activity::where('content_hash', $analysis->content_hash)->orderBy('id')->first();

        return [
            'activity_name' => $activity?->activity_name,
            'description' => $activity?->description,
        ];
    }

    /** Jangkar konsistensi: aktivitas di grup sama yang sudah dinilai. */
    public function anchors(AiAnalysis $analysis): array
    {
        if (! $analysis->group_code) {
            return [];
        }

        return AiAnalysis::successful()
            ->where('group_code', $analysis->group_code)
            ->whereKeyNot($analysis->id)
            ->latest('analyzed_at')
            ->limit((int) config('workload.anchor_examples'))
            ->get()
            ->map(fn (AiAnalysis $item) => $this->representative($item) + [
                'automation_level' => $item->automation_level->value,
                'reason' => $item->reason,
            ])
            ->all();
    }

    /** Hash ikut berubah kalau prompt_version atau model diganti -> otomatis analisis ulang. */
    private function refreshHashes(): void
    {
        Activity::query()->each(function (Activity $activity) {
            $hash = $this->normalizer->contentHash($activity->activity_name, $activity->description);

            if ($hash !== $activity->content_hash) {
                $activity->update([
                    'content_hash' => $hash,
                    'activity_group_id' => null,
                    'estimated_fte_saving' => null,
                ]);
            }
        });
    }

    public function isRunning(): bool
    {
        return AiAnalysis::whereIn('status', [AnalysisStatus::Pending->value, AnalysisStatus::Processing->value])->exists()
            && DB::table('jobs')->exists();
    }

    public function statusCounts(): array
    {
        $counts = DB::table('ai_analyses')
            ->whereIn('content_hash', Activity::query()->select('content_hash'))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return array_map('intval', $counts + ['pending' => 0, 'processing' => 0, 'success' => 0, 'failed' => 0]);
    }
}