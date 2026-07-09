<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
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
        .totals { margin-top: 16px; }
        .totals .row { display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0; }
        .totals .row.total { font-weight: 700; border-top: 2px solid #111111; margin-top: 8px; padding-top: 8px; }
        .footer { background: #F8F6F2; border-top: 1px solid #D8D2C8; padding: 16px 32px; font-size: 12px; color: #666; }
        .badge { display: inline-block; background: #C8A44D; color: #111; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>R&amp;C Fashion</h1>
        </div>
        <div class="body">
            <h2>Your order is confirmed!</h2>
            <p>Hi {{ $order->user->name ?? $order->shipping_name }}, thank you for your purchase.</p>

            <div class="order-meta">
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('d M Y, H:i') }}</p>
                <p><strong>Payment Method:</strong>
                    @if($order->payment_method === 'mpesa') M-Pesa (Simulated)
                    @elseif($order->payment_method === 'cash_on_delivery') Cash on Delivery
                    @else Card (Simulated) @endif
                </p>
                <p><strong>Payment Status:</strong> <span class="badge">{{ strtoupper($order->payment_status) }}</span></p>
            </div>

            <h3 style="font-size:15px; margin-bottom:8px;">Items Ordered</h3>
            @foreach($order->items as $item)
            <div class="item-row">
                <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                <span>KES {{ number_format($item->unit_price * $item->quantity, 2) }}</span>
            </div>
            @endforeach

            <div class="totals">
                <div class="row"><span>Subtotal</span><span>KES {{ number_format($order->subtotal, 2) }}</span></div>
                <div class="row"><span>Shipping</span><span>KES {{ number_format($order->shipping_cost, 2) }}</span></div>
                <div class="row total"><span>Total</span><span>KES {{ number_format($order->total, 2) }}</span></div>
            </div>

            <p style="margin-top:24px; font-size:14px;">
                <strong>Shipping to:</strong> {{ $order->shipping_name }}, {{ $order->shipping_line1 }}, {{ $order->shipping_city }}<br>
                <strong>Phone:</strong> {{ $order->shipping_phone }}
            </p>
        </div>
        <div class="footer">
            <p>This is an automated message from R&amp;C Fashion. This is a simulated academic environment &mdash; no real payment was processed.</p>
        </div>
    </div>
</body>
</html>
