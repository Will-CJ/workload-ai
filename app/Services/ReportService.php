<?php

namespace App\Services;

use App\Enums\AnalysisStatus;
use App\Enums\SummaryScope;
use App\Models\Activity;
use App\Models\AiSummary;
use App\Models\Department;
use App\Models\Employee;

class ReportService
{
    public function __construct(private MetricsService $metrics) {}

    /** Semua data untuk PDF & Excel. Murni membaca DB, tidak memanggil AI. */
    public function build(): array
    {
        $activities = $this->metrics->activities();
        $analyzed = $activities->filter(fn (Activity $a) => $a->analysis?->status === AnalysisStatus::Success);

        $departments = Department::orderBy('name')->get()->map(fn (Department $d) => ['name' => $d->name]
            + $this->metrics->summarize($this->metrics->activities(SummaryScope::Department, $d->id), 3)
            + ['summary' => AiSummary::for(SummaryScope::Department, $d->id)]
        )->all();

        $employees = Employee::with('department')->orderBy('employee_code')->get()->map(fn (Employee $e) => [
            'code' => $e->employee_code,
            'name' => $e->name,
            'department' => $e->department->name,
        ] + $this->metrics->summarize($this->metrics->activities(SummaryScope::Employee, $e->id), 3)
          + ['summary' => AiSummary::for(SummaryScope::Employee, $e->id)]
        )->all();

        return [
            'generated_at' => now(),
            'global' => $this->metrics->summarize($activities),
            'global_summary' => AiSummary::for(SummaryScope::Global),
            'departments' => $departments,
            'employees' => $employees,
            'groups' => $this->metrics->groups($analyzed),
            'activities' => $activities,
            'assumptions' => [
                'work_days_per_year' => config('workload.work_days_per_year'),
                'minutes_per_day' => config('workload.minutes_per_day'),
                'saving_rate' => config('workload.saving_rate'),
                'ai_model' => config('workload.ai_model'),
                'prompt_version' => config('workload.prompt_version'),
            ],
        ];
    }
}