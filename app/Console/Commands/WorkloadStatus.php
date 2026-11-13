<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\AiAnalysis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        $this->newLine();
        $this->info('Ringkasan AI:');
        $this->table(['Status', 'Jumlah'], DB::table('ai_summaries')
        ->selectRaw('status, count(*) as total')->groupBy('status')->get()
        ->map(fn ($r) => [$r->status, $r->total])->all());

        return self::SUCCESS;
    }
}