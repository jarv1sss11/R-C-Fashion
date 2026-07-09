<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #F8F6F2; color: #111111; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border: 1px solid #D8D2C8; }
        .header { background: #111111; color: #C8A44D; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 0.04em; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; margin-top: 0; }
        .order-meta { background: #F8F6F2; border: 1px solid #D8D2C8; padding: 16px; margin-bottom: 24px; }
        .order-meta p { margin: 4px 0; font-size: 14px; }
        .item-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #D8D2C8; font-size: 14px; }
        .footer { background: #F8F6F2; border-top: 1px solid #D8D2C8; padding: 16px 32px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>R&amp;C Fashion</h1>
        </div>
        <div class="body">
            <h2>New order for {{ $vendorStoreName }}</h2>
            <p>You have received a new order. Please prepare the following items for fulfilment.</p>

            <div class="order-meta">
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('d M Y, H:i') }}</p>
                <p><strong>Ship To:</strong> {{ $order->shipping_name }}, {{ $order->shipping_city }}</p>
                <p><strong>Phone:</strong> {{ $order->shipping_phone }}</p>
            </div>

            <h3 style="font-size:15px; margin-bottom:8px;">Your Items in This Order</h3>
            @foreach($items as $item)
            <div class="item-row">
                <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                <span>KES {{ number_format($item->unit_price * $item->quantity, 2) }}</span>
            </div>
            @endforeach

            <p style="margin-top:24px; font-size:14px;">Log in to your vendor dashboard to update fulfilment status.</p>
        </div>
        <div class="footer">
            <p>This is an automated message from R&amp;C Fashion.</p>
        </div>
    </div>
</body>
</html>
