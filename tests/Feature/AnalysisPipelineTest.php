<?php

use App\Ai\Agents\ActivityAnalyzer;
use App\Ai\Agents\ActivityGrouper;
use App\Enums\AnalysisStatus;
use App\Models\Activity;
use App\Models\AiAnalysis;
use App\Services\AnalysisPipeline;
use App\Services\WorkloadImportService;
use Database\Seeders\ActivityGroupSeeder;
use App\Ai\Agents\SummaryWriter;

beforeEach(function () {
    config([
        'workload.ai_rate_limit_per_minute' => 1000,
        'workload.ai_simulate_failure_rate' => 0,
    ]);

    $this->seed(ActivityGroupSeeder::class);
    app(WorkloadImportService::class)->import(base_path('dataset/workload_assessment.xlsx'), 'w.xlsx');

    ActivityGrouper::fake(fn () => ['assignments' => []]); // semua fallback ke 'other'
    ActivityAnalyzer::fake(fn () => [
        'automation_level' => 'high',
        'reason' => 'Aktivitas berulang dan berbasis aturan yang jelas.',
        'recommendation' => 'Gunakan OCR dan integrasi langsung ke ERP.',
    ]);
    SummaryWriter::fake(fn () => [
        'summary' => str_repeat('Ringkasan uji. ', 5), 
        'priority_rationales' => []
    ]);
});

it('menganalisis semua aktivitas dan menghitung saving secara deterministik', function () {
    $result = app(AnalysisPipeline::class)->run();

    expect($result['queued'])->toBe(60)
        ->and(AiAnalysis::where('status', AnalysisStatus::Success)->count())->toBe(60)
        ->and(Activity::whereNull('estimated_fte_saving')->count())->toBe(0)
        ->and((float) Activity::where('activity_code', 'ACT-0001')->value('estimated_fte_saving'))->toBe(0.1021);
});

it('tidak memanggil AI lagi untuk konten yang sudah di-cache', function () {
    $pipeline = app(AnalysisPipeline::class);
    $pipeline->run();
    $second = $pipeline->run();

    expect($second['queued'])->toBe(0)->and($second['cached'])->toBe(60);
    ActivityAnalyzer::assertPromptedTimes(60);
});