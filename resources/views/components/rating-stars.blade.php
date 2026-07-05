@props([
    'rating' => null,
    'count' => 0,
])

@if ($rating === null || $count === 0)
    <span class="rating-stars rating-stars--empty">No reviews yet</span>
@else
    @php
        $rounded = round($rating);
    @endphp
    <span class="rating-stars" aria-label="{{ number_format($rating, 1) }} out of 5 stars">
        @for ($i = 1; $i <= 5; $i++)
            <span class="rating-star {{ $i <= $rounded ? 'is-filled' : '' }}">★</span>
        @endfor
        <span class="rating-stars-count">({{ $count }})</span>
    </span>
@endif
