<?php

namespace App\Services\Admin\Dashboard;

use App\Models\RecommendationLog;
use App\Services\Admin\Dashboard\Concerns\BuildsMonthlySeries;
use Illuminate\Support\Facades\DB;

class RecommendationWidget
{
    use BuildsMonthlySeries;

    public function data(): array
    {
        $generated = RecommendationLog::count();
        $clicks = RecommendationLog::whereNotNull('clicked_at')->count();

        return [
            'generated' => $generated,
            'clicks' => $clicks,
            'ctr' => $generated > 0 ? round($clicks / $generated * 100, 2) : 0.0,
            'clicks_per_month' => $this->monthlySeries('recommendation_logs', 'clicked_at'),
            'failed_evaluations' => DB::table('failed_jobs')->where('payload', 'like', '%Recommendation%')->count(),
        ];
    }
}
