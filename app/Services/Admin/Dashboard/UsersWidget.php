<?php

namespace App\Services\Admin\Dashboard;

use App\Models\User;
use App\Services\Admin\Dashboard\Concerns\BuildsMonthlySeries;

class UsersWidget
{
    use BuildsMonthlySeries;

    public function data(): array
    {
        return [
            'total' => User::count(),
            'buyers' => User::where('role', 'buyer')->count(),
            'vendors' => User::where('role', 'vendor')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'new_per_month' => $this->monthlySeries('users', 'created_at'),
        ];
    }
}
