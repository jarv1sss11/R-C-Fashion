<x-layouts.app title="My Wishlist — R&C Fashion">
    <x-navbar variant="full" />

    <main class="account">
        <div class="container account-inner">
            <h1 class="account-heading">My Account</h1>

            <x-account-nav active="wishlist" />

            <x-flash-status />

            @if ($products->isEmpty())
                <p class="account-empty">
                    Your wishlist is empty. Browse products and tap "Add to Wishlist" to save items here.
                </p>
            @else
                <x-product-grid :products="$products" />
            @endif
        </div>
    </main>
</x-layouts.app>
