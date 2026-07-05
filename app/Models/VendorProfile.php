<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VendorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'store_name',
        'store_slug',
        'description',
        'logo_path',
        'banner_path',
        'approval_status',
        'phone',
        'email',
        'county',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null);
    }

    protected function bannerUrl(): Attribute
    {
        return Attribute::get(fn () => $this->banner_path ? Storage::disk('public')->url($this->banner_path) : null);
    }
}
