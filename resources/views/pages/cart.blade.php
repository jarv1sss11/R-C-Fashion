<x-layouts.app title="Your Cart — R&C Fashion">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[
                ['label' => 'Home', 'href' => route('home')],
                ['label' => 'Cart'],
            ]" />

            <x-flash-status />

            <h1 class="catalog-heading">Your Cart</h1>

            @if ($cart->items->isEmpty())
                <x-empty-state
                    title="Your cart is empty"
                    message="Browse the catalogue and add something you like."
                >
                    <x-button :href="route('products.index')" variant="primary">Explore Collection</x-button>
                </x-empty-state>
            @else
                <div class="cart-layout">
                    <div class="cart-items">
                        @foreach ($cart->items as $item)
                            <x-cart-item :item="$item" />
                        @endforeach

                        <form method="POST" action="{{ route('cart.clear') }}" class="cart-clear-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cart-clear-link">Clear Cart</button>
                        </form>
                    </div>

                    <x-cart-summary :totals="$totals" :checkout-href="route('checkout.index')" />
                </div>
            @endif
        </div>
    </main>
</x-layouts.app>
