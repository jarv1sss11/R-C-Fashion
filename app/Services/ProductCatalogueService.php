<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Central query builder for the customer-facing catalogue (Step 8).
 *
 * Deliberately kept free of any personalization/ranking logic — this is
 * plain filtering and sorting only. Step 9's RecommendationService is meant
 * to sit alongside (not inside) this class; it can reuse the base
 * `published()` scope/eager-loading here without this service knowing
 * anything about recommendations.
 */
class ProductCatalogueService
{
    /**
     * Common set of eager-loaded relations for any product listing/detail view.
     */
    private const WITH = ['category', 'brand', 'vendor.vendorProfile', 'images'];

    public function query(array $filters = []): Builder
    {
        $query = Product::query()
            ->published()
            ->with(self::WITH)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (! empty($filters['age_group'])) {
            $query->where('age_group', $filters['age_group']);
        }

        if (! empty($filters['material'])) {
            $query->where('material', $filters['material']);
        }

        if (! empty($filters['season'])) {
            $query->where('season', $filters['season']);
        }

        if (! empty($filters['style'])) {
            $query->where('style', $filters['style']);
        }

        if (! empty($filters['color'])) {
            $query->where('primary_color', $filters['color']);
        }

        if (! empty($filters['size'])) {
            $query->whereJsonContains('sizes', $filters['size']);
        }

        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (! empty($filters['availability']) && $filters['availability'] === 'in_stock') {
            $query->where('stock_quantity', '>', 0);
        }

        if (! empty($filters['min_rating'])) {
            $query->having('reviews_avg_rating', '>=', $filters['min_rating']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(
                fn (Builder $q) => $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
            );
        }

        return $query->latest();
    }

    public function latest(int $perPage = 12, array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    public function featured(int $limit = 8): Collection
    {
        return Product::query()
            ->published()
            ->where('is_featured', true)
            ->with(self::WITH)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->take($limit)
            ->get();
    }

    public function newArrivals(int $limit = 8): Collection
    {
        return Product::query()
            ->published()
            ->with(self::WITH)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * "Popular Products" for the homepage — ranked by rating, a distinct
     * slice from the recommendation engine's "Trending" (activity-window
     * based) so the two homepage sections don't show the same list twice.
     */
    public function topRated(int $limit = 8): Collection
    {
        return Product::query()
            ->published()
            ->with(self::WITH)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->having('reviews_count', '>', 0)
            ->orderByDesc('reviews_avg_rating')
            ->take($limit)
            ->get();
    }

    public function forCategory(Category $category, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->query($filters)
            ->where('category_id', $category->id)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function search(string $term, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->query([...$filters, 'search' => $term])
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findBySlug(string $slug): Product
    {
        return Product::published()
            ->with([...self::WITH, 'reviews'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function forVendor(int $vendorUserId, int $perPage = 12): LengthAwarePaginator
    {
        return $this->query()
            ->where('vendor_id', $vendorUserId)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Fetches published products by ID, preserving the given order — used
     * for "Recently Viewed"/"Customers Also Viewed", whose ordering (most
     * recent, most co-viewed) is decided by the repository query that
     * produced the ID list, not by this method.
     */
    public function byIds(array $ids): Collection
    {
        if (empty($ids)) {
            return new Collection();
        }

        $products = Product::query()
            ->published()
            ->whereIn('id', $ids)
            ->with(self::WITH)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get()
            ->keyBy('id');

        return new Collection(
            collect($ids)->map(fn ($id) => $products->get($id))->filter()->values()->all()
        );
    }

    /**
     * "More From This Brand" — a plain filter, not a scored recommendation;
     * deliberately outside the recommendation engine since it's a
     * deterministic "same brand_id" lookup, not a ranked blend.
     */
    public function moreFromBrand(Product $product, int $limit = 6): Collection
    {
        if (! $product->brand_id) {
            return new Collection();
        }

        return Product::query()
            ->published()
            ->where('brand_id', $product->brand_id)
            ->where('id', '!=', $product->id)
            ->with(self::WITH)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * "Complete the Look" heuristic — same style, a *different* category,
     * ranked by rating/featured status. A catalogue query, not a new
     * recommendation algorithm; curated `collections`/`collection_items`
     * rows can layer on top of this later without changing this method.
     */
    public function completeTheLook(Product $product, int $limit = 4): Collection
    {
        if (! $product->style) {
            return new Collection();
        }

        return Product::query()
            ->published()
            ->where('style', $product->style)
            ->where('category_id', '!=', $product->category_id)
            ->with(self::WITH)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('is_featured')
            ->orderByDesc('reviews_avg_rating')
            ->take($limit)
            ->get();
    }

    /**
     * Options for the filter sidebar. Sizes/genders/age-groups are small
     * fixed taxonomies rather than derived from data — brand/material/
     * season/style are database-driven since vendors/seeders introduce new
     * values organically over time.
     */
    public function filterOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'colors' => Product::published()
                ->whereNotNull('primary_color')
                ->distinct()
                ->orderBy('primary_color')
                ->pluck('primary_color'),
            'materials' => Product::published()
                ->whereNotNull('material')
                ->distinct()
                ->orderBy('material')
                ->pluck('material'),
            'seasons' => Product::published()
                ->whereNotNull('season')
                ->distinct()
                ->orderBy('season')
                ->pluck('season'),
            'styles' => Product::published()
                ->whereNotNull('style')
                ->distinct()
                ->orderBy('style')
                ->pluck('style'),
            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'ageGroups' => ['Adult', 'Teen', 'Kids', 'Senior'],
        ];
    }
}
