@props(['products'])

@if ($products->isEmpty())
    <x-empty-state
        title="No products found"
        message="Try adjusting your filters or search terms."
    />
@else
    <div class="product-grid">
        @foreach ($products as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
@endif
