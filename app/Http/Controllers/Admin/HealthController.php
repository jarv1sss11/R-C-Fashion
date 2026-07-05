<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\RecommendationLog;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\Admin\HealthCheckService;
use Illuminate\View\View;

class HealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $health)
    {
    }

    public function index(): View
    {
        return view('admin.health', [
            'checks' => $this->health->checks(),
            'counts' => [
                'users' => User::count(),
                'vendors' => VendorProfile::count(),
                'products' => Product::count(),
                'orders' => Order::count(),
                'recommendations' => RecommendationLog::count(),
            ],
        ]);
    }
}
