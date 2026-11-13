<?php

namespace App\Console\Commands;

use App\Services\SummaryService;
use Illuminate\Console\Command;

class SummarizeWorkload extends Command
{
    protected $signature = 'workload:summarize {--force : Generate ulang walaupun angka tidak berubah}';

    protected $description = 'Buat kesimpulan AI per karyawan, departemen, dan global';

    public function handle(SummaryService $summaries): int
    {
        $count = $summaries->dispatchAll((bool) $this->option('force'));

        $this->info("{$count} job ringkasan masuk antrian. Pastikan queue:work berjalan.");

        return self::SUCCESS;
    }
}