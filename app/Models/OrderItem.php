<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'vendor_id',
        'product_name',
        'unit_price',
        'quantity',
        'fulfillment_status',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Nullable — the product may have been deleted since this order was
     * placed. `product_name`/`unit_price` on this row are the source of
     * truth for display, not this relation.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    protected function lineTotal(): Attribute
    {
        return Attribute::get(fn () => $this->quantity * (float) $this->unit_price);
    }
}
