@props(['totals', 'checkoutHref' => null])

<div class="cart-summary">
    <h2 class="cart-summary-title">Order Summary</h2>

    <div class="cart-summary-row">
        <span>Items ({{ $totals['item_count'] }})</span>
        <x-price-badge :price="$totals['subtotal']" />
    </div>

    <p class="cart-summary-note">Shipping and totals are calculated at checkout.</p>

    @if ($checkoutHref)
        <x-button :href="$checkoutHref" variant="primary" class="btn-block">Proceed to Checkout</x-button>
    @endif
</div>
