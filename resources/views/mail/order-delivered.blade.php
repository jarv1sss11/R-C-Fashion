<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Confirmed</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #F8F6F2; color: #111; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border: 1px solid #D8D2C8; }
        .header { background: #111111; color: #C8A44D; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 0.04em; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; margin-top: 0; }
        .confirm-box { background: #F8F6F2; border: 2px solid #C8A44D; padding: 20px; margin: 20px 0; }
        .confirm-box p { margin: 0; font-size: 14px; }
        .footer { background: #F8F6F2; border-top: 1px solid #D8D2C8; padding: 16px 32px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header"><h1>R&amp;C Fashion</h1></div>
        <div class="body">
            <h2>Thanks for confirming delivery!</h2>
            <p>Hi {{ $order->user->name ?? $order->shipping_name }}, we've marked your order as delivered based on your confirmation.</p>

            <div class="confirm-box">
                <p>We hope you love your new items. If anything's not right, please reach out and we'll sort it out.</p>
            </div>

            <p style="font-size:14px;"><strong>Order:</strong> {{ $order->order_number }}<br>
            <strong>Delivered to:</strong> {{ $order->shipping_name }}, {{ $order->shipping_line1 }}, {{ $order->shipping_city }}</p>
        </div>
        <div class="footer">
            <p>This is an automated message from R&amp;C Fashion.</p>
        </div>
    </div>
</body>
</html>
