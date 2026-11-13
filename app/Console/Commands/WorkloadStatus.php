<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\AiAnalysis;
use Illuminate\Console\Command;

class WorkloadStatus extends Command
{
    protected $signature = 'workload:status';

    protected $description = 'Tampilkan status analisis AI';

    public function handle(): int
    {
        $counts = AiAnalysis::query()
            ->whereIn('content_hash', Activity::query()->select('content_hash'))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $this->table(['Status', 'Jumlah'], $counts->map(fn ($total, $status) => [$status, $total])->values()->all());

        AiAnalysis::failed()->limit(10)->get()->each(
            fn (AiAnalysis $a) => $this->warn("#{$a->id}: {$a->error}")
        );

        return self::SUCCESS;
    }
}