<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Assigned</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #F8F6F2; color: #111; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border: 1px solid #D8D2C8; }
        .header { background: #111111; color: #C8A44D; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 0.04em; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; margin-top: 0; }
        .rider-box { background: #F8F6F2; border: 2px solid #C8A44D; padding: 20px; margin: 20px 0; }
        .rider-box h3 { margin: 0 0 12px; font-size: 15px; color: #888; text-transform: uppercase; letter-spacing: 0.06em; }
        .rider-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 14px; border-bottom: 1px solid #D8D2C8; }
        .rider-row:last-child { border-bottom: none; }
        .footer { background: #F8F6F2; border-top: 1px solid #D8D2C8; padding: 16px 32px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header"><h1>R&amp;C Fashion</h1></div>
        <div class="body">
            <h2>Your rider is on the way!</h2>
            <p>Hi {{ $order->user->name ?? $order->shipping_name }}, great news! A rider has been assigned to deliver your order.</p>

            <div class="rider-box">
                <h3>Rider Details</h3>
                <div class="rider-row"><span>Rider Name</span><strong>{{ $assignment->rider->name }}</strong></div>
                <div class="rider-row"><span>Phone</span><span>{{ $assignment->rider->phone }}</span></div>
                <div class="rider-row"><span>Vehicle</span><span>{{ ucfirst($assignment->rider->vehicle_type) }}</span></div>
                @if($assignment->rider->number_plate)
                <div class="rider-row"><span>Number Plate</span><span>{{ $assignment->rider->number_plate }}</span></div>
                @endif
                @if($assignment->estimated_delivery)
                <div class="rider-row"><span>Est. Delivery</span><span>{{ $assignment->estimated_delivery->format('d M Y, H:i') }}</span></div>
                @endif
            </div>

            <p style="font-size:14px;"><strong>Order:</strong> {{ $order->order_number }}<br>
            <strong>Delivering to:</strong> {{ $order->shipping_name }}, {{ $order->shipping_line1 }}, {{ $order->shipping_city }}</p>
        </div>
        <div class="footer">
            <p>This is an automated message from R&amp;C Fashion.</p>
        </div>
    </div>
</body>
</html>
