<?php

namespace App\Services;

use App\Enums\SummaryScope;
use App\Jobs\GenerateSummaryJob;
use App\Models\AiSummary;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Support\Facades\Bus;

class SummaryService
{
    public function __construct(private MetricsService $metrics) {}

    /** @return list<array{0: SummaryScope, 1: int}> */
    public function scopes(): array
    {
        return [
            [SummaryScope::Global, 0],
            ...Department::orderBy('name')->pluck('id')->map(fn ($id) => [SummaryScope::Department, (int) $id])->all(),
            ...Employee::orderBy('employee_code')->pluck('id')->map(fn ($id) => [SummaryScope::Employee, (int) $id])->all(),
        ];
    }

    /** [K] Semua angka untuk AI dihitung di sini. */
    public function input(SummaryScope $scope, int $scopeId = 0): array
    {
        $activities = $this->metrics->activities($scope, $scopeId);

        $name = match ($scope) {
            SummaryScope::Global => 'PT ABC (seluruh perusahaan)',
            SummaryScope::Department => Department::find($scopeId)?->name,
            SummaryScope::Employee => ($e = Employee::with('department')->find($scopeId))
                ? "{$e->name} ({$e->department->name})" : null,
        };

        $breakdown = match ($scope) {
            SummaryScope::Global => $this->metrics->breakdown($activities, 'department'),
            SummaryScope::Department => $this->metrics->breakdown($activities, 'employee'),
            SummaryScope::Employee => [],
        };

        return ['scope' => $scope->value, 'name' => $name]
            + $this->metrics->summarize($activities)
            + ['breakdown' => $breakdown];
    }

    public function inputHash(array $input): string
    {
        return hash('sha256', json_encode($input).'|'.config('workload.prompt_version').'|'.config('workload.ai_model'));
    }

    public function dispatchAll(bool $force = false): int
    {
        if ($force) {
            AiSummary::query()->update(['input_hash' => str_repeat('0', 64)]); // paksa generate ulang
        }

        $jobs = array_map(fn (array $s) => new GenerateSummaryJob($s[0], $s[1]), $this->scopes());

        Bus::batch($jobs)->name('workload-summaries')->allowFailures()->dispatch();

        return count($jobs);
    }
}