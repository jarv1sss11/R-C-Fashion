<x-layouts.app title="My Products — R&C Fashion">
    <x-navbar variant="full" />

    <main class="vendor">
        <div class="container vendor-inner">
            <x-vendor-sidebar active="products" />

            <div class="vendor-content">
                <div class="vendor-heading-row">
                    <h1 class="vendor-heading">My Products</h1>
                    <x-button :href="route('vendor.products.create')" variant="primary">Add Product</x-button>
                </div>

                <x-flash-status />

                @if ($products->isEmpty())
                    <x-empty-state
                        title="No products yet"
                        message="Add your first product to start building your store."
                    >
                        <x-button :href="route('vendor.products.create')" variant="outline">Add a Product</x-button>
                    </x-empty-state>
                @else
                    <x-product-table :products="$products" />
                    <x-pagination :paginator="$products" />
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
