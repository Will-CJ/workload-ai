<?php

namespace App\Enums;

enum AutomationLevel: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    /** Persentase FTE yang bisa dihemat (dari config/workload.php). */
    public function savingRate(): float
    {
        return (float) config("workload.saving_rate.{$this->value}");
    }

    /** Dipakai untuk tie-break saat mengurutkan prioritas (high paling atas). */
    public function weight(): int
    {
        return match ($this) {
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
