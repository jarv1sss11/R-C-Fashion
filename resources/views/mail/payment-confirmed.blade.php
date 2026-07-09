<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmed</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #F8F6F2; color: #111111; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border: 1px solid #D8D2C8; }
        .header { background: #111111; color: #C8A44D; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 0.04em; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; margin-top: 0; color: #1a6b3c; }
        .receipt-box { background: #F8F6F2; border: 2px solid #C8A44D; padding: 20px; text-align: center; margin-bottom: 24px; }
        .receipt-box .ref { font-size: 22px; font-weight: 700; letter-spacing: 0.1em; color: #111; }
        .receipt-box .label { font-size: 12px; color: #666; margin-bottom: 4px; }
        .meta-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; border-bottom: 1px solid #D8D2C8; }
        .footer { background: #F8F6F2; border-top: 1px solid #D8D2C8; padding: 16px 32px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>R&amp;C Fashion</h1>
        </div>
        <div class="body">
            <h2>Payment Confirmed</h2>
            <p>Hi {{ $order->user->name ?? $order->shipping_name }}, your Cash on Delivery payment has been confirmed by our team.</p>

            <div class="receipt-box">
                <div class="label">Receipt Number</div>
                <div class="ref">{{ $payment->payment_reference }}</div>
                <div class="label" style="margin-top:8px;">{{ $payment->paid_at?->format('d M Y, H:i') }}</div>
            </div>

            <div class="meta-row"><span>Order Number</span><span>{{ $order->order_number }}</span></div>
            <div class="meta-row"><span>Amount Paid</span><span>KES {{ number_format($payment->amount, 2) }}</span></div>
            <div class="meta-row"><span>Payment Method</span><span>Cash on Delivery</span></div>
            <div class="meta-row"><span>Order Status</span><span>{{ ucfirst($order->order_status) }}</span></div>
        </div>
        <div class="footer">
            <p>This is an automated message from R&amp;C Fashion. This is a simulated academic environment &mdash; no real payment was processed.</p>
        </div>
    </div>
</body>
</html>
