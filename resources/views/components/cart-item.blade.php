@props(['item'])

@php $product = $item->product; @endphp

<div class="cart-item">
    <a href="{{ route('products.show', $product->slug) }}" class="cart-item-image">
        @if ($product->images->isNotEmpty())
            <img src="{{ $product->images->first()->url }}" alt="{{ $product->name }}">
        @else
            <span class="cart-item-image--empty"></span>
        @endif
    </a>

    <div class="cart-item-body">
        <a href="{{ route('products.show', $product->slug) }}" class="cart-item-name">{{ $product->name }}</a>
        <x-price-badge :price="$product->price" :currency="$product->currency" />

        @if ($item->quantity > $product->stock_quantity)
            <p class="cart-item-warning">Only {{ $product->stock_quantity }} left in stock — please update the quantity.</p>
        @endif
    </div>

    <div class="cart-item-quantity">
        <form method="POST" action="{{ route('cart.decrease', $item) }}">
            @csrf
            <button type="submit" class="cart-item-qty-btn" aria-label="Decrease quantity" @disabled($item->quantity <= 1)>&minus;</button>
        </form>
        <span class="cart-item-qty-value">{{ $item->quantity }}</span>
        <form method="POST" action="{{ route('cart.increase', $item) }}">
            @csrf
            <button type="submit" class="cart-item-qty-btn" aria-label="Increase quantity">+</button>
        </form>
    </div>

    <div class="cart-item-total">
        <x-price-badge :price="$item->lineTotal" :currency="$product->currency" />
    </div>

    <form method="POST" action="{{ route('cart.destroy', $item) }}" class="cart-item-remove-form">
        @csrf
        @method('DELETE')
        <button type="submit" class="cart-item-remove">Remove</button>
    </form>
</div>
