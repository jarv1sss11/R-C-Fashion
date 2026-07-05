<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

/**
 * The only place `products.stock_quantity` is written to as a result of a
 * sale. Row-locks the product so two concurrent checkouts can't both
 * deduct from the same unit of stock (same pattern as CartService).
 */
class InventoryService
{
    /**
     * @throws ValidationException if the product no longer has enough stock
     */
    public function deduct(Product $product, int $quantity): void
    {
        $locked = Product::query()->lockForUpdate()->findOrFail($product->id);

        if ($quantity > $locked->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$locked->stock_quantity} \"{$locked->name}\" left in stock.",
            ]);
        }

        $locked->decrement('stock_quantity', $quantity);
    }
}
