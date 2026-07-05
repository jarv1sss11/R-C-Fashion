@props(['title', 'subtitle', 'href', 'image' => null])

<section class="lifestyle-banner {{ ! $image ? 'editorial-card--tone-2' : '' }}">
    @if ($image)
        <img src="{{ $image }}" alt="" class="lifestyle-banner-image">
    @endif
    <div class="lifestyle-banner-scrim"></div>
    <div class="lifestyle-banner-content">
        <h2 class="lifestyle-banner-title">{{ $title }}</h2>
        <p class="lifestyle-banner-subtitle">{{ $subtitle }}</p>
        <x-button :href="$href" variant="primary">Shop Now</x-button>
    </div>
</section>
