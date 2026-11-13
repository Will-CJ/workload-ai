<?php

use App\Ai\Agents\ActivityAnalyzer;
use App\Ai\Agents\ActivityGrouper;
use App\Ai\Agents\SummaryWriter;
use App\Models\User;
use App\Services\AnalysisPipeline;
use App\Services\ResultsExporter;
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
    SummaryWriter::fake(fn () => ['summary' => str_repeat('Ringkasan uji. ', 5), 'priority_rationales' => []]);

    app(AnalysisPipeline::class)->run();
});

it('results.json berisi semua aktivitas dengan format yang benar', function () {
    $results = app(ResultsExporter::class)->results();
    $first = collect($results)->firstWhere('activity_id', 'ACT-0001');

    expect($results)->toHaveCount(60)
        ->and(collect($results)->pluck('activity_id')->unique())->toHaveCount(60)
        ->and(array_keys($first))->toBe([
            'activity_id', 'automation_level', 'reason', 'recommendation',
            'group_id', 'fte', 'estimated_fte_saving', 'status',
        ])
        ->and($first['fte'])->toBe(0.1458)
        ->and($first['estimated_fte_saving'])->toBe(0.1021)
        ->and($first['status'])->toBe('success');
});

it('file export bisa diunduh', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('exports.json'))->assertOk()->assertDownload('results.json');
    $this->get(route('exports.excel'))->assertOk()->assertDownload();
    $this->get(route('exports.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('reports'))->assertOk();
});

it('command menulis results.json', function () {
    $path = 'storage/app/test-results.json';

    $this->artisan('workload:export-results', ['--path' => $path])->assertSuccessful();

    expect(json_decode(file_get_contents(base_path($path)), true))->toHaveCount(60);
    unlink(base_path($path));
});