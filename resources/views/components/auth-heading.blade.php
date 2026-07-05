@props([
    'lines',
    'subtitle' => null,
    'divider' => false,
])

<div class="auth-heading">
    <h1 class="auth-heading-title">
        {{ $lines[0] }}<br>
        <span class="auth-heading-accent">{{ $lines[1] }}</span>
    </h1>

    @if ($subtitle)
        <p class="auth-heading-subtitle">{{ $subtitle }}</p>
    @endif

    @if ($divider)
        <hr class="auth-heading-divider">
    @endif
</div>
