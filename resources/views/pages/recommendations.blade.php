<x-layouts.app title="Your Recommendations — R&C Fashion">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[
                ['label' => 'Home', 'href' => route('home')],
                ['label' => 'Recommendations'],
            ]" />

            <x-flash-status />

            <h1 class="catalog-heading">Recommended For You</h1>
            <p class="recommendations-subtitle">Personalized picks based on your browsing, wishlist, and shopping activity.</p>

            <nav class="algorithm-switch" aria-label="Compare recommendation algorithms">
                @foreach (['hybrid' => 'Hybrid (Default)', 'content' => 'Content-Based', 'collaborative' => 'Collaborative', 'popularity' => 'Trending'] as $value => $label)
                    <a
                        href="{{ route('recommendations.index', ['algorithm' => $value]) }}"
                        class="algorithm-switch-link {{ $algorithm === $value ? 'is-active' : '' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            @if (count($results) === 0)
                <x-empty-state
                    title="No recommendations yet"
                    message="Browse products, add items to your wishlist, or make a purchase — we'll start tailoring picks for you."
                />
            @else
                <div class="recommendation-grid recommendation-grid--page">
                    @foreach ($results as $result)
                        <x-recommendation-card :result="$result" module="recommendations_page" />
                    @endforeach
                </div>
            @endif
        </div>
    </main>
</x-layouts.app>
