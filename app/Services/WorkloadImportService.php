<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Imports\WorkloadImport;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class WorkloadImportService
{
    public function __construct(
        private FteCalculator $calculator,
        private TextNormalizer $normalizer,
    ) {}

    public function import(string $absolutePath, string $originalName, ?int $userId = null, ?string $storedPath = null): ImportBatch
    {
        $batch = ImportBatch::create([
            'user_id' => $userId,
            'original_name' => $originalName,
            'stored_path' => $storedPath,
            'status' => ImportStatus::Processing,
        ]);

        $import = new WorkloadImport($batch, $this->calculator, $this->normalizer);

        try {
            DB::transaction(fn () => Excel::import($import, $absolutePath));
        } catch (Throwable $e) {
            report($e);
            $batch->update([
                'status' => ImportStatus::Failed,
                'errors' => [['row' => null, 'activity_id' => null, 'messages' => ['File tidak dapat dibaca: '.$e->getMessage()]]],
            ]);

            return $batch->fresh();
        }

        $status = match (true) {
            $import->headerInvalid => ImportStatus::Failed,
            $import->errors !== [] => ImportStatus::CompletedWithErrors,
            default => ImportStatus::Completed,
        };

        $batch->update([
            'total_rows' => $import->totalRows,
            'success_rows' => $import->successRows,
            'failed_rows' => $import->totalRows - $import->successRows,
            'errors' => $import->errors ?: null,
            'status' => $status,
        ]);

        return $batch->fresh();
    }
}