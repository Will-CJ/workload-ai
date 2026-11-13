<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Services\AnalysisPipeline;
use Illuminate\Console\Command;

class AnalyzeWorkload extends Command
{
    protected $signature = 'workload:analyze {--force : Abaikan cache dan analisis ulang semua}';

    protected $description = 'Jalankan analisis AI (yang sudah sukses diambil dari cache, yang gagal di-retry)';

    public function handle(AnalysisPipeline $pipeline): int
    {
        if (Activity::count() === 0) {
            $this->error('Belum ada data. Jalankan workload:import dulu.');

            return self::FAILURE;
        }

        $result = $pipeline->run((bool) $this->option('force'));

        $this->table(['Konten unik', 'Dari cache', 'Masuk antrian'], [[$result['total'], $result['cached'], $result['queued']]]);

        if ($result['queued'] > 0) {
            $this->info('Pastikan worker jalan: php artisan queue:work  —  cek progress: php artisan workload:status');
        }

        return self::SUCCESS;
    }
}