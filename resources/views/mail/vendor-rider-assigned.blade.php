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
        .meta-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 14px; border-bottom: 1px solid #D8D2C8; }
        .meta-row:last-child { border-bottom: none; }
        .box { background: #F8F6F2; border: 1px solid #D8D2C8; padding: 16px; margin: 16px 0; }
        .footer { background: #F8F6F2; border-top: 1px solid #D8D2C8; padding: 16px 32px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header"><h1>R&amp;C Fashion</h1></div>
        <div class="body">
            <h2 style="margin-top:0;">Rider Assigned — {{ $order->order_number }}</h2>
            <p>Hi {{ $vendorStoreName }}, a rider has been assigned to deliver the following items from your store.</p>

            <div class="box">
                <h3 style="margin:0 0 10px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#888;">Your Items</h3>
                @foreach($items as $item)
                <div class="meta-row"><span>{{ $item->product_name }} &times; {{ $item->quantity }}</span></div>
                @endforeach
            </div>

            <div class="box">
                <h3 style="margin:0 0 10px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#888;">Rider Details</h3>
                <div class="meta-row"><span>Rider</span><strong>{{ $assignment->rider->name }}</strong></div>
                <div class="meta-row"><span>Phone</span><span>{{ $assignment->rider->phone }}</span></div>
                <div class="meta-row"><span>Vehicle</span><span>{{ ucfirst($assignment->rider->vehicle_type) }} {{ $assignment->rider->number_plate }}</span></div>
                @if($assignment->estimated_delivery)
                <div class="meta-row"><span>Est. Delivery</span><span>{{ $assignment->estimated_delivery->format('d M Y, H:i') }}</span></div>
                @endif
            </div>
        </div>
        <div class="footer"><p>This is an automated message from R&amp;C Fashion.</p></div>
    </div>
</body>
</html>
