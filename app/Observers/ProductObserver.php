<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

/**
 * Keeps products.search_index in sync whenever a product is saved.
 *
 * Registered in AppServiceProvider::boot().
 *
 * Uses the `saving` hook (fires before INSERT and UPDATE) so the correct
 * search_index value is written in the same query as the rest of the
 * product data — zero extra round-trips.
 *
 * loadMissing(['brand', 'category']) is safe here: if the relations are
 * already loaded on the model instance (typical when a controller eager-
 * loaded them), no additional queries fire.
 */
class ProductObserver
{
    public function __construct(
        private readonly SearchService $search,
    ) {
    }

    public function saving(Product $product): void
    {
        $product->loadMissing(['brand', 'category']);
        $product->search_index = $this->search->buildSearchIndex($product);
    }
}
