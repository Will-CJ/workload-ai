<?php

namespace App\Enums;

enum Frequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    /** Berapa kali periode ini terjadi dalam setahun (dari config/workload.php). */
    public function periodsPerYear(): int
    {
        return (int) config("workload.periods_per_year.{$this->value}");
    }

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Harian',
            self::Weekly => 'Mingguan',
            self::Monthly => 'Bulanan',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
