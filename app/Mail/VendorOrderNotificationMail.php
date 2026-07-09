<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VendorOrderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly Collection $items,
        public readonly string $vendorStoreName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Order {$this->order->order_number} — R&C Fashion",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.vendor-order-notification',
        );
    }
}
