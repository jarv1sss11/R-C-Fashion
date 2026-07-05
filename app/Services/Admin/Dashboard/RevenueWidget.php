<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Order;
use App\Services\Admin\Dashboard\Concerns\BuildsMonthlySeries;

class RevenueWidget
{
    use BuildsMonthlySeries;

    public function data(): array
    {
        return [
            'total' => (float) Order::sum('total'),
            'per_month' => $this->monthlySeries('orders', 'created_at', 'total'),
        ];
    }
}
