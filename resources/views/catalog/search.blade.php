<x-layouts.app title="Search — R&C Fashion">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[['label' => 'Home', 'href' => route('home')], ['label' => 'Search']]" />

            <h1 class="catalog-heading">Search Products</h1>

            <x-search-bar :term="$term" />

            <x-flash-status />

            @if ($products === null)
                <p class="catalog-empty">Enter a search term above to find products.</p>
            @else
                <p class="catalog-search-summary">{{ $products->total() }} result(s) for &ldquo;{{ $term }}&rdquo;</p>

                <div class="catalog-layout">
                    <x-filter-sidebar
                        :categories="$categories"
                        :brands="$brands"
                        :colors="$colors"
                        :sizes="$sizes"
                        :materials="$materials"
                        :seasons="$seasons"
                        :styles="$styles"
                        :ageGroups="$ageGroups"
                        :filters="$filters"
                    />

                    <div class="catalog-content">
                        <x-product-grid :products="$products" />
                        <x-pagination :paginator="$products" />
                    </div>
                </div>
            @endif
        </div>
    </main>
</x-layouts.app>
