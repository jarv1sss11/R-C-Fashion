<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

/**
 * Covers both `Order` (auto-discovered — Order → OrderPolicy matches
 * Laravel's convention) and `OrderItem` (registered manually in
 * AppServiceProvider, since OrderItem → OrderPolicy does not match).
 * One class because both abilities answer the same underlying question:
 * "does this user have a legitimate reason to see/touch this order?"
 */
class OrderPolicy
{
    /**
     * The buyer who placed the order, or any vendor with at least one item
     * in it — never another buyer, never an unrelated vendor.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->id === $order->user_id) {
            return true;
        }

        return $user->role === 'vendor' && $order->items()->where('vendor_id', $user->id)->exists();
    }

    /**
     * A vendor may only update fulfillment on their own line items — never
     * another vendor's items in the same shared order.
     */
    public function updateFulfillment(User $user, OrderItem $orderItem): bool
    {
        return $user->id === $orderItem->vendor_id;
    }

    /**
     * Only the buyer who placed the order can confirm they received it —
     * unlike view(), a vendor with items in the order does not qualify here.
     */
    public function confirmReceived(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
}
