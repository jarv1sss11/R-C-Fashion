@props([
    'price',
    'currency' => 'KES',
])

<span {{ $attributes->merge(['class' => 'price-badge']) }}>{{ $currency }} {{ number_format($price, 2) }}</span>
