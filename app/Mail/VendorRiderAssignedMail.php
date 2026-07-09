<?php

namespace App\Mail;

use App\Models\DeliveryAssignment;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VendorRiderAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly DeliveryAssignment $assignment,
        public readonly Collection $items,
        public readonly string $vendorStoreName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rider Assigned for Order {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.vendor-rider-assigned');
    }
}
