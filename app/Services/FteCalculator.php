<?php

namespace App\Services;

use App\Enums\AutomationLevel;
use App\Enums\Frequency;

class FteCalculator
{
    /** 1 FTE dalam menit per tahun (240 x 480 = 115.200). */
    public function minutesPerFte(): int
    {
        return (int) config('workload.work_days_per_year') * (int) config('workload.minutes_per_day');
    }

    public function annualMinutes(Frequency $frequency, int $volume, int $durationMinutes): int
    {
        return $durationMinutes * $volume * $frequency->periodsPerYear();
    }

    public function fteFromAnnual(int $annualMinutes): float
    {
        return round($annualMinutes / $this->minutesPerFte(), 4);
    }

    public function fte(Frequency $frequency, int $volume, int $durationMinutes): float
    {
        return $this->fteFromAnnual($this->annualMinutes($frequency, $volume, $durationMinutes));
    }

    public function saving(float $fte, AutomationLevel $level): float
    {
        return round($fte * $level->savingRate(), 4);
    }
}