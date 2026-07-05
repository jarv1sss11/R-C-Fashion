<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
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

        return view('vendor.dashboard', [
            'vendorProfile' => $vendor->vendorProfile,
            'totalProducts' => $vendor->products()->count(),
            'activeProducts' => $vendor->products()->where('status', 'published')->count(),
            'outOfStockProducts' => $vendor->products()->where('stock_quantity', 0)->count(),
            'pendingOrders' => $this->orders->pendingOrderItemCount($vendor),
        ]);
    }
}
