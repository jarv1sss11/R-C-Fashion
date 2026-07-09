<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderReceivedConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'order_received_confirmed',
            'title'        => 'Delivery Confirmed by Buyer',
            'message'      => "The buyer confirmed receipt of order {$this->order->order_number}.",
            'order_id'     => $this->order->id,
            'url'          => route('vendor.orders.show', $this->order),
        ];
    }
}
