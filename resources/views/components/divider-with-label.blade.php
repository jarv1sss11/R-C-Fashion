@props(['label' => null])

<div class="divider-with-label">
    <span class="divider-with-label-line" aria-hidden="true"></span>
    <span class="divider-with-label-text">{{ $label ?? $slot }}</span>
    <span class="divider-with-label-line" aria-hidden="true"></span>
</div>
