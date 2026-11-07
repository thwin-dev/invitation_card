<?php

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Services\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function enrollments(Request $request) { return $this->reports->enrollments($this->filters($request)); }
    public function revenue(Request $request) { return $this->reports->revenue($this->filters($request)); }
    public function completion(Request $request) { return $this->reports->completion($this->filters($request)); }
    public function studentProgress(Request $request) { return $this->reports->studentProgress($this->filters($request)); }
    public function certificates(Request $request) { return $this->reports->certificates($this->filters($request)); }
    public function trainers(Request $request) { return $this->reports->trainers($this->filters($request)); }

    public function export(Request $request, string $report): StreamedResponse
    {
        abort_unless($request->input('format', 'csv') === 'csv', 422, 'Only CSV export is currently supported.');
        $methods = [
            'enrollments' => 'enrollments',
            'revenue' => 'revenue',
            'completion' => 'completion',
            'student-progress' => 'studentProgress',
            'certificates' => 'certificates',
            'trainers' => 'trainers',
        ];
        abort_unless(isset($methods[$report]), 404);
        $rows = $this->reports->{$methods[$report]}($this->filters($request))->getCollection();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($rows->isNotEmpty()) {
                fputcsv($handle, array_keys((array) $rows->first()));
                foreach ($rows as $row) fputcsv($handle, (array) $row);
            }
            fclose($handle);
        }, $report.'-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function filters(Request $request): array
    {
        return $request->only(['from', 'to', 'status', 'course_id', 'student_id', 'student_status', 'trainer_id', 'per_page']);
    }
}
