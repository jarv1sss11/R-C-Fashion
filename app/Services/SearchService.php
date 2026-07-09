<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Intelligent full-text search over the product catalogue.
 *
 * Architecture overview
 * ─────────────────────
 * The single source of truth for searchable content is the products.search_index
 * column — a maintained plain-TEXT field whose content is rebuilt by
 * ProductObserver on every product save, and by the `search:rebuild-index`
 * artisan command for bulk backfill.  It contains:
 *
 *     {name} × 2  {brand_name}  {category_name}  {style}  {color}
 *     {material}  {season}  {age_group}  {gender}  {tags…}  {description}
 *
 * Repeating the product name gives it a higher effective TF-IDF weight in
 * FULLTEXT scoring without any query-time trickery.
 *
 * Search strategy (three-tier relevance fallback)
 * ─────────────────────────────────────────────────
 * 1. STRICT — every significant token required (+word1* +word2* …).
 *    Highest precision; returns items that contain every search term.
 * 2. MAJORITY MATCH — 3+ token queries only. If strict returns nothing,
 *    require a strict majority (more than half) of tokens to match on the
 *    same row. This is a genuine relevance floor, not a plain OR: a query
 *    like "breezy summer dres" no longer surfaces every product that merely
 *    contains "dres*" — it requires at least 2 of the 3 tokens on one row.
 *    Skipped for 2-token queries: majority-of-2 (more than half of 2 = both)
 *    is identical to strict, which has already failed by this point.
 * 3. BEST-SINGLE-TOKEN FALLBACK — reached only when tiers 1 and 2 both
 *    return nothing. Each significant token is measured independently for
 *    catalogue support (how many products contain it); the RAREST token
 *    with nonzero support — not the most common one — wins, and its results
 *    are returned under an explicit "isn't available, but you may also
 *    like" banner. Rarer terms are more specific to what the user actually
 *    typed ("hoodie") than common descriptors that happen to match more
 *    rows simply by being generic ("red"). This is the mechanism behind
 *    both the "red hoodie" honest-fallback case and the "Chrome Hearts
 *    jacket" no-such-brand case — a single shared code path, not two
 *    special cases.
 * 4. If every token has zero catalogue support, there is nothing left to
 *    show — return empty results plus a Levenshtein-based spelling
 *    suggestion (didYouMean()).
 *
 * WHY Boolean mode instead of Natural Language mode
 * ──────────────────────────────────────────────────
 * Natural Language mode silently ignores words that appear in >50% of rows.
 * At ~154 products a category word like "Men" or "Sports" could already
 * exceed that threshold.  Boolean mode has no such suppression and supports
 * prefix wildcards (term*) needed for autocomplete.
 *
 * WHY Levenshtein in PHP instead of MySQL SOUNDEX / DIFFERENCE
 * ─────────────────────────────────────────────────────────────
 * SOUNDEX is phonetic-only (same sound ≠ same prefix) and produces many
 * false positives for short product keywords.  PHP Levenshtein gives
 * character-level edit distance with a proportional tolerance threshold
 * (≤ 33% of the word's length), which handles swaps, insertions, and
 * deletions uniformly.  At ≤ 500 catalogue words the per-request corpus
 * scan is under 1 ms.
 */
class SearchService
{
    public function __construct(
        private readonly ProductCatalogueService $catalogue,
    ) {
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Main search entry point.
     *
     * Returns an array with three keys:
     *   'results'         LengthAwarePaginator  — the paginated product results
     *   'did_you_mean'    ?string               — corrected query string, or null
     *   'fallback_notice' ?string               — set only when tier 3 fired;
     *                                              UI must render this as an
     *                                              explicit "not a real match"
     *                                              banner above the results.
     */
    public function search(string $term, array $filters = [], int $perPage = 12): array
    {
        $clean = $this->sanitize($term);

        if (strlen($clean) < 2) {
            return [
                'results'         => $this->catalogue->query($filters)->paginate($perPage)->withQueryString(),
                'did_you_mean'    => null,
                'fallback_notice' => null,
            ];
        }

        $tokens = $this->tokenize($clean);

        // No token reached the 3-char minimum — fall back to a single prefix
        // on the whole sanitized string rather than tier logic that assumes
        // at least one real token exists.
        if (empty($tokens)) {
            $results = $this->runFulltextSearch(mb_strtolower(trim($clean)) . '*', $filters, $perPage);

            return $results->total() > 0
                ? ['results' => $results, 'did_you_mean' => null, 'fallback_notice' => null]
                : ['results' => $results, 'did_you_mean' => $this->didYouMean($clean), 'fallback_notice' => null];
        }

        // Tier 1 — strict: every significant token required
        $strict  = $this->toBooleanTerm($tokens, strict: true);
        $results = $this->runFulltextSearch($strict, $filters, $perPage);

        if ($results->total() > 0) {
            return ['results' => $results, 'did_you_mean' => null, 'fallback_notice' => null];
        }

        // Tier 2 — majority match, 3+ tokens only
        if (count($tokens) >= 3) {
            $threshold = intdiv(count($tokens), 2) + 1; // strictly more than half
            $results   = $this->runMajorityMatchSearch($tokens, $threshold, $filters, $perPage);

            if ($results->total() > 0) {
                return ['results' => $results, 'did_you_mean' => null, 'fallback_notice' => null];
            }
        }

        // Tier 3 — best-single-token fallback, explicitly labelled so it is
        // never mistaken for a genuine match to the full query.
        $bestToken = $this->findBestSupportedToken($tokens, $filters);

        if ($bestToken !== null) {
            $results = $this->runFulltextSearch($this->toBooleanTerm([$bestToken], strict: false), $filters, $perPage);

            if ($results->total() > 0) {
                return [
                    'results'         => $results,
                    'did_you_mean'    => null,
                    'fallback_notice' => "\"{$term}\" isn't available, but you may also like:",
                ];
            }
        }

        // Nothing at all — not even a single token has catalogue support.
        // $results here is already an empty paginator from tier 1 or tier 2.
        return [
            'results'         => $results,
            'did_you_mean'    => $this->didYouMean($clean),
            'fallback_notice' => null,
        ];
    }

    /**
     * Lightweight suggestions endpoint for autocomplete.
     *
     * Returns up to $limit items, each an array with keys:
     *   text  string  — display label
     *   type  string  — 'product' | 'brand' | 'category'
     *   url   string  — destination URL
     *
     * Uses prefix wildcard Boolean mode so partial inputs work
     * (e.g. "bla" → "Black Leather Jacket …"). Deliberately stays a plain
     * loose (any-token) match rather than the full tier system above —
     * autocomplete is meant to be forgiving as the user is still typing.
     */
    public function suggestions(string $term, int $limit = 8): array
    {
        $clean = $this->sanitize($term);

        if (strlen($clean) < 2) {
            return [];
        }

        $tokens = $this->tokenize($clean);
        $booleanTerm = empty($tokens)
            ? mb_strtolower(trim($clean)) . '*'
            : $this->toBooleanTerm($tokens, strict: false);

        // Product suggestions
        $products = Product::query()
            ->published()
            ->whereRaw('MATCH(search_index) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm])
            ->orderByRaw('MATCH(search_index) AGAINST(? IN BOOLEAN MODE) DESC', [$booleanTerm])
            ->limit($limit)
            ->get(['id', 'name', 'slug']);

        $items = $products->map(fn (Product $p) => [
            'text' => $p->name,
            'type' => 'product',
            'url'  => route('products.show', $p->slug),
        ])->all();

        // Brand name suggestions (prefix match, case-insensitive)
        if (count($items) < $limit) {
            $brands = Brand::query()
                ->where('name', 'like', "%{$clean}%")
                ->limit(2)
                ->get(['name']);

            foreach ($brands as $brand) {
                $items[] = [
                    'text' => $brand->name,
                    'type' => 'brand',
                    'url'  => route('search.index') . '?q=' . urlencode($brand->name),
                ];
            }
        }

        // Category name suggestions (prefix match)
        if (count($items) < $limit) {
            $cats = Category::query()
                ->where('name', 'like', "%{$clean}%")
                ->whereNull('deleted_at')
                ->limit(2)
                ->get(['name', 'slug']);

            foreach ($cats as $cat) {
                $items[] = [
                    'text' => $cat->name,
                    'type' => 'category',
                    'url'  => route('categories.show', $cat->slug),
                ];
            }
        }

        return array_slice($items, 0, $limit);
    }

    /**
     * Build the search_index text for a product.
     * Called by ProductObserver and RebuildSearchIndex; lives here so the
     * format is defined in exactly one place.
     */
    public function buildSearchIndex(Product $product): string
    {
        $brandName    = $product->brand?->name ?? '';
        $categoryName = $product->category?->name ?? '';
        $tags         = implode(' ', $product->tags ?? []);

        $parts = [
            $product->name,           // repeated to boost TF-IDF weight
            $product->name,
            $brandName,
            $categoryName,
            $product->style ?? '',
            $product->primary_color ?? '',
            $product->material ?? '',
            $product->season ?? '',
            $product->age_group ?? '',
            $product->gender ?? '',
            $tags,
            $product->description ?? '',
        ];

        return implode(' ', array_filter($parts, fn ($s) => $s !== ''));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function runFulltextSearch(string $booleanTerm, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->catalogue->query($filters)
            ->whereRaw('MATCH(products.search_index) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm])
            ->reorder()
            ->orderByRaw('MATCH(products.search_index) AGAINST(? IN BOOLEAN MODE) DESC', [$booleanTerm])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Tier 2 — requires at least $threshold of $tokens to match on the same
     * row (a genuine relevance floor), not merely "any one of them".
     *
     * Built as one query: a per-token boolean existence check
     * (MATCH(...) AGAINST('word*') > 0, coerced to 1/0 by MySQL) summed into
     * a `match_count` column, filtered via HAVING, and ranked first by how
     * many of the required tokens matched, then by the overall loose
     * relevance score as a tiebreaker.
     */
    private function runMajorityMatchSearch(array $tokens, int $threshold, array $filters, int $perPage): LengthAwarePaginator
    {
        $loose = $this->toBooleanTerm($tokens, strict: false);

        $matchCountSql = implode(' + ', array_fill(
            0,
            count($tokens),
            '(MATCH(products.search_index) AGAINST(? IN BOOLEAN MODE) > 0)'
        ));
        $tokenBindings = array_map(fn ($t) => "{$t}*", $tokens);

        return $this->catalogue->query($filters)
            ->selectRaw("({$matchCountSql}) as match_count", $tokenBindings)
            ->whereRaw('MATCH(products.search_index) AGAINST(? IN BOOLEAN MODE)', [$loose])
            ->havingRaw('match_count >= ?', [$threshold])
            ->reorder()
            ->orderByRaw('match_count DESC')
            ->orderByRaw('MATCH(products.search_index) AGAINST(? IN BOOLEAN MODE) DESC', [$loose])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Tier 3 — measures each token's catalogue support independently (a
     * plain product count, respecting the same filters as every other
     * tier) and returns whichever NONZERO-support token is rarest. A rare
     * term is more likely what the user actually meant ("hoodie") than a
     * common descriptor that happens to match many rows simply by being
     * generic ("red"). Returns null only when every token has zero
     * support, signalling total failure.
     */
    private function findBestSupportedToken(array $tokens, array $filters): ?string
    {
        $best      = null;
        $bestCount = PHP_INT_MAX;

        foreach ($tokens as $token) {
            $count = $this->catalogue->query($filters)
                ->whereRaw('MATCH(products.search_index) AGAINST(? IN BOOLEAN MODE)', ["{$token}*"])
                ->count();

            if ($count > 0 && $count < $bestCount) {
                $best      = $token;
                $bestCount = $count;
            }
        }

        return $best;
    }

    /**
     * Extracts significant (≥3 char) lowercase tokens from a cleaned query
     * string. Words shorter than innodb_ft_min_token_size (3) are dropped
     * because MySQL would ignore them anyway.
     */
    private function tokenize(string $clean): array
    {
        $words = preg_split('/\s+/', mb_strtolower(trim($clean)), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, fn ($w) => mb_strlen($w) >= 3));
    }

    /**
     * Converts an already-tokenized word list into a MySQL FULLTEXT Boolean
     * Mode expression using prefix wildcards.
     *
     * strict=true  →  "+word1* +word2*"  (all words must appear)
     * strict=false →  "word1* word2*"    (any word sufficient)
     */
    private function toBooleanTerm(array $tokens, bool $strict = true): string
    {
        if (empty($tokens)) {
            return '';
        }

        $prefix = $strict ? '+' : '';

        return implode(' ', array_map(fn ($w) => "{$prefix}{$w}*", $tokens));
    }

    /**
     * Strip MySQL FULLTEXT Boolean Mode operators from user input so they
     * cannot inject search logic or cause a syntax error.
     */
    private function sanitize(string $term): string
    {
        // Remove all FULLTEXT operator characters; collapse whitespace
        $stripped = preg_replace('/[+\-<>()"~*@]+/', ' ', $term);

        return trim(preg_replace('/\s{2,}/', ' ', $stripped) ?? '');
    }

    /**
     * Levenshtein-based "Did you mean?" for zero-result queries.
     *
     * Tokenizes the query into words, attempts to find a close match for each
     * word in a corpus built from product names, brand names, and category
     * names.  Returns the corrected phrase if at least one word was improved,
     * or null if every word already matches something (or nothing is close
     * enough to be a confident suggestion).
     *
     * Tolerance: edit distance ≤ max(1, floor(wordLength × 0.33)).
     * This permits 1 correction for a 3-char word, 2 for 6-7 chars, etc.
     */
    private function didYouMean(string $clean): ?string
    {
        $corpus = $this->buildWordCorpus();

        if (empty($corpus)) {
            return null;
        }

        $inputWords  = preg_split('/\s+/', mb_strtolower(trim($clean)), -1, PREG_SPLIT_NO_EMPTY);
        $corrected   = [];
        $hadFix      = false;

        foreach ($inputWords as $word) {
            if (mb_strlen($word) < 3) {
                $corrected[] = $word;
                continue;
            }

            $threshold = max(1, (int) floor(mb_strlen($word) * 0.33));
            $best      = null;
            $bestDist  = PHP_INT_MAX;

            foreach ($corpus as $candidate) {
                $dist = levenshtein($word, $candidate);

                if ($dist > 0 && $dist < $bestDist && $dist <= $threshold) {
                    $best     = $candidate;
                    $bestDist = $dist;
                }
            }

            if ($best !== null) {
                $corrected[] = $best;
                $hadFix      = true;
            } else {
                $corrected[] = $word;
            }
        }

        if (! $hadFix) {
            return null;
        }

        $suggestion = implode(' ', $corrected);

        // Only return the suggestion if it would actually produce results
        $loose = $this->toBooleanTerm($this->tokenize($suggestion), strict: false);
        $count = Product::query()
            ->published()
            ->whereRaw('MATCH(search_index) AGAINST(? IN BOOLEAN MODE)', [$loose])
            ->count();

        return $count > 0 ? $suggestion : null;
    }

    /**
     * Builds a de-duplicated list of lowercase words drawn from product names,
     * brand names, and category names — the "known vocabulary" of the catalogue.
     * Used exclusively by didYouMean(); not called on the hot path.
     */
    private function buildWordCorpus(): array
    {
        $texts = collect()
            ->merge(Product::published()->pluck('name'))
            ->merge(Brand::pluck('name'))
            ->merge(Category::whereNull('deleted_at')->pluck('name'));

        $words = [];

        foreach ($texts as $text) {
            foreach (preg_split('/[\s\-_\/]+/', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) as $word) {
                $w = preg_replace('/[^a-z0-9]/', '', $word);
                if (mb_strlen($w) >= 3) {
                    $words[$w] = true;
                }
            }
        }

        return array_keys($words);
    }
}
