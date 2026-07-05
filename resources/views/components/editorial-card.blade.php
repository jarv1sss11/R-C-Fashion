@props([
    'title',
    'href' => '#',
    'tone' => 1,
    'image' => null,
])

@php
    $imagePath = $image ? public_path('images/editorial/hero/'.$image) : null;
    $hasImage = $imagePath && file_exists($imagePath);
@endphp

<a href="{{ $href }}" class="editorial-card {{ ! $hasImage ? 'editorial-card--tone-'.$tone : '' }}">
    @if ($hasImage)
        <img src="{{ asset('images/editorial/hero/'.$image) }}" alt="" class="editorial-card-image">
    @endif
    <span class="editorial-card-scrim"></span>
    <span class="editorial-card-caption">
        <span class="editorial-card-title">{{ $title }}</span>
        <span class="editorial-card-cta">
            Shop Now
            <x-icon name="arrow-right" class="editorial-card-cta-icon" />
        </span>
    </span>
</a>
