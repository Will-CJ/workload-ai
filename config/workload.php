<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Asumsi waktu kerja (dipakai untuk menghitung FTE)
    |--------------------------------------------------------------------------
    | 1 FTE = work_days_per_year x minutes_per_day = 240 x 480 = 115.200 menit/tahun
    */
    'work_days_per_year' => 240,
    'minutes_per_day' => 480,

    /*
    | Berapa kali satu periode terjadi dalam setahun.
    | weekly = 240 hari / 5 hari kerja = 48 minggu efektif.
    */
    'periods_per_year' => [
        'daily' => 240,
        'weekly' => 48,
        'monthly' => 12,
    ],

    /*
    | Persentase FTE yang diasumsikan bisa dihemat per level otomasi.
    | estimated_fte_saving = fte x saving_rate[level]
    */
    'saving_rate' => [
        'high' => 0.70,
        'medium' => 0.40,
        'low' => 0.10,
    ],

    'prompt_version' => env('AI_PROMPT_VERSION', 'v1'),
    'ai_provider' => env('AI_PROVIDER', 'gemini'),
    'ai_model' => env('AI_MODEL', 'gemini-3.1-flash-lite'),
    'ai_timeout' => (int) env('AI_TIMEOUT', 60),
    'ai_rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 10),
    'ai_simulate_failure_rate' => (float) env('AI_SIMULATE_FAILURE_RATE', 0),
    'grouping_chunk_size' => 60,
    'anchor_examples' => 3,
];
