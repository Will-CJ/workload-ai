<?php

namespace App\Exports;

use App\Enums\AnalysisStatus;
use App\Models\Activity;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Export;

class WorkloadReportExport implements Export, WithMultipleSheets
{
    public function __construct(private array $report) {}

    public function sheets(): array
    {
        $r = $this->report;
        $g = $r['global'];
        $a = $r['assumptions'];
        $summaryText = fn ($s) => $s?->status === AnalysisStatus::Success ? $s->summary : '';

        return [
            new ArraySheet('Ringkasan', ['Metrik', 'Nilai'], [
                ['Dibuat', $r['generated_at']->format('Y-m-d H:i')],
                ['Total aktivitas', $g['activity_count']],
                ['Aktivitas dianalisis', $g['analyzed_count']],
                ['Total FTE', $g['total_fte']],
                ['Potensi saving FTE', $g['total_saving']],
                ['% saving', $g['saving_percentage']],
                ['Jumlah level High', $g['levels']['high']['count']],
                ['Jumlah level Medium', $g['levels']['medium']['count']],
                ['Jumlah level Low', $g['levels']['low']['count']],
                ['Kesimpulan AI (global)', $summaryText($r['global_summary'])],
                ['Asumsi: hari kerja/tahun', $a['work_days_per_year']],
                ['Asumsi: menit kerja/hari', $a['minutes_per_day']],
                ['Asumsi: saving rate high/medium/low', implode(' / ', $a['saving_rate'])],
                ['Model AI', $a['ai_model']],
                ['Versi prompt', $a['prompt_version']],
            ]),

            new ArraySheet('Aktivitas', [
                'activity_id', 'employee_id', 'employee_name', 'department', 'activity_name', 'frequency',
                'volume', 'duration_minutes', 'annual_minutes', 'fte', 'group_id', 'group_label',
                'automation_level', 'estimated_fte_saving', 'status', 'reason', 'recommendation',
            ], $r['activities']->map(fn (Activity $x) => [
                $x->activity_code, $x->employee->employee_code, $x->employee->name, $x->employee->department->name,
                $x->activity_name, $x->frequency->value, $x->volume, $x->duration_minutes, $x->annual_minutes,
                (float) $x->fte, $x->group?->code, $x->group?->label, $x->analysis?->automation_level?->value,
                $x->estimated_fte_saving === null ? null : (float) $x->estimated_fte_saving,
                $x->analysis?->status?->value ?? 'pending', $x->analysis?->reason, $x->analysis?->recommendation,
            ])->all()),

            new ArraySheet('Grup', ['group_id', 'label', 'aktivitas', 'karyawan', 'departemen', 'total_fte', 'saving_fte'],
                array_map(fn ($x) => [
                    $x['group_id'], $x['label'], $x['activity_count'], $x['employee_count'],
                    $x['department_count'], $x['fte'], $x['saving'],
                ], $r['groups'])),

            new ArraySheet('Departemen', ['departemen', 'aktivitas', 'total_fte', 'saving_fte', '%_saving', 'high', 'medium', 'low', 'kesimpulan_ai'],
                array_map(fn ($x) => [
                    $x['name'], $x['activity_count'], $x['total_fte'], $x['total_saving'], $x['saving_percentage'],
                    $x['levels']['high']['count'], $x['levels']['medium']['count'], $x['levels']['low']['count'],
                    $summaryText($x['summary']),
                ], $r['departments'])),

            new ArraySheet('Karyawan', ['employee_id', 'nama', 'departemen', 'aktivitas', 'total_fte', 'saving_fte', '%_saving', 'kesimpulan_ai'],
                array_map(fn ($x) => [
                    $x['code'], $x['name'], $x['department'], $x['activity_count'], $x['total_fte'],
                    $x['total_saving'], $x['saving_percentage'], $summaryText($x['summary']),
                ], $r['employees'])),
        ];
    }
}