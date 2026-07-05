<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(): View
    {
        $summary = $this->dashboard->summary();

        return view('admin.dashboard', [
            'summary' => $summary,
            'notifications' => $this->dashboard->notifications($summary),
        ]);
    }
}
