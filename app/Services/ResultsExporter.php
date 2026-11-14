<?php

namespace App\Services;

use App\Enums\AnalysisStatus;
use App\Models\Activity;

class ResultsExporter
{
    /** Format results.json sesuai soal. Aktivitas yang belum/gagal dianalisis tetap dicantumkan. */
    public function results(): array
    {
        return Activity::query()
            ->with(['analysis', 'group'])
            ->orderBy('activity_code')
            ->get()
            ->map(function (Activity $a) {
                $ok = $a->analysis?->status === AnalysisStatus::Success;

                return [
                    'activity_id' => $a->activity_code,
                    'automation_level' => $ok ? $a->analysis->automation_level->value : null,
                    'reason' => $ok ? $a->analysis->reason : null,
                    'recommendation' => $ok ? $a->analysis->recommendation : null,
                    'group_id' => $a->group?->code ?? $a->analysis?->group_code,
                    'fte' => round((float) $a->fte, 4),
                    'estimated_fte_saving' => $ok ? round((float) $a->estimated_fte_saving, 4) : null,
                    'status' => $ok ? 'success' : 'failed',
                ];
            })
            ->all();
    }

    public function toJson(): string
    {
        return json_encode(
            ['activities' => $this->results()],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    public function save(string $absolutePath): string
    {
        file_put_contents($absolutePath, $this->toJson());

        return $absolutePath;
    }
}