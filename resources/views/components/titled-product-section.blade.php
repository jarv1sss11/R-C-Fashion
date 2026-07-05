@props(['title', 'products'])

@if ($products->isNotEmpty())
    <section class="recommendation-section">
        <h2 class="recommendation-section-title">{{ $title }}</h2>

        <div class="recommendation-grid">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>
@endif
