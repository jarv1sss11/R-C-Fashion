<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly Payment $payment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'payment_confirmed',
            'title'     => 'Payment Confirmed',
            'message'   => "Payment for order {$this->order->order_number} has been confirmed. Receipt: {$this->payment->payment_reference}",
            'order_id'  => $this->order->id,
            'url'       => route('orders.show', $this->order),
        ];
    }
}
