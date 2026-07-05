<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The single place cart totals and stock-aware quantity changes are
 * computed — controllers never touch CartItem/Cart directly. Every write
 * that depends on current stock (add, increase, update) row-locks the
 * product inside a transaction so two concurrent requests for the last unit
 * of a product can't both succeed (Phase D: race-condition prevention).
 */
class CartService
{
    public function __construct(private readonly CartRepository $repository)
    {
    }

    public function getCart(User $user): Cart
    {
        $cart = $this->repository->findOrCreateForUser($user);

        return $cart->load(['items.product.images', 'items.product.category']);
    }

    public function addToCart(User $user, Product $product, int $quantity = 1): CartItem
    {
        return DB::transaction(function () use ($user, $product, $quantity) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $cart = $this->repository->findOrCreateForUser($user);
            $existing = $this->repository->findItem($cart, $lockedProduct->id);
            $newQuantity = ($existing?->quantity ?? 0) + $quantity;

            $this->assertStockAvailable($lockedProduct, $newQuantity);

            if ($existing) {
                $this->repository->updateItemQuantity($existing, $newQuantity);

                return $existing->refresh();
            }

            return $this->repository->createItem($cart, $lockedProduct->id, $newQuantity);
        });
    }

    /**
     * Set to an exact quantity. A quantity of 0 or less removes the item.
     */
    public function updateQuantity(CartItem $item, int $quantity): ?CartItem
    {
        if ($quantity <= 0) {
            $this->removeItem($item);

            return null;
        }

        return DB::transaction(function () use ($item, $quantity) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($item->product_id);
            $this->assertStockAvailable($lockedProduct, $quantity);
            $this->repository->updateItemQuantity($item, $quantity);

            return $item->refresh();
        });
    }

    public function increaseQuantity(CartItem $item, int $by = 1): ?CartItem
    {
        return $this->updateQuantity($item, $item->quantity + $by);
    }

    public function decreaseQuantity(CartItem $item, int $by = 1): ?CartItem
    {
        return $this->updateQuantity($item, $item->quantity - $by);
    }

    public function removeItem(CartItem $item): void
    {
        $this->repository->deleteItem($item);
    }

    public function clearCart(User $user): void
    {
        $this->repository->clear($this->repository->findOrCreateForUser($user));
    }

    /**
     * @return array{item_count: int, subtotal: float}
     */
    public function totals(Cart $cart): array
    {
        $cart->loadMissing('items.product');

        return [
            'item_count' => (int) $cart->items->sum('quantity'),
            'subtotal' => round((float) $cart->items->sum(fn (CartItem $item) => $item->lineTotal), 2),
        ];
    }

    /**
     * Re-checks every line against the product's current stock — called
     * immediately before checkout, since cart contents can go stale between
     * being added and the buyer actually paying.
     *
     * @return array<int, string> validation messages, empty if everything is in stock
     */
    public function validateForCheckout(Cart $cart): array
    {
        $cart->loadMissing('items.product');
        $errors = [];

        foreach ($cart->items as $item) {
            if ($item->quantity > $item->product->stock_quantity) {
                $errors[] = "Only {$item->product->stock_quantity} \"{$item->product->name}\" left in stock — please update your cart.";
            }
        }

        return $errors;
    }

    private function assertStockAvailable(Product $product, int $requestedQuantity): void
    {
        if ($requestedQuantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$product->stock_quantity} \"{$product->name}\" left in stock.",
            ]);
        }
    }
}
