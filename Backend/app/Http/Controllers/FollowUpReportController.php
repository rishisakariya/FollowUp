<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Exports\FollowUpsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class FollowUpReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function saveFullFollowupsWithSummary()
    {
        $followups = $this->reportService->getAllFollowUpsWithStatus();
        $counts = $this->reportService->getFollowUpStatusCounts();

        $filePath = 'reports/followups_with_summary.xlsx';

        Excel::store(new FollowUpsExport($followups, $counts), $filePath);

        return response()->json([
            'message' => 'Excel file saved successfully.',
            'file_path' => Storage::url($filePath)
        ]);
    }
}
