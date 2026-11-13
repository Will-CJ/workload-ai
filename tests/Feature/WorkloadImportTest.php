<?php

use App\Enums\ImportStatus;
use App\Models\Activity;
use App\Models\Department;
use App\Models\Employee;
use App\Services\WorkloadImportService;

it('mengimport dataset valid', function () {
    $batch = app(WorkloadImportService::class)
        ->import(base_path('dataset/workload_assessment.xlsx'), 'workload_assessment.xlsx');

    expect($batch->status)->toBe(ImportStatus::Completed)
        ->and($batch->success_rows)->toBe(60)
        ->and(Activity::count())->toBe(60)
        ->and(Employee::count())->toBe(12)
        ->and(Department::count())->toBe(4)
        ->and((float) Activity::where('activity_code', 'ACT-0001')->value('fte'))->toBe(0.1458);
});

it('mencatat error per baris untuk data invalid', function () {
    $batch = app(WorkloadImportService::class)
        ->import(base_path('dataset/invalid_sample.xlsx'), 'invalid_sample.xlsx');

    expect($batch->status)->toBe(ImportStatus::CompletedWithErrors)
        ->and($batch->total_rows)->toBe(6)
        ->and($batch->success_rows)->toBe(1)
        ->and($batch->failed_rows)->toBe(5)
        ->and(collect($batch->errors)->pluck('activity_id')->all())
        ->toContain('ACT-0102', 'ACT-0103', 'ACT-0101', 'ACT-0104', 'ACT-0105');
});

it('import ulang tidak menduplikasi aktivitas', function () {
    $service = app(WorkloadImportService::class);
    $service->import(base_path('dataset/workload_assessment.xlsx'), 'a.xlsx');
    $service->import(base_path('dataset/workload_assessment.xlsx'), 'b.xlsx');

    expect(Activity::count())->toBe(60);
});