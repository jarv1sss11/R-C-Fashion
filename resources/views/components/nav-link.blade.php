@props([
    'href' => '#',
    'active' => false,
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'nav-link' . ($active ? ' nav-link--active' : '')]) }}>
    {{ $slot }}
</a>
