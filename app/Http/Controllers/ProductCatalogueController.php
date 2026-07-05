<?php

namespace App\Http\Controllers;

use App\Repositories\RecommendationRepository;
use App\Services\ProductCatalogueService;
use App\Services\Recommendation\InteractionTrackingService;
use App\Services\Recommendation\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCatalogueController extends Controller
{
    private const FILTER_KEYS = [
        'category_id', 'gender', 'brand_id', 'age_group', 'color',
        'size', 'material', 'season', 'style', 'min_price', 'max_price',
        'availability', 'min_rating',
    ];

    public function __construct(
        private readonly ProductCatalogueService $catalogue,
        private readonly InteractionTrackingService $tracking,
        private readonly RecommendationService $recommendations,
        private readonly RecommendationRepository $recommendationRepository,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(self::FILTER_KEYS);

        return view('catalog.index', [
            'featured' => $this->catalogue->featured(),
            'products' => $this->catalogue->latest(12, $filters),
            'filters' => $filters,
            ...$this->catalogue->filterOptions(),
        ]);
    }

    public function show(string $product, Request $request): View
    {
        $product = $this->catalogue->findBySlug($product);
        $user = $request->user();

        $this->tracking->recordView($user, $product);

        $similar = $this->recommendations->similarProducts($product, 6);
        $this->recommendations->logShown($user, $similar, 'product_detail');

        $recommendedForYou = $user ? $this->recommendations->forYou($user, 6) : [];

        $recentlyViewedIds = $user
            ? $this->recommendationRepository->recentlyViewedProductIds($user->id, 8, $product->id)
            : [];
        $coViewedIds = $this->recommendationRepository->coViewedProductIds($product->id, 6);

        return view('catalog.show', [
            'product' => $product,
            'isWishlisted' => $user->wishlists()->where('product_id', $product->id)->exists(),
            'similar' => $similar,
            'recommendedForYou' => $recommendedForYou,
            'recentlyViewed' => $this->catalogue->byIds($recentlyViewedIds),
            'customersAlsoViewed' => $this->catalogue->byIds($coViewedIds),
            'moreFromBrand' => $this->catalogue->moreFromBrand($product, 6),
            'completeTheLook' => $this->catalogue->completeTheLook($product, 4),
        ]);
    }
}
