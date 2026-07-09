<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAssignment;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): View
    {
        $vendor = $request->user();

        // Delivery assignments for orders that contain this vendor's products
        $vendorOrderIds = $vendor->products()
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->distinct()
            ->pluck('order_items.order_id');

        $activeDeliveries = DeliveryAssignment::whereIn('order_id', $vendorOrderIds)
            ->whereIn('status', ['assigned', 'picked_up'])
            ->count();

        return view('vendor.dashboard', [
            'vendorProfile' => $vendor->vendorProfile,
            'totalProducts' => $vendor->products()->count(),
            'activeProducts' => $vendor->products()->where('status', 'published')->count(),
            'outOfStockProducts' => $vendor->products()->where('stock_quantity', 0)->count(),
            'pendingOrders' => $this->orders->pendingOrderItemCount($vendor),
            'activeDeliveries' => $activeDeliveries,
        ]);
    }
}
