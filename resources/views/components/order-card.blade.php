@props(['order'])

<a href="{{ route('orders.show', $order) }}" class="order-card">
    <div class="order-card-header">
        <span class="order-card-number">{{ $order->order_number }}</span>
        <span class="order-card-date">{{ $order->created_at->format('d M Y') }}</span>
    </div>

    <div class="order-card-badges">
        <x-status-badge :status="$order->order_status" />
        <x-status-badge :status="$order->payment_status" />
        <x-status-badge :status="$order->delivery_status" />
    </div>

    <div class="order-card-footer">
        <span>{{ $order->items->sum('quantity') }} item(s)</span>
        <x-price-badge :price="$order->total" />
    </div>
</a>
