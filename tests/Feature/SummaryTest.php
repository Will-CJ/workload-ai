<?php

use App\Ai\Agents\ActivityAnalyzer;
use App\Ai\Agents\ActivityGrouper;
use App\Ai\Agents\SummaryWriter;
use App\Enums\AnalysisStatus;
use App\Enums\SummaryScope;
use App\Models\AiSummary;
use App\Services\AnalysisPipeline;
use App\Services\SummaryService;
use App\Services\WorkloadImportService;
use Database\Seeders\ActivityGroupSeeder;

beforeEach(function () {
    config(['workload.ai_rate_limit_per_minute' => 1000, 'workload.ai_simulate_failure_rate' => 0]);

    $this->seed(ActivityGroupSeeder::class);
    app(WorkloadImportService::class)->import(base_path('dataset/workload_assessment.xlsx'), 'w.xlsx');

    ActivityGrouper::fake(fn () => ['assignments' => []]);
    ActivityAnalyzer::fake(fn () => [
        'automation_level' => 'high',
        'reason' => 'Aktivitas berulang dan berbasis aturan yang jelas.',
        'recommendation' => 'Gunakan OCR dan integrasi langsung ke ERP.',
    ]);
    SummaryWriter::fake(fn () => [
        'summary' => 'Ringkasan uji yang cukup panjang untuk lolos validasi panjang minimum dari sistem.',
        'priority_rationales' => [['activity_id' => 'ACT-0051', 'rationale' => 'Saving terbesar.']],
    ]);

    app(AnalysisPipeline::class)->run();
});

it('membuat 17 ringkasan (1 global, 4 departemen, 12 karyawan)', function () {
    expect(AiSummary::where('status', AnalysisStatus::Success)->count())->toBe(17);
});

it('urutan prioritas ditentukan sistem, bukan AI', function () {
    $global = AiSummary::for(SummaryScope::Global);

    // semua level di-fake 'high', jadi prioritas teratas = FTE terbesar = ACT-0051 (0,2 FTE)
    expect($global->priorities[0]['activity_id'])->toBe('ACT-0051')
        ->and($global->priorities[0]['estimated_fte_saving'])->toBe(0.14)
        ->and($global->priorities[0]['rationale'])->toBe('Saving terbesar.')
        ->and($global->priorities[1]['rationale'])->toBeNull();
});

it('tidak memanggil AI lagi jika angka tidak berubah', function () {
    app(SummaryService::class)->dispatchAll();

    SummaryWriter::assertPromptedTimes(17);
});