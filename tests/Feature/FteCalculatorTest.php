<?php

use App\Enums\AutomationLevel;
use App\Enums\Frequency;
use App\Services\FteCalculator;

it('menghitung FTE sesuai contoh soal (daily 14 x 5 menit)', function () {
    $calc = app(FteCalculator::class);
    $annual = $calc->annualMinutes(Frequency::Daily, 14, 5);

    expect($annual)->toBe(16800)
        ->and($calc->fteFromAnnual($annual))->toBe(0.1458);
});

it('menghitung FTE weekly dan monthly', function () {
    $calc = app(FteCalculator::class);

    expect($calc->fte(Frequency::Weekly, 2, 90))->toBe(0.075)
        ->and($calc->fte(Frequency::Monthly, 1, 960))->toBe(0.1);
});

it('menghitung estimated FTE saving per level', function () {
    $calc = app(FteCalculator::class);

    expect($calc->saving(0.1458, AutomationLevel::High))->toBe(0.1021)
        ->and($calc->saving(0.1458, AutomationLevel::Medium))->toBe(0.0583)
        ->and($calc->saving(0.1458, AutomationLevel::Low))->toBe(0.0146);
});