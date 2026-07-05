<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Product;
use App\Models\VendorProfile;

class DashboardService
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function __construct(
        private readonly UsersWidget $users,
        private readonly VendorWidget $vendors,
        private readonly OrdersWidget $orders,
        private readonly RevenueWidget $revenue,
        private readonly RecommendationWidget $recommendations,
        private readonly SystemHealthWidget $health,
    ) {
    }

    public function summary(): array
    {
        return [
            'users' => $this->users->data(),
            'vendors' => $this->vendors->data(),
            'orders' => $this->orders->data(),
            'revenue' => $this->revenue->data(),
            'recommendations' => $this->recommendations->data(),
            'health' => $this->health->data(),
            'product_count' => Product::count(),
        ];
    }

    public function notifications(array $summary): array
    {
        $lowStock = Product::where('status', 'published')
            ->whereBetween('stock_quantity', [1, self::LOW_STOCK_THRESHOLD])
            ->count();

        $outOfStock = Product::where('status', 'published')->where('stock_quantity', 0)->count();

        return array_filter([
            'pending_vendor_approvals' => VendorProfile::where('approval_status', 'pending')->count(),
            'pending_product_moderation' => Product::where('status', 'draft')->count(),
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'suspended_vendors' => VendorProfile::whereHas('user', fn ($q) => $q->where('status', 'suspended'))->count(),
            'failed_recommendation_evaluations' => $summary['recommendations']['failed_evaluations'],
        ], fn ($count) => $count > 0);
    }
}
