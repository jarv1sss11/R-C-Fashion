@props(['term' => null, 'compact' => false])

<div
    class="search-bar-wrapper{{ $compact ? ' search-bar-wrapper--compact' : '' }}"
    data-suggestions-url="{{ auth()->check() ? route('search.suggestions') : '' }}"
>
    @if ($compact)
        <button type="button" class="navbar-search-toggle" aria-label="Open search" aria-expanded="false">
            <x-icon name="search" class="navbar-search-toggle-icon" />
        </button>
    @endif

    <form method="GET" action="{{ gated_route(route('search.index')) }}" class="search-bar" role="search">
        <input
            type="search"
            name="q"
            value="{{ $term }}"
            placeholder="Search products..."
            class="search-bar-input"
            aria-label="Search products"
            autocomplete="off"
            aria-autocomplete="list"
            aria-haspopup="listbox"
        >
        <button type="submit" class="search-bar-submit">
            <x-icon name="search" class="search-bar-icon" />
        </button>
    </form>

    <div class="search-suggestions" role="listbox" aria-label="Search suggestions" hidden></div>
</div>
