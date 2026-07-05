@props(['product'])

<div class="product-gallery" data-product-gallery>
    <div class="product-gallery-main">
        @if ($product->images->isNotEmpty())
            <img src="{{ $product->images->first()->url }}" alt="{{ $product->name }}" data-gallery-main>
        @else
            <span class="product-gallery-main--empty" data-gallery-main></span>
        @endif
    </div>

    @if ($product->images->count() > 1)
        <div class="product-gallery-thumbs">
            @foreach ($product->images as $image)
                <button
                    type="button"
                    class="product-gallery-thumb {{ $loop->first ? 'is-active' : '' }}"
                    data-gallery-thumb
                    data-image-url="{{ $image->url }}"
                >
                    <img src="{{ $image->url }}" alt="">
                </button>
            @endforeach
        </div>
    @endif
</div>
