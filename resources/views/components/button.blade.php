@props([
    'href' => null,
    'variant' => 'primary',
    'icon' => null,
    'type' => null,
])

@php
    $tag = $type ? 'button' : 'a';
@endphp

<{{ $tag }}
    @if ($type) type="{{ $type }}" @endif
    @if (!$type) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'btn btn-' . $variant]) }}
>
    <span>{{ $slot }}</span>
    @if ($icon)
        <x-icon :name="$icon" class="btn-icon" />
    @endif
</{{ $tag }}>
