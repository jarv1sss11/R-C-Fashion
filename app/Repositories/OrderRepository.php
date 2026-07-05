<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Sole data-access point for `orders`/`order_items`. OrderService/
 * CheckoutService own the business logic (totals, status transitions);
 * this class only reads/writes.
 */
class OrderRepository
{
    public function create(array $attributes): Order
    {
        return Order::query()->create($attributes);
    }

    public function addItem(Order $order, array $attributes): OrderItem
    {
        return $order->items()->create($attributes);
    }

    public function ordersForUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $userId)
            ->with('items')
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(int $userId, int $orderId): ?Order
    {
        return Order::query()
            ->where('user_id', $userId)
            ->with('items')
            ->find($orderId);
    }

    /**
     * Every order item belonging to a given vendor, most recent order first
     * — the raw material for the Vendor Order Management list.
     */
    public function itemsForVendor(int $vendorId, int $perPage = 10): LengthAwarePaginator
    {
        return OrderItem::query()
            ->where('vendor_id', $vendorId)
            ->with(['order', 'product'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function itemsForVendorInOrder(int $vendorId, int $orderId): Collection
    {
        return OrderItem::query()
            ->where('vendor_id', $vendorId)
            ->where('order_id', $orderId)
            ->with('product')
            ->get();
    }

    public function pendingItemCountForVendor(int $vendorId): int
    {
        return OrderItem::query()
            ->where('vendor_id', $vendorId)
            ->where('fulfillment_status', 'pending')
            ->count();
    }
}
