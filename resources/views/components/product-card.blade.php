@props(['product'])

<a href="{{ route('products.show', $product->slug) }}" class="product-card">
    <div class="product-card-image">
        @if ($product->images->isNotEmpty())
            <img src="{{ $product->images->first()->url }}" alt="{{ $product->name }}">
        @else
            <span class="product-card-image--empty"></span>
        @endif
    </div>

    <div class="product-card-body">
        <span class="product-card-vendor">{{ $product->vendor->vendorProfile->store_name ?? $product->vendor->name }}</span>
        <span class="product-card-name">{{ $product->name }}</span>
        <x-rating-stars :rating="$product->reviews_avg_rating" :count="$product->reviews_count ?? 0" />
        <x-price-badge :price="$product->price" :currency="$product->currency" />
    </div>
</a>
