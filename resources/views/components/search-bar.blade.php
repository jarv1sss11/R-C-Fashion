@props(['term' => null])

<form method="GET" action="{{ route('search.index') }}" class="search-bar" role="search">
    <input
        type="search"
        name="q"
        value="{{ $term }}"
        placeholder="Search products..."
        class="search-bar-input"
        aria-label="Search products"
    >
    <button type="submit" class="search-bar-submit">
        <x-icon name="search" class="search-bar-icon" />
    </button>
</form>
