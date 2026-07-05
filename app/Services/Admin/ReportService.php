<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\RecommendationLog;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every report method returns a plain Collection of associative arrays —
 * the same rows both the HTML table and the CSV export render from, so the
 * two never drift out of sync.
 */
class ReportService
{
    public const TYPES = [
        'users' => 'Users',
        'vendors' => 'Vendors',
        'products' => 'Products',
        'orders' => 'Orders',
        'revenue' => 'Revenue',
        'best_selling_products' => 'Best Selling Products',
        'best_selling_categories' => 'Best Selling Categories',
        'recommendations' => 'Recommendation Statistics',
    ];

    public function generate(string $type, ?string $from, ?string $to): Collection
    {
        return match ($type) {
            'vendors' => $this->vendors($from, $to),
            'products' => $this->products($from, $to),
            'orders' => $this->orders($from, $to),
            'revenue' => $this->revenue($from, $to),
            'best_selling_products' => $this->bestSellingProducts($from, $to),
            'best_selling_categories' => $this->bestSellingCategories($from, $to),
            'recommendations' => $this->recommendations($from, $to),
            default => $this->users($from, $to),
        };
    }

    private function dateRange($query, ?string $from, ?string $to, string $column = 'created_at')
    {
        return $query
            ->when($from, fn ($q, $value) => $q->whereDate($column, '>=', $value))
            ->when($to, fn ($q, $value) => $q->whereDate($column, '<=', $value));
    }

    private function users(?string $from, ?string $to): Collection
    {
        return $this->dateRange(User::query(), $from, $to)
            ->orderByDesc('created_at')
            ->get(['name', 'email', 'role', 'status', 'created_at'])
            ->map(fn ($user) => [
                'Name' => $user->name,
                'Email' => $user->email,
                'Role' => $user->role,
                'Status' => $user->status,
                'Joined' => $user->created_at->format('Y-m-d'),
            ]);
    }

    private function vendors(?string $from, ?string $to): Collection
    {
        return $this->dateRange(VendorProfile::query()->with('user'), $from, $to)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($vendor) => [
                'Store' => $vendor->store_name,
                'Owner' => $vendor->user->name,
                'Email' => $vendor->user->email,
                'Approval Status' => $vendor->approval_status,
                'Account Status' => $vendor->user->status,
                'Registered' => $vendor->created_at->format('Y-m-d'),
            ]);
    }

    private function products(?string $from, ?string $to): Collection
    {
        return $this->dateRange(Product::query()->with(['vendor', 'category']), $from, $to)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($product) => [
                'Product' => $product->name,
                'Vendor' => $product->vendor->name,
                'Category' => $product->category->name,
                'Status' => $product->status,
                'Stock' => $product->stock_quantity,
                'Price (KES)' => $product->price,
                'Listed' => $product->created_at->format('Y-m-d'),
            ]);
    }

    private function orders(?string $from, ?string $to): Collection
    {
        return $this->dateRange(Order::query()->with('user'), $from, $to)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($order) => [
                'Order Number' => $order->order_number,
                'Buyer' => $order->user->name,
                'Total (KES)' => $order->total,
                'Order Status' => $order->order_status,
                'Payment Status' => $order->payment_status,
                'Delivery Status' => $order->delivery_status,
                'Placed' => $order->created_at->format('Y-m-d'),
            ]);
    }

    private function revenue(?string $from, ?string $to): Collection
    {
        $query = $this->dateRange(DB::table('orders'), $from, $to)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as order_count, SUM(total) as revenue")
            ->groupBy('month')
            ->orderBy('month');

        return $query->get()->map(fn ($row) => [
            'Month' => $row->month,
            'Orders' => $row->order_count,
            'Revenue (KES)' => number_format((float) $row->revenue, 2, '.', ''),
        ]);
    }

    private function bestSellingProducts(?string $from, ?string $to): Collection
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('users', 'users.id', '=', 'products.vendor_id')
            ->selectRaw('products.name as product, users.name as vendor, SUM(order_items.quantity) as units, SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->groupBy('products.name', 'users.name')
            ->orderByDesc('units');

        $this->dateRange($query, $from, $to, 'orders.created_at');

        return $query->limit(50)->get()->map(fn ($row) => [
            'Product' => $row->product,
            'Vendor' => $row->vendor,
            'Units Sold' => (int) $row->units,
            'Revenue (KES)' => number_format((float) $row->revenue, 2, '.', ''),
        ]);
    }

    private function bestSellingCategories(?string $from, ?string $to): Collection
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category, SUM(order_items.quantity) as units, SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->groupBy('categories.name')
            ->orderByDesc('units');

        $this->dateRange($query, $from, $to, 'orders.created_at');

        return $query->get()->map(fn ($row) => [
            'Category' => $row->category,
            'Units Sold' => (int) $row->units,
            'Revenue (KES)' => number_format((float) $row->revenue, 2, '.', ''),
        ]);
    }

    private function recommendations(?string $from, ?string $to): Collection
    {
        $query = $this->dateRange(RecommendationLog::query(), $from, $to, 'shown_at')
            ->selectRaw('algorithm_source, COUNT(*) as generated, SUM(clicked_at is not null) as clicks')
            ->groupBy('algorithm_source')
            ->orderByDesc('generated');

        return $query->get()->map(fn ($row) => [
            'Algorithm' => $row->algorithm_source,
            'Recommendations Generated' => (int) $row->generated,
            'Clicks' => (int) $row->clicks,
            'CTR (%)' => $row->generated > 0 ? round($row->clicks / $row->generated * 100, 2) : 0.0,
        ]);
    }
}
