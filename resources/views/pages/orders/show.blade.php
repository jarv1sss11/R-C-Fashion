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
                $trackingSteps = [
                    'pending'          => 'Order Placed',
                    'preparing'        => 'Preparing',
                    'rider_assigned'   => 'Rider Assigned',
                    'out_for_delivery' => 'Out for Delivery',
                    'delivered'        => 'Delivered',
                ];
                // Map legacy 'shipped' to out_for_delivery position
                $statusKey = $order->delivery_status === 'shipped' ? 'out_for_delivery' : $order->delivery_status;
                $currentStepIndex = array_search($statusKey, array_keys($trackingSteps), true);
                if ($currentStepIndex === false) $currentStepIndex = 0;
            @endphp

            <div class="order-tracking">
                @foreach ($trackingSteps as $step => $label)
                    <div class="order-tracking-step {{ $loop->index <= $currentStepIndex ? 'is-complete' : '' }}">
                        <span class="order-tracking-dot"></span>
                        <span class="order-tracking-label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Rider info if assigned --}}
            @if($order->deliveryAssignment)
            <div class="order-rider-box">
                <p class="order-rider-label">Delivery Rider</p>
                <p class="order-rider-name">{{ $order->deliveryAssignment->rider->name }}</p>
                <p class="order-rider-meta">{{ $order->deliveryAssignment->rider->phone }}
                    &middot; {{ ucfirst($order->deliveryAssignment->rider->vehicle_type) }}
                    @if($order->deliveryAssignment->rider->number_plate)
                        &middot; {{ $order->deliveryAssignment->rider->number_plate }}
                    @endif
                </p>
                @if($order->deliveryAssignment->estimated_delivery)
                <p class="order-rider-meta">Est. Delivery: {{ $order->deliveryAssignment->estimated_delivery->format('d M Y, H:i') }}</p>
                @endif
            </div>
            @endif

            @if($order->user_id === auth()->id() && $order->delivery_status === 'out_for_delivery')
            <form method="POST" action="{{ route('orders.confirm-received', $order) }}" style="margin-bottom: var(--space-3);">
                @csrf
                <button type="submit" class="btn btn-primary">Confirm Received</button>
            </form>
            @endif

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
                    <p class="order-detail-meta">Delivery: {{ ucfirst($order->delivery_option) }} &middot;
                        Payment:
                        @if($order->payment_method === 'mpesa') M-Pesa (Simulated)
                        @elseif($order->payment_method === 'cash_on_delivery') Cash on Delivery
                        @else Card (Simulated) @endif
                    </p>

                    {{-- Payment Details --}}
                    @if($order->payment)
                        <h2 class="checkout-section-title" style="margin-top:2rem;">Payment Details</h2>
                        <div class="order-payment-box">
                            <div class="order-payment-row">
                                <span>Status</span>
                                <x-status-badge :status="$order->payment->status" />
                            </div>
                            @if($order->payment->payment_reference)
                            <div class="order-payment-row">
                                <span>Reference / Receipt</span>
                                <span class="order-payment-ref">{{ $order->payment->payment_reference }}</span>
                            </div>
                            @endif
                            @if($order->payment->paid_at)
                            <div class="order-payment-row">
                                <span>Paid At</span>
                                <span>{{ $order->payment->paid_at->format('d M Y, H:i') }}</span>
                            </div>
                            @endif
                            <div class="order-payment-row">
                                <span>Amount</span>
                                <x-price-badge :price="$order->payment->amount" />
                            </div>

                            @if($order->payment->status === 'paid')
                            <div style="margin-top:1rem;">
                                <a href="{{ route('orders.receipt', $order) }}" target="_blank" class="btn btn-secondary btn-sm">
                                    View / Print Receipt
                                </a>
                            </div>
                            @endif

                            @if($order->payment->payment_method === 'mpesa' || $order->payment->payment_method === 'mock_card')
                            <p class="order-payment-disclaimer">
                                <strong>Simulation only</strong> — This is an academic demonstration. No real payment was processed.
                            </p>
                            @endif
                        </div>
                    @endif
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
