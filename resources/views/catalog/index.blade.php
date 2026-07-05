<x-layouts.app title="Shop All Products — R&C Fashion">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[['label' => 'Home', 'href' => route('home')], ['label' => 'Products']]" />

            <h1 class="catalog-heading">Shop All Products</h1>

            <x-flash-status />

            @if ($featured->isNotEmpty())
                <section class="catalog-featured">
                    <h2 class="catalog-subheading">Featured</h2>
                    <x-product-grid :products="$featured" />
                </section>
            @endif

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
                    <h2 class="catalog-subheading">Latest Products</h2>
                    <x-product-grid :products="$products" />
                    <x-pagination :paginator="$products" />
                </div>
            </div>
        </div>
    </main>
</x-layouts.app>
