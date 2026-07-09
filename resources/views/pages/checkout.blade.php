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
                <form method="POST" action="{{ route('checkout.store') }}" class="checkout-form" id="checkout-form">
                    @csrf

                    <h2 class="checkout-section-title">Shipping Address</h2>
                    <x-input-field label="Full Name" name="shipping_name" :value="$defaultAddress?->label ?? auth()->user()->name" />
                    <x-input-field label="Address Line" name="shipping_line1" :value="$defaultAddress?->line1" />
                    <x-input-field label="City / Town" name="shipping_city" :value="$defaultAddress?->city" />
                    <x-input-field label="Phone Number" name="shipping_phone" :value="$defaultAddress?->phone ?? auth()->user()->phone" id="checkout-phone" />

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
                        :options="['mpesa' => 'M-Pesa (Simulated STK Push)', 'cash_on_delivery' => 'Cash on Delivery', 'mock_card' => 'Card (Placeholder — no real payment)']"
                        value="mpesa"
                        id="payment-method-select"
                    />

                    <x-button type="submit" variant="primary" class="btn-block" id="checkout-submit-btn">Review &amp; Place Order</x-button>
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

    {{-- M-Pesa STK Push Simulation Modal --}}
    <div class="mpesa-modal-backdrop" id="mpesa-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="mpesa-modal-title">
        <div class="mpesa-modal">
            <div class="mpesa-modal-logo">
                <span class="mpesa-logo-mark">M</span>
                <span class="mpesa-logo-text">M-PESA</span>
            </div>

            <h2 class="mpesa-modal-title" id="mpesa-modal-title">Sending STK Push</h2>

            <div class="mpesa-modal-body" id="mpesa-step-push">
                <p class="mpesa-modal-subtitle">A payment request will be sent to:</p>
                <p class="mpesa-phone-display" id="mpesa-phone-display">0712 345 678</p>
                <p class="mpesa-amount-display">KES {{ number_format($totals['subtotal'], 2) }}</p>

                <div class="mpesa-spinner-wrap">
                    <div class="mpesa-spinner"></div>
                </div>
                <p class="mpesa-status-text" id="mpesa-status-text">Authenticating...</p>

                <p class="mpesa-disclaimer">
                    <strong>SIMULATION ONLY</strong> — This is an academic demonstration.<br>
                    No real money will be deducted.
                </p>
            </div>

            <div class="mpesa-modal-body mpesa-success" id="mpesa-step-success" hidden>
                <div class="mpesa-check-icon">&#10003;</div>
                <p class="mpesa-success-text">Payment Successful!</p>
                <p class="mpesa-success-sub">Placing your order...</p>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const form     = document.getElementById('checkout-form');
        const modal    = document.getElementById('mpesa-modal');
        const stepPush = document.getElementById('mpesa-step-push');
        const stepOk   = document.getElementById('mpesa-step-success');
        const statusEl = document.getElementById('mpesa-status-text');
        const phoneEl  = document.getElementById('mpesa-phone-display');
        const phoneSrc = document.getElementById('checkout-phone');
        const select   = document.getElementById('payment-method-select');

        let intercepting = false;

        form.addEventListener('submit', function (e) {
            if (select.value !== 'mpesa' || intercepting) return;

            e.preventDefault();
            intercepting = true;

            // Show phone from input
            if (phoneSrc) phoneEl.textContent = phoneSrc.value || '—';

            modal.removeAttribute('aria-hidden');
            modal.classList.add('is-open');

            // Simulate STK Push steps
            const steps = [
                { delay: 800,  text: 'Sending STK Push...' },
                { delay: 900,  text: 'Processing Payment...' },
                { delay: 900,  done: true },
            ];

            let elapsed = 0;
            steps.forEach(function (step) {
                elapsed += step.delay;
                setTimeout(function () {
                    if (step.done) {
                        stepPush.hidden = true;
                        stepOk.hidden   = false;
                        setTimeout(function () {
                            form.submit();
                        }, 900);
                    } else {
                        statusEl.textContent = step.text;
                    }
                }, elapsed);
            });
        });
    })();
    </script>
</x-layouts.app>
