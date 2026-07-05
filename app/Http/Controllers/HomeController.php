<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ProductCatalogueService;
use App\Services\Recommendation\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly RecommendationService $recommendations,
        private readonly ProductCatalogueService $catalogue,
    ) {
    }

    public function index(Request $request): View
    {
        $recommended = [];

        if ($request->user()) {
            $recommended = $this->recommendations->forYou($request->user(), 8);
            $this->recommendations->logShown($request->user(), $recommended, 'home');
        }

        return view('pages.home', [
            'recommended' => $recommended,
            'featuredCollections' => $this->catalogue->featured(8),
            'trending' => $this->recommendations->trending(8),
            'newArrivals' => $this->catalogue->newArrivals(8),
            'categories' => Category::whereNull('parent_id')->orderBy('display_order')->get(),
            'popular' => $this->catalogue->topRated(8),
        ]);
    }
}
