<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Order;
use App\Services\Admin\Dashboard\Concerns\BuildsMonthlySeries;
use Illuminate\Support\Facades\DB;

class OrdersWidget
{
    use BuildsMonthlySeries;

    public function data(): array
    {
        return [
            'total' => Order::count(),
            'per_month' => $this->monthlySeries('orders', 'created_at'),
            'best_selling_categories' => $this->bestSellingCategories(),
        ];
    }

    private function bestSellingCategories(): array
    {
        return DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category, SUM(order_items.quantity) as units')
            ->groupBy('categories.name')
            ->orderByDesc('units')
            ->limit(6)
            ->pluck('units', 'category')
            ->map(fn ($units) => (float) $units)
            ->all();
    }
}
