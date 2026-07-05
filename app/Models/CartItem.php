<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * quantity x current product price. Deliberately not stored — an active
     * cart always reflects the product's live price, never a stale snapshot.
     */
    protected function lineTotal(): Attribute
    {
        return Attribute::get(fn () => $this->quantity * (float) $this->product->price);
    }
}
