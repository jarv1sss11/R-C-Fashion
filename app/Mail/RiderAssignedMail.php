<?php

namespace App\Mail;

use App\Models\DeliveryAssignment;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RiderAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly DeliveryAssignment $assignment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Rider is On the Way — {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.rider-assigned');
    }
}
