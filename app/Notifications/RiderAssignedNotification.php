<?php

namespace App\Notifications;

use App\Models\DeliveryAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RiderAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly DeliveryAssignment $assignment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->assignment->order;
        $rider = $this->assignment->rider;

        return [
            'type'        => 'rider_assigned',
            'title'       => 'Rider Assigned',
            'message'     => "Rider {$rider->name} has been assigned to deliver order {$order->order_number}.",
            'order_id'    => $order->id,
            'rider_name'  => $rider->name,
            'rider_phone' => $rider->phone,
            'vehicle'     => $rider->vehicle_type,
            'plate'       => $rider->number_plate,
            'url'         => route('orders.show', $order),
        ];
    }
}
