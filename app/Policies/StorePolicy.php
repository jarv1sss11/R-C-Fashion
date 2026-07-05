<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorProfile;

class StorePolicy
{
    public function update(User $user, VendorProfile $vendorProfile): bool
    {
        return $user->id === $vendorProfile->user_id;
    }
}
