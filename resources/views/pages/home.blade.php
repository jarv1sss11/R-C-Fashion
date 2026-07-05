<x-layouts.app title="R&C Fashion — Fashion That Defines You.">
    <x-navbar variant="full" />

    <main class="hero">
        <div class="container">
            <x-flash-status />
        </div>

        <div class="container hero-inner">
            <div class="hero-copy">
                <h1 class="hero-headline">Fashion That Defines <span class="hero-headline-accent">You.</span></h1>
                <p class="hero-subtitle">Made for Kenyan fashion shopping.</p>
                <p class="hero-description">Quality, individuality, and Kenyan craftsmanship—curated for the modern shopper, from vendors you can trust.</p>

                <div class="hero-actions">
                    <x-button :href="gated_route(route('products.index'))" variant="primary">Explore Collection</x-button>
                    <x-button :href="gated_route(route('recommendations.index'))" variant="text-link" icon="arrow-right">Get Outfit Suggestions</x-button>
                </div>
            </div>

            <div class="hero-gallery">
                <div class="editorial-card-grid">
                    <x-editorial-card title="Timeless Essentials" :href="gated_route(route('products.index'))" :tone="1" image="hero-1.jpg" />
                    <x-editorial-card title="Premium Footwear" :href="gated_route(route('products.index'))" :tone="2" image="hero-2.jpg" />
                    <x-editorial-card title="Finishing Touches" :href="gated_route(route('products.index'))" :tone="3" image="hero-3.jpg" />
                </div>

                <ul class="trust-sidebar">
                    <x-trust-item icon="hanger" label="Curated Outfits" sublabel="Handpicked for you" />
                    <x-trust-item icon="badge-check" label="Premium Quality" sublabel="Only the best" />
                    <x-trust-item icon="shield" label="Secure Payments" sublabel="M-Pesa & Cards" />
                    <x-trust-item icon="truck" label="Fast Delivery" sublabel="Across Kenya" />
                </ul>
            </div>
        </div>

        <div class="container">
            @if ($featuredCollections->isNotEmpty())
                <section class="home-section">
                    <h2 class="home-section-title">Featured Collections</h2>
                    <x-product-grid :products="$featuredCollections" />
                </section>
            @endif

            @if (count($trending) > 0)
                <x-recommendation-section title="Trending Products" :results="$trending" module="home_trending" />
            @endif

            <x-titled-product-section title="New Arrivals" :products="$newArrivals" />

            @if ($categories->isNotEmpty())
                <section class="home-section">
                    <h2 class="home-section-title">Shop by Category</h2>
                    <x-category-banner-grid :categories="$categories" />
                </section>
            @endif

            <x-lifestyle-banner
                title="Dress For Every Season"
                subtitle="Editorial-ready looks for work, weekends, and everything between."
                :href="gated_route(route('products.index'))"
                :image="file_exists(public_path('images/editorial/lifestyle/home.jpg')) ? asset('images/editorial/lifestyle/home.jpg') : null"
            />

            @auth
                <x-recommendation-section
                    title="Recommended For You"
                    :results="$recommended"
                    module="home"
                    emptyMessage="Browse a few products or add something to your wishlist and we'll start tailoring picks for you."
                />
            @endauth

            <x-titled-product-section title="Popular Products" :products="$popular" />

            <x-newsletter-banner />
        </div>
    </main>
</x-layouts.app>
