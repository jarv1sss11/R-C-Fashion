<x-layouts.app title="My Orders — R&C Fashion">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[
                ['label' => 'Home', 'href' => route('home')],
                ['label' => 'My Orders'],
            ]" />

            <x-flash-status />

            <h1 class="catalog-heading">My Orders</h1>

            @if ($orders->isEmpty())
                <x-empty-state
                    title="No orders yet"
                    message="Once you place an order, it will show up here."
                >
                    <x-button :href="route('products.index')" variant="primary">Explore Collection</x-button>
                </x-empty-state>
            @else
                <div class="order-list">
                    @foreach ($orders as $order)
                        <x-order-card :order="$order" />
                    @endforeach
                </div>

                <x-pagination :paginator="$orders" />
            @endif
        </div>
    </main>
</x-layouts.app>
