<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\RecommendationAnalyticsService;
use Illuminate\View\View;

class RecommendationAnalyticsController extends Controller
{
    public function __construct(private readonly RecommendationAnalyticsService $analytics)
    {
    }

    public function index(): View
    {
        $breakdown = $this->analytics->productBreakdown();

        return view('admin.recommendation-analytics', [
            'overview' => $this->analytics->overview(),
            'algorithmUsage' => $this->analytics->algorithmUsage(),
            'evaluation' => $this->analytics->evaluationMetrics(),
            'mostRecommended' => $breakdown->sortByDesc('shown')->take(10)->values(),
            'mostClicked' => $breakdown->sortByDesc('clicks')->take(10)->values(),
            'highestCtr' => $breakdown->filter(fn ($row) => $row['shown'] > 0)->sortByDesc('ctr')->take(10)->values(),
            'lowestCtr' => $breakdown->filter(fn ($row) => $row['shown'] > 0)->sortBy('ctr')->take(10)->values(),
        ]);
    }
}
