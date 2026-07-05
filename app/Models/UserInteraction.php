<?php

namespace App\Models;

use App\Enums\InteractionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInteraction extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'product_id',
        'interaction_type',
        'weight',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'interaction_type' => InteractionType::class,
            'weight' => 'float',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
