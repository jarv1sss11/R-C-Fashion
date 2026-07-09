<?php

namespace App\Http\Controllers;

use App\Services\ProductCatalogueService;
use App\Services\Recommendation\InteractionTrackingService;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    private const FILTER_KEYS = [
        'category_id', 'gender', 'brand_id', 'age_group', 'color',
        'size', 'material', 'season', 'style', 'min_price', 'max_price',
        'availability', 'min_rating',
    ];

    public function __construct(
        private readonly ProductCatalogueService $catalogue,
        private readonly SearchService $search,
        private readonly InteractionTrackingService $tracking,
    ) {
    }

    public function index(Request $request): View
    {
        $term    = trim((string) $request->query('q', ''));
        $filters = $request->only(self::FILTER_KEYS);

        $products       = null;
        $didYouMean     = null;
        $fallbackNotice = null;

        if ($term !== '') {
            $this->tracking->recordSearch($request->user(), $term);

            $result         = $this->search->search($term, $filters);
            $products       = $result['results'];
            $didYouMean     = $result['did_you_mean'];
            $fallbackNotice = $result['fallback_notice'];
        }

        return view('catalog.search', [
            'term'           => $term,
            'products'       => $products,
            'didYouMean'     => $didYouMean,
            'fallbackNotice' => $fallbackNotice,
            'filters'        => $filters,
            ...$this->catalogue->filterOptions(),
        ]);
    }

    /**
     * JSON endpoint for navbar autocomplete.
     *
     * Returns up to 8 suggestions as [{text, type, url}] — never throws,
     * returns an empty array on any error so the frontend degrades gracefully.
     *
     * GET /search/suggestions?q=...
     */
    public function suggestions(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        try {
            return response()->json($this->search->suggestions($term));
        } catch (\Throwable) {
            return response()->json([]);
        }
    }
}
