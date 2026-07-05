<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $type = $request->input('type', 'users');
        $from = $request->input('from');
        $to = $request->input('to');

        return view('admin.reports.index', [
            'types' => ReportService::TYPES,
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'rows' => $this->reports->generate($type, $from, $to),
        ]);
    }

    public function export(Request $request): Response
    {
        $type = $request->input('type', 'users');
        $rows = $this->reports->generate($type, $request->input('from'), $request->input('to'));

        $csv = fopen('php://temp', 'w+');

        if ($rows->isNotEmpty()) {
            fputcsv($csv, array_keys($rows->first()));

            foreach ($rows as $row) {
                fputcsv($csv, $row);
            }
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$type}-report.csv\"",
        ]);
    }
}
