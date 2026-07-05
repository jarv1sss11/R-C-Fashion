<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(private readonly OrderRepository $repository)
    {
    }

    public function generateOrderNumber(): string
    {
        do {
            $number = 'RC-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    public function ordersForBuyer(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->ordersForUser($user->id, $perPage);
    }

    public function orderItemsForVendor(User $vendor, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->itemsForVendor($vendor->id, $perPage);
    }

    public function pendingOrderItemCount(User $vendor): int
    {
        return $this->repository->pendingItemCountForVendor($vendor->id);
    }

    /**
     * A vendor viewing a shared order only ever sees their own line items
     * — never another vendor's products within the same order.
     *
     * @return Collection<int, OrderItem>
     */
    public function vendorItemsInOrder(Order $order, User $vendor): Collection
    {
        return $this->repository->itemsForVendorInOrder($vendor->id, $order->id);
    }

    /**
     * A vendor only ever updates the fulfillment status of their own line
     * items — never another vendor's items in the same order (no
     * multi-vendor checkout splitting, but each vendor still only manages
     * their own slice of a shared order).
     */
    public function updateFulfillmentStatus(OrderItem $item, string $status): void
    {
        $item->update(['fulfillment_status' => $status]);

        $this->syncOrderDeliveryStatus($item->order);
    }

    /**
     * Derives the parent order's delivery/order status from its items'
     * fulfillment statuses, rather than tracking it independently — a
     * single order is "delivered" only once every vendor's items are.
     */
    private function syncOrderDeliveryStatus(Order $order): void
    {
        $order->loadMissing('items');

        if ($order->items->every(fn (OrderItem $item) => $item->fulfillment_status === 'delivered')) {
            $order->update(['delivery_status' => 'delivered', 'order_status' => 'completed']);
        } elseif ($order->items->contains(fn (OrderItem $item) => $item->fulfillment_status !== 'pending')) {
            $order->update(['delivery_status' => 'shipped']);
        }
    }
}
