<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class NewOrderVendorNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly Collection $items,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'new_order',
            'title'        => 'New Order Received',
            'message'      => "New order {$this->order->order_number} contains {$this->items->count()} item(s) from your store.",
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'item_count'   => $this->items->count(),
            'url'          => route('vendor.orders.show', $this->order),
        ];
    }
}
