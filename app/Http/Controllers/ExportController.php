<?php

namespace App\Http\Controllers;

use App\Exports\WorkloadReportExport;
use App\Services\ReportService;
use App\Services\ResultsExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function pdf(ReportService $reports)
    {
        return Pdf::loadView('reports.pdf', $reports->build())
            ->setPaper('a4')
            ->download('workload-report-'.now()->format('Ymd-His').'.pdf');
    }

    public function excel(ReportService $reports)
    {
        return Excel::download(
            new WorkloadReportExport($reports->build()),
            'workload-report-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function json(ResultsExporter $exporter)
    {
        return response($exporter->toJson(), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="results.json"',
        ]);
    }
}