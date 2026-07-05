<?php

namespace App\Http\Controllers;

use App\Services\ProductCatalogueService;
use App\Services\Recommendation\InteractionTrackingService;
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
        private readonly InteractionTrackingService $tracking,
    ) {
    }

    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));
        $filters = $request->only(self::FILTER_KEYS);

        if ($term !== '') {
            $this->tracking->recordSearch($request->user(), $term);
        }

        return view('catalog.search', [
            'term' => $term,
            'products' => $term !== '' ? $this->catalogue->search($term, $filters) : null,
            'filters' => $filters,
            ...$this->catalogue->filterOptions(),
        ]);
    }
}
