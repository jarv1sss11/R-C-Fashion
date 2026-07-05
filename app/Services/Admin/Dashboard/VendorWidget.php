<?php

namespace App\Services\Admin\Dashboard;

use App\Models\VendorProfile;
use App\Services\Admin\Dashboard\Concerns\BuildsMonthlySeries;

class VendorWidget
{
    use BuildsMonthlySeries;

    public function data(): array
    {
        return [
            'total' => VendorProfile::count(),
            'pending' => VendorProfile::where('approval_status', 'pending')->count(),
            'approved' => VendorProfile::where('approval_status', 'approved')->count(),
            'rejected' => VendorProfile::where('approval_status', 'rejected')->count(),
            'suspended' => VendorProfile::whereHas('user', fn ($q) => $q->where('status', 'suspended'))->count(),
            'registrations_per_month' => $this->monthlySeries('vendor_profiles', 'created_at'),
        ];
    }
}
