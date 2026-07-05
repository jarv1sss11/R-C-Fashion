<x-layouts.app title="Checkout — R&C Fashion">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[
                ['label' => 'Home', 'href' => route('home')],
                ['label' => 'Cart', 'href' => route('cart.index')],
                ['label' => 'Checkout'],
            ]" />

            <x-flash-status />

            <h1 class="catalog-heading">Checkout</h1>

            <div class="cart-layout">
                <form method="POST" action="{{ route('checkout.store') }}" class="checkout-form">
                    @csrf

                    <h2 class="checkout-section-title">Shipping Address</h2>
                    <x-input-field label="Full Name" name="shipping_name" :value="$defaultAddress?->label ?? auth()->user()->name" />
                    <x-input-field label="Address Line" name="shipping_line1" :value="$defaultAddress?->line1" />
                    <x-input-field label="City / Town" name="shipping_city" :value="$defaultAddress?->city" />
                    <x-input-field label="Phone Number" name="shipping_phone" :value="$defaultAddress?->phone ?? auth()->user()->phone" />

                    <h2 class="checkout-section-title">Delivery Option</h2>
                    <x-select-field
                        label="Delivery Option"
                        name="delivery_option"
                        :options="['standard' => 'Standard Delivery — KES 200.00', 'express' => 'Express Delivery — KES 500.00']"
                        value="standard"
                    />

                    <h2 class="checkout-section-title">Payment Method</h2>
                    <x-select-field
                        label="Payment Method"
                        name="payment_method"
                        :options="['mock_card' => 'Card (Placeholder — no real payment processed)', 'cash_on_delivery' => 'Cash on Delivery']"
                        value="mock_card"
                    />

                    <x-button type="submit" variant="primary" class="btn-block">Review &amp; Place Order</x-button>
                </form>

                <div class="cart-summary">
                    <h2 class="cart-summary-title">Order Summary</h2>

                    @foreach ($cart->items as $item)
                        <div class="cart-summary-line">
                            <span>{{ $item->product->name }} &times; {{ $item->quantity }}</span>
                            <x-price-badge :price="$item->lineTotal" />
                        </div>
                    @endforeach

                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <x-price-badge :price="$totals['subtotal']" />
                    </div>

                    <p class="cart-summary-note">Shipping cost is added based on your delivery option. Final total shown on your order confirmation.</p>
                </div>
            </div>
        </div>
    </main>
</x-layouts.app>
