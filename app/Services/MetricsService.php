<?php

namespace App\Services;

use App\Enums\AnalysisStatus;
use App\Enums\AutomationLevel;
use App\Enums\SummaryScope;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MetricsService
{
    /** Aktivitas untuk sebuah scope, lengkap dengan relasinya. */
    public function activities(SummaryScope $scope = SummaryScope::Global, int $scopeId = 0): Collection
    {
        return Activity::query()
            ->with(['analysis', 'group', 'employee.department'])
            ->when($scope === SummaryScope::Department, fn (Builder $q) => $q->forDepartment($scopeId))
            ->when($scope === SummaryScope::Employee, fn (Builder $q) => $q->where('employee_id', $scopeId))
            ->orderBy('activity_code')
            ->get();
    }

    public function summarize(Collection $activities, int $priorityLimit = 5): array
    {
        $analyzed = $activities->filter(fn (Activity $a) => $a->analysis?->status === AnalysisStatus::Success);
        $totalFte = $this->sum($activities, 'fte');
        $totalSaving = $this->sum($analyzed, 'estimated_fte_saving');

        $levels = [];
        foreach (AutomationLevel::cases() as $level) {
            $items = $analyzed->filter(fn (Activity $a) => $a->analysis->automation_level === $level);
            $levels[$level->value] = [
                'count' => $items->count(),
                'fte' => $this->sum($items, 'fte'),
                'saving' => $this->sum($items, 'estimated_fte_saving'),
            ];
        }

        return [
            'activity_count' => $activities->count(),
            'analyzed_count' => $analyzed->count(),
            'unanalyzed_count' => $activities->count() - $analyzed->count(),
            'total_fte' => $totalFte,
            'total_saving' => $totalSaving,
            'saving_percentage' => $totalFte > 0 ? round($totalSaving / $totalFte * 100, 1) : 0.0,
            'levels' => $levels,
            'groups' => $this->groups($analyzed),
            'priorities' => $this->priorities($analyzed, $priorityLimit),
        ];
    }

    /** Urutan prioritas: saving desc, level desc, fte desc, activity_id asc. */
    public function priorities(Collection $analyzed, int $limit = 5): array
    {
        return $analyzed
            ->sort(fn (Activity $a, Activity $b) => [
                (float) $b->estimated_fte_saving, $b->analysis->automation_level->weight(), (float) $b->fte, $a->activity_code,
            ] <=> [
                (float) $a->estimated_fte_saving, $a->analysis->automation_level->weight(), (float) $a->fte, $b->activity_code,
            ])
            ->take($limit)
            ->map(fn (Activity $a) => [
                'activity_id' => $a->activity_code,
                'activity_name' => $a->activity_name,
                'employee' => $a->employee->name,
                'department' => $a->employee->department->name,
                'group_id' => $a->group?->code ?? 'other',
                'automation_level' => $a->analysis->automation_level->value,
                'fte' => round((float) $a->fte, 4),
                'estimated_fte_saving' => round((float) $a->estimated_fte_saving, 4),
                'recommendation' => $a->analysis->recommendation,
            ])
            ->values()
            ->all();
    }

    public function groups(Collection $analyzed): array
    {
        return $analyzed
            ->groupBy(fn (Activity $a) => $a->group?->code ?? 'other')
            ->map(fn (Collection $items, string $code) => [
                'group_id' => $code,
                'label' => $items->first()->group?->label ?? 'Lainnya',
                'activity_count' => $items->count(),
                'employee_count' => $items->pluck('employee_id')->unique()->count(),
                'department_count' => $items->pluck('employee.department_id')->unique()->count(),
                'fte' => $this->sum($items, 'fte'),
                'saving' => $this->sum($items, 'estimated_fte_saving'),
            ])
            ->sortByDesc('saving')
            ->values()
            ->all();
    }

    /** Rincian per departemen atau per karyawan. */
    public function breakdown(Collection $activities, string $by): array
    {
        return $activities
            ->groupBy(fn (Activity $a) => $by === 'department' ? $a->employee->department->name : $a->employee->name)
            ->map(function (Collection $items, string $name) {
                $fte = $this->sum($items, 'fte');
                $saving = $this->sum($items, 'estimated_fte_saving');

                return [
                    'name' => $name,
                    'activity_count' => $items->count(),
                    'fte' => $fte,
                    'saving' => $saving,
                    'saving_percentage' => $fte > 0 ? round($saving / $fte * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('saving')
            ->values()
            ->all();
    }

    private function sum(Collection $items, string $column): float
    {
        return round($items->sum(fn (Activity $a) => (float) $a->{$column}), 4);
    }
}