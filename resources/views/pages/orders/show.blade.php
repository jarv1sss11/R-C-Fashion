<x-layouts.app :title="$order->order_number . ' — R&C Fashion'">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[
                ['label' => 'Home', 'href' => route('home')],
                ['label' => 'My Orders', 'href' => route('orders.index')],
                ['label' => $order->order_number],
            ]" />

            <x-flash-status />

            <div class="order-detail-header">
                <div>
                    <h1 class="catalog-heading">{{ $order->order_number }}</h1>
                    <p class="order-detail-date">Placed {{ $order->created_at->format('d M Y, H:i') }}</p>
                </div>
                <div class="order-card-badges">
                    <x-status-badge :status="$order->order_status" />
                    <x-status-badge :status="$order->payment_status" />
                    <x-status-badge :status="$order->delivery_status" />
                </div>
            </div>

            @php
                $trackingSteps = ['pending' => 'Order Placed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
                $currentStepIndex = array_search($order->delivery_status, array_keys($trackingSteps), true);
            @endphp

            <div class="order-tracking">
                @foreach ($trackingSteps as $step => $label)
                    <div class="order-tracking-step {{ $loop->index <= $currentStepIndex ? 'is-complete' : '' }}">
                        <span class="order-tracking-dot"></span>
                        <span class="order-tracking-label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>

            <div class="cart-layout">
                <div class="order-detail-items">
                    <h2 class="checkout-section-title">Items</h2>
                    @foreach ($order->items as $item)
                        <div class="order-detail-item">
                            <span class="order-detail-item-name">{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                            <x-status-badge :status="$item->fulfillment_status" />
                            <x-price-badge :price="$item->lineTotal" />
                        </div>
                    @endforeach

                    <h2 class="checkout-section-title">Shipping Address</h2>
                    <p>{{ $order->shipping_name }}<br>{{ $order->shipping_line1 }}, {{ $order->shipping_city }}<br>{{ $order->shipping_phone }}</p>
                    <p class="order-detail-meta">Delivery: {{ ucfirst($order->delivery_option) }} &middot; Payment: {{ $order->payment_method === 'cash_on_delivery' ? 'Cash on Delivery' : 'Card' }}</p>
                </div>

                <div class="cart-summary">
                    <h2 class="cart-summary-title">Order Total</h2>
                    <div class="cart-summary-row"><span>Subtotal</span><x-price-badge :price="$order->subtotal" /></div>
                    <div class="cart-summary-row"><span>Shipping</span><x-price-badge :price="$order->shipping_cost" /></div>
                    <div class="cart-summary-row"><span>Tax</span><x-price-badge :price="$order->tax" /></div>
                    <div class="cart-summary-row cart-summary-row--total"><span>Total</span><x-price-badge :price="$order->total" /></div>
                </div>
            </div>
        </div>
    </main>
</x-layouts.app>
