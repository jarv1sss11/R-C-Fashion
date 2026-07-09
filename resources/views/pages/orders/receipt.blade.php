<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $order->order_number }} — R&C Fashion</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; background: #fff; padding: 24px; max-width: 480px; margin: 0 auto; }
        .receipt-header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 16px; margin-bottom: 16px; }
        .receipt-header h1 { font-size: 1.4rem; letter-spacing: 0.08em; }
        .receipt-header p { font-size: 0.8rem; color: #666; margin-top: 4px; }
        .receipt-number { text-align: center; padding: 12px; background: #F8F6F2; border: 1px solid #D8D2C8; margin-bottom: 16px; }
        .receipt-number .label { font-size: 0.7rem; color: #888; letter-spacing: 0.06em; text-transform: uppercase; }
        .receipt-number .ref { font-size: 1.3rem; font-weight: 700; letter-spacing: 0.1em; }
        .receipt-section { margin-bottom: 16px; }
        .receipt-section h2 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: #888; border-bottom: 1px solid #D8D2C8; padding-bottom: 4px; margin-bottom: 8px; }
        .receipt-row { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 3px 0; }
        .receipt-row.total { font-weight: 700; font-size: 1rem; border-top: 2px solid #111; margin-top: 6px; padding-top: 6px; }
        .receipt-disclaimer { font-size: 0.7rem; text-align: center; color: #B3261E; border: 1px dashed #B3261E; padding: 8px; margin-top: 20px; }
        .receipt-footer { text-align: center; font-size: 0.75rem; color: #888; margin-top: 24px; border-top: 1px solid #D8D2C8; padding-top: 12px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="receipt-header">
        <h1>R&amp;C Fashion</h1>
        <p>Official Purchase Receipt</p>
    </div>

    <div class="receipt-number">
        <div class="label">Receipt Number</div>
        <div class="ref">{{ $payment->payment_reference ?? '—' }}</div>
    </div>

    <div class="receipt-section">
        <h2>Order Details</h2>
        <div class="receipt-row"><span>Order Number</span><span>{{ $order->order_number }}</span></div>
        <div class="receipt-row"><span>Date</span><span>{{ $order->created_at->format('d M Y') }}</span></div>
        <div class="receipt-row"><span>Paid At</span><span>{{ $payment->paid_at?->format('d M Y, H:i') ?? '—' }}</span></div>
        <div class="receipt-row"><span>Payment Method</span>
            <span>@if($payment->payment_method === 'mpesa') M-Pesa
                  @elseif($payment->payment_method === 'cash_on_delivery') Cash on Delivery
                  @else Card @endif
            </span>
        </div>
    </div>

    <div class="receipt-section">
        <h2>Bill To</h2>
        <div class="receipt-row"><span>Name</span><span>{{ $order->shipping_name }}</span></div>
        <div class="receipt-row"><span>Address</span><span>{{ $order->shipping_line1 }}, {{ $order->shipping_city }}</span></div>
        <div class="receipt-row"><span>Phone</span><span>{{ $order->shipping_phone }}</span></div>
    </div>

    <div class="receipt-section">
        <h2>Items</h2>
        @foreach($order->items as $item)
        <div class="receipt-row">
            <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
            <span>KES {{ number_format($item->unit_price * $item->quantity, 2) }}</span>
        </div>
        @endforeach
        <div class="receipt-row" style="margin-top:6px;"><span>Subtotal</span><span>KES {{ number_format($order->subtotal, 2) }}</span></div>
        <div class="receipt-row"><span>Shipping</span><span>KES {{ number_format($order->shipping_cost, 2) }}</span></div>
        <div class="receipt-row total"><span>Total Paid</span><span>KES {{ number_format($order->total, 2) }}</span></div>
    </div>

    <div class="receipt-disclaimer">
        <strong>SIMULATED PAYMENT — ACADEMIC DEMONSTRATION ONLY</strong><br>
        No real money was charged. This receipt is generated for software engineering project purposes.
    </div>

    <div class="receipt-footer">
        <p>Thank you for shopping with R&amp;C Fashion</p>
        <p style="margin-top:4px;">{{ config('app.url') }}</p>
    </div>

    <div class="no-print" style="text-align:center;margin-top:24px;">
        <button onclick="window.print()" style="padding:10px 28px;background:#111;color:#fff;border:none;cursor:pointer;font-size:0.9rem;">
            Print / Save as PDF
        </button>
        <a href="{{ route('orders.show', $order) }}" style="margin-left:12px;font-size:0.85rem;color:#666;">
            Back to Order
        </a>
    </div>

</body>
</html>
