<?php

namespace App\Console\Commands;

use App\Services\WorkloadImportService;
use Illuminate\Console\Command;

class ImportWorkload extends Command
{
    protected $signature = 'workload:import {path : Path file Excel, relatif dari root project}';

    protected $description = 'Import dataset workload assessment dari file Excel';

    public function handle(WorkloadImportService $service): int
    {
        $path = realpath($this->argument('path')) ?: base_path($this->argument('path'));

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $batch = $service->import($path, basename($path));

        $this->table(
            ['Batch', 'Status', 'Total', 'Sukses', 'Gagal'],
            [[$batch->id, $batch->status->value, $batch->total_rows, $batch->success_rows, $batch->failed_rows]],
        );

        foreach ($batch->errors ?? [] as $error) {
            $this->warn(sprintf('Baris %s (%s): %s', $error['row'] ?? '-', $error['activity_id'] ?? '-', implode(' ', $error['messages'])));
        }

        return self::SUCCESS;
    }
}