<?php

namespace App\Console\Commands;

use App\Services\ResultsExporter;
use Illuminate\Console\Command;

class ExportResults extends Command
{
    protected $signature = 'workload:export-results {--path=results.json : Lokasi file, relatif dari root project}';

    protected $description = 'Tulis results.json dari hasil analisis yang tersimpan';

    public function handle(ResultsExporter $exporter): int
    {
        $results = $exporter->results();
        $path = $exporter->save(base_path($this->option('path')));
        $failed = collect($results)->where('status', 'failed')->count();

        $this->info(count($results)." aktivitas ditulis ke {$path}");

        if ($failed > 0) {
            $this->warn("{$failed} aktivitas berstatus failed.");
        }

        return self::SUCCESS;
    }
}