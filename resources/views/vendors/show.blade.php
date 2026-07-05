<x-layouts.app :title="$vendor->store_name . ' — R&C Fashion'">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[['label' => 'Home', 'href' => route('home')], ['label' => $vendor->store_name]]" />

            @if ($vendor->banner_url)
                <div class="vendor-storefront-banner">
                    <img src="{{ $vendor->banner_url }}" alt="{{ $vendor->store_name }} banner">
                </div>
            @endif

            <div class="vendor-storefront-header">
                @if ($vendor->logo_url)
                    <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->store_name }}" class="vendor-storefront-logo">
                @endif
                <div>
                    <h1 class="catalog-heading">{{ $vendor->store_name }}</h1>
                    @if ($vendor->description)
                        <p class="vendor-storefront-description">{{ $vendor->description }}</p>
                    @endif

                    <div class="vendor-storefront-meta">
                        <x-rating-stars :rating="$averageRating" :count="$productCount" />
                        <span>{{ $productCount }} product{{ $productCount === 1 ? '' : 's' }}</span>
                        <span>Joined {{ $vendor->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>

            <h2 class="catalog-subheading">Products from this store</h2>
            <x-product-grid :products="$products" />
            <x-pagination :paginator="$products" />
        </div>
    </main>
</x-layouts.app>
