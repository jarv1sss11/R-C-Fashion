<x-layouts.app :title="$category->name . ' — R&C Fashion'">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[['label' => 'Home', 'href' => route('home')], ['label' => $category->name]]" />

            <h1 class="catalog-heading">{{ $category->name }}</h1>

            <x-flash-status />

            <div class="catalog-layout">
                <x-filter-sidebar :colors="$colors" :sizes="$sizes" :filters="$filters" />

                <div class="catalog-content">
                    <x-product-grid :products="$products" />
                    <x-pagination :paginator="$products" />
                </div>
            </div>
        </div>
    </main>
</x-layouts.app>
