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
        // Delegates to SearchService for relevance-ranked FULLTEXT search.
        // The result['results'] paginator is returned directly so callers that
        // only need the paginator (e.g. API integrations) don't have to change.
        return app(SearchService::class)->search($term, $filters, $perPage)['results'];
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
     * deterministic "same brand_id" lookup, not a ranked blend. Still
     * gender/age_group-gated like every other cross-sell module — "same
     * brand" never overrides "not the same audience" (a Kids shoe must not
     * surface an adult item from the same brand). No broaden-tier here:
     * unlike similarProducts()/completeTheLook(), "same brand" is the
     * module's entire purpose, so a thin/zero result is the honest outcome
     * rather than something to broaden past.
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
            ->genderCompatible($product->gender)
            ->ageGroupCompatible($product->age_group)
            ->with(self::WITH)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * "Complete the Look" — outfit-complement heuristic keyed off
     * product_type (e.g. a jacket completes with shirts/trousers/watches,
     * not with anything else sharing its `style` string regardless of what
     * it actually is). Gender-gated via Product::scopeGenderCompatible() so
     * a men's product never completes with a women's-only item, and a Kids item
     * never completes with an adult item regardless of gender (a Unisex-
     * gender Kids shoe still only completes with other Kids-age_group items,
     * gated via the new Product::scopeAgeGroupCompatible()).
     *
     * Still a catalogue query, not a new recommendation algorithm; curated
     * `collections`/`collection_items` rows can layer on top of this later
     * without changing this method.
     *
     * Returns empty (not a degraded department-only match) when the anchor
     * has no product_type, no mapped complements, or no gender/age_group-
     * compatible candidate exists — showing nothing is preferable to a
     * mismatch. No broaden-tier needed here: COMPLEMENT_TYPES is already a
     * curated "broadened" set (e.g. jacket -> 7 different types), unlike
     * similarProducts() which starts from a single exact type.
     */
    /**
     * Each entry is either:
     *  - a flat list (`['a', 'b']`) — one complement set that genuinely fits
     *    every gender/age_group this anchor type appears as, reviewed and
     *    kept unified deliberately, not by default; OR
     *  - a bucketed list keyed by 'men_unisex' / 'women' / 'kids' — used
     *    wherever the genuine complement actually differs by audience.
     *    Resolved by resolveComplementTypes() based on the anchor's own
     *    gender/age_group, never the candidate's.
     *
     * Every entry below was reviewed against one question: would a real
     * person genuinely style these together, or is this just the
     * least-bad option from a limited type list? Where the honest answer
     * was the latter, the list was corrected or, if nothing in the
     * taxonomy genuinely fits, left empty for that bucket — a thin/empty
     * "Complete the Look" is preferable to a forced mismatch.
     *
     * Footwear (shoes/sneakers/heels/sandals/boots) is the type that
     * exposed this: every one of them sent ALL genders to belt+watch+
     * wallet, a menswear-coded set that never fit women's heels/sandals.
     * Women's footwear now styles with bag/earrings/sunglasses instead.
     * Kids footwear is left empty — no kids belt/watch/wallet/bag/jewelry/
     * sunglasses exist in this taxonomy, and a hat is not a footwear
     * complement, so there is nothing genuine to offer.
     *
     * Other bucketed corrections found by the same review:
     *  - jacket: the old single list forced 'blouse' (womenswear) into the
     *    same set as 'belt'/'trousers' (menswear). Split; women's jacket
     *    styles with blouse/jeans/skirt/watch/bag, men's/unisex keeps the
     *    original menswear set. Kids jacket (Girls, in the current
     *    catalogue) styles with dress/skirt/tee/shoes/sneakers/hat.
     *  - blazer: same men/women split as jacket. No kids bucket — a blazer
     *    isn't a genuine kids styling category in this catalogue (self-
     *    flagged: real-world kids formalwear exists, but nothing in this
     *    taxonomy would complete such a look, so left empty rather than
     *    guessing).
     *  - watch: 'belt'/'wallet' never fit a women's watch the same way;
     *    women's watch now styles with bag/sunglasses/earrings, matching
     *    the same accessory logic as women's footwear.
     *  - wallet: same flaw — 'belt' dropped for women, 'earrings' added.
     *  - shirt: the old list (trousers/blazer/watch/belt) is a genuine
     *    adult menswear look, but a Kids (Boys) shirt has no genuine use
     *    for a blazer/watch/belt in this catalogue. Kids bucket now styles
     *    with shorts/joggers/jacket/shoes/sneakers instead.
     *  - dress: adult (Women) list was already genuine — this is the
     *    pattern the footwear fix now matches. Kids (Girls) dress styles
     *    with jacket/skirt/shoes/hat; adult jewelry/heels/scarf dropped as
     *    not genuine for a kids anchor (self-flagged: whether kids
     *    jewelry belongs here is a judgment call with no data signal
     *    either way, so it was left out rather than guessed in).
     *  - skirt: adult (Women) list unchanged. Kids (Girls) skirt drops
     *    heels/sandals/blouse (none exist for kids and heels specifically
     *    aren't a genuine kids item) in favour of tee/jacket/shoes/sneakers.
     *  - sportswear_top / sportswear_bottom: the adult list is genuinely
     *    gender-neutral athletic wear (sneakers/bag/top↔bottom) and was
     *    kept unified across men_unisex/women. Kids sportswear_top styles
     *    with joggers/shorts/sneakers instead of 'sportswear_bottom',
     *    since kids athletic bottoms are classified as joggers/shorts in
     *    this taxonomy, not as a separate sportswear_bottom type.
     *  - hat: sunglasses/scarf were never gender-skewed, so the adult list
     *    stays unified. Kids bucket is left empty (self-flagged: 'hat'
     *    spans very different real items — beanie vs sun hat vs headband —
     *    so no single kids complement felt genuine enough to commit to
     *    rather than guess).
     *
     * Reviewed and kept flat/unchanged because no gender-skew flaw was
     * found (menswear-only or womenswear-only types where the single list
     * already fits every audience it can apply to, or already-neutral
     * accessory pairings): bag, earrings, sunglasses, scarf, necklace,
     * blouse, tee, jeans, joggers, romper, bracelet, shorts. `belt` and
     * `trousers` are Men-only in the current catalogue; if a women's
     * product of either type is ever added, they warrant the same
     * gender-lens review this pass gave to footwear/watch/wallet.
     * `waistcoat` stays Men-only and unbucketed — a waistcoat is a
     * menswear-specific garment in virtually any real fashion context, not
     * merely a data gap.
     */
    private const COMPLEMENT_TYPES = [
        'shoes' => [
            'men_unisex' => ['belt', 'watch', 'wallet'],
            'women'      => ['bag', 'earrings', 'sunglasses'],
            'kids'       => [],
        ],
        'sneakers' => [
            'men_unisex' => ['belt', 'watch', 'wallet'],
            'women'      => ['bag', 'earrings', 'sunglasses'],
            'kids'       => [],
        ],
        'heels' => [
            'men_unisex' => ['belt', 'watch', 'wallet'],
            'women'      => ['bag', 'earrings', 'sunglasses'],
            'kids'       => [],
        ],
        'sandals' => [
            'men_unisex' => ['belt', 'watch', 'wallet'],
            'women'      => ['bag', 'earrings', 'sunglasses'],
            'kids'       => [],
        ],
        'boots' => [
            'men_unisex' => ['belt', 'watch', 'wallet'],
            'women'      => ['bag', 'earrings', 'sunglasses'],
            'kids'       => [],
        ],
        'jacket' => [
            'men_unisex' => ['shirt', 'tee', 'trousers', 'jeans', 'watch', 'belt'],
            'women'      => ['blouse', 'tee', 'jeans', 'skirt', 'watch', 'bag'],
            'kids'       => ['dress', 'skirt', 'tee', 'shoes', 'sneakers', 'hat'],
        ],
        'blazer' => [
            'men_unisex' => ['shirt', 'tee', 'trousers', 'jeans', 'watch', 'belt'],
            'women'      => ['blouse', 'skirt', 'jeans', 'watch', 'bag'],
            'kids'       => [],
        ],
        'dress' => [
            'men_unisex' => [],
            'women'      => ['heels', 'sandals', 'earrings', 'necklace', 'bracelet', 'bag', 'sunglasses', 'scarf'],
            'kids'       => ['jacket', 'skirt', 'shoes', 'hat'],
        ],
        'sportswear_top' => [
            'men_unisex' => ['sportswear_bottom', 'sneakers', 'bag'],
            'women'      => ['sportswear_bottom', 'sneakers', 'bag'],
            'kids'       => ['joggers', 'shorts', 'sneakers'],
        ],
        'sportswear_bottom' => [
            'men_unisex' => ['sportswear_top', 'sneakers', 'bag'],
            'women'      => ['sportswear_top', 'sneakers', 'bag'],
            'kids'       => [],
        ],
        'watch' => [
            'men_unisex' => ['belt', 'wallet', 'sunglasses'],
            'women'      => ['bag', 'sunglasses', 'earrings'],
            'kids'       => [],
        ],
        'wallet' => [
            'men_unisex' => ['belt', 'watch', 'bag'],
            'women'      => ['bag', 'watch', 'earrings'],
            'kids'       => [],
        ],
        'shirt' => [
            'men_unisex' => ['trousers', 'jeans', 'blazer', 'jacket', 'watch', 'belt'],
            'women'      => [],
            'kids'       => ['shorts', 'joggers', 'jacket', 'shoes', 'sneakers'],
        ],
        'skirt' => [
            'men_unisex' => [],
            'women'      => ['blouse', 'tee', 'heels', 'sandals', 'jacket'],
            'kids'       => ['tee', 'jacket', 'shoes', 'sneakers'],
        ],
        'hat'               => ['sunglasses', 'scarf'],
        'sunglasses'        => ['hat', 'watch', 'bag'],
        'bag'               => ['watch', 'sunglasses', 'scarf'],
        'earrings'          => ['necklace', 'bracelet', 'dress', 'blouse'],
        'hoodie'            => ['joggers', 'jeans', 'sneakers', 'tee'],
        'scarf'             => ['hat', 'jacket', 'sunglasses'],
        'necklace'          => ['earrings', 'bracelet', 'dress', 'blouse'],
        'blouse'            => ['skirt', 'jeans', 'trousers', 'blazer', 'necklace', 'earrings'],
        'tee'               => ['jeans', 'joggers', 'jacket', 'sneakers'],
        'jeans'             => ['shirt', 'tee', 'blouse', 'jacket', 'belt', 'sneakers'],
        'joggers'           => ['hoodie', 'tee', 'sneakers'],
        'romper'            => ['hat', 'shoes'],
        'bracelet'          => ['necklace', 'earrings', 'watch'],
        'belt'              => ['trousers', 'jeans', 'wallet', 'shoes', 'watch'],
        'trousers'          => ['shirt', 'blazer', 'belt', 'shoes', 'watch'],
        'waistcoat'         => ['shirt', 'trousers', 'blazer', 'watch'],
        'shorts'            => ['tee', 'sneakers', 'hat'],
    ];

    /**
     * Resolves a COMPLEMENT_TYPES entry for the given anchor. A flat entry
     * (a plain list of product_type strings) applies unchanged to every
     * gender/age_group. A bucketed entry (keyed by 'men_unisex'/'women'/
     * 'kids') is resolved by the ANCHOR's own gender/age_group — never the
     * candidate's, which is a separate concern already handled by
     * genderCompatible()/ageGroupCompatible() on the query itself.
     */
    private function resolveComplementTypes(Product $product): array
    {
        $entry = self::COMPLEMENT_TYPES[$product->product_type] ?? [];

        if (empty($entry) || array_is_list($entry)) {
            return $entry;
        }

        $bucket = $product->age_group === 'Kids'
            ? 'kids'
            : ($product->gender === 'Women' ? 'women' : 'men_unisex');

        return $entry[$bucket] ?? [];
    }

    public function completeTheLook(Product $product, int $limit = 4): Collection
    {
        $complementTypes = $this->resolveComplementTypes($product);

        if (empty($complementTypes)) {
            return new Collection();
        }

        return Product::query()
            ->published()
            ->whereIn('product_type', $complementTypes)
            ->where('id', '!=', $product->id)
            ->genderCompatible($product->gender)
            ->ageGroupCompatible($product->age_group)
            ->with(self::WITH)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByRaw('style = ? DESC', [$product->style])
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
