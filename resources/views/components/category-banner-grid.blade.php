@props(['categories'])

@php
    // Static mapping, not a DB column — these are versioned editorial assets
    // (see docs/ image-integration plan), not vendor-uploaded content, so they
    // don't belong on the `categories` table itself.
    $categoryImages = [
        'men' => 'men.jpg',
        'women' => 'women.jpg',
        'kids' => 'kids.jpg',
        'sports' => 'sports.jpg',
        'accessories' => 'accessories.jpg',
    ];
@endphp

<div class="category-banner-grid">
    @foreach ($categories as $index => $category)
        @php
            $imagePath = public_path('images/editorial/categories/'.($categoryImages[$category->slug] ?? ''));
            $hasImage = isset($categoryImages[$category->slug]) && file_exists($imagePath);
            $tone = ($index % 3) + 1;
        @endphp

        <a href="{{ route('categories.show', $category->slug) }}" class="category-banner {{ ! $hasImage ? 'editorial-card--tone-'.$tone : '' }}">
            @if ($hasImage)
                <img src="{{ asset('images/editorial/categories/'.$categoryImages[$category->slug]) }}" alt="{{ $category->name }}" class="category-banner-image">
            @endif
            <div class="category-banner-scrim"></div>
            <span class="category-banner-name">{{ $category->name }}</span>
        </a>
    @endforeach
</div>
