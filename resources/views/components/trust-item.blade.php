@props([
    'icon',
    'label',
    'sublabel',
])

<li class="trust-item">
    <span class="trust-item-icon">
        <x-icon :name="$icon" />
    </span>
    <span class="trust-item-text">
        <span class="trust-item-label">{{ $label }}</span>
        <span class="trust-item-sublabel">{{ $sublabel }}</span>
    </span>
</li>
