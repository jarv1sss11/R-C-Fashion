<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'vehicle_type',
        'number_plate',
        'available',
        'status',
        'notes',
    ];

    protected $casts = [
        'available' => 'boolean',
    ];

    public function deliveryAssignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class)->whereIn('status', ['assigned', 'picked_up']);
    }
}
