<?php

namespace App\Services\Admin;

use App\Models\RecommendationLog;
use App\Models\User;
use App\Services\Recommendation\RecommendationEvaluator;
use Illuminate\Support\Facades\DB;

/**
 * Read-only reporting over the existing recommendation_logs table and the
 * unmodified RecommendationEvaluator — no algorithm code is touched here.
 */
class RecommendationAnalyticsService
{
    private const ALGORITHMS = ['content', 'collaborative', 'popularity', 'hybrid'];

    public function __construct(private readonly RecommendationEvaluator $evaluator)
    {
    }

    public function overview(): array
    {
        $generated = RecommendationLog::count();
        $clicks = RecommendationLog::whereNotNull('clicked_at')->count();

        return [
            'generated' => $generated,
            'clicks' => $clicks,
            'ctr' => $generated > 0 ? round($clicks / $generated * 100, 2) : 0.0,
            'generated_today' => RecommendationLog::whereDate('shown_at', today())->count(),
            'cold_start_users' => User::whereDoesntHave('interactions')->count(),
            'hybrid_usage' => RecommendationLog::where('algorithm_source', 'hybrid')->count(),
        ];
    }

    public function algorithmUsage(): array
    {
        return RecommendationLog::query()
            ->selectRaw('algorithm_source, COUNT(*) as total')
            ->groupBy('algorithm_source')
            ->orderByDesc('total')
            ->pluck('total', 'algorithm_source')
            ->all();
    }

    public function evaluationMetrics(): array
    {
        return collect(self::ALGORITHMS)
            ->mapWithKeys(fn ($algorithm) => [$algorithm => $this->evaluator->evaluate($algorithm)])
            ->all();
    }

    /**
     * Per-product shown/clicked/CTR counts — computed once and sliced by the
     * controller into "most recommended," "most clicked," "highest/lowest
     * CTR" so the underlying query only runs a single time per request.
     */
    public function productBreakdown()
    {
        return DB::table('recommendation_logs')
            ->join('products', 'products.id', '=', 'recommendation_logs.product_id')
            ->selectRaw('products.name as product, COUNT(*) as shown, SUM(recommendation_logs.clicked_at is not null) as clicks')
            ->groupBy('products.name')
            ->get()
            ->map(fn ($row) => [
                'product' => $row->product,
                'shown' => (int) $row->shown,
                'clicks' => (int) $row->clicks,
                'ctr' => $row->shown > 0 ? round($row->clicks / $row->shown * 100, 2) : 0.0,
            ]);
    }
}
