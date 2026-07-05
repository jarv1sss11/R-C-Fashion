<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;

/**
 * Sole data-access point for `carts`/`cart_items`. CartService owns all
 * stock-validation and total-calculation logic; this class only reads/writes.
 */
class CartRepository
{
    public function findOrCreateForUser(User $user): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $user->id]);
    }

    public function findItem(Cart $cart, int $productId): ?CartItem
    {
        return $cart->items()->where('product_id', $productId)->first();
    }

    public function createItem(Cart $cart, int $productId, int $quantity): CartItem
    {
        return $cart->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    public function updateItemQuantity(CartItem $item, int $quantity): void
    {
        $item->update(['quantity' => $quantity]);
    }

    public function deleteItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
