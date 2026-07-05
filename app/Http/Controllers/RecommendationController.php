<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Recommendation\RecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    private const COMPARABLE_ALGORITHMS = ['content', 'collaborative', 'popularity', 'hybrid'];

    public function __construct(private readonly RecommendationService $recommendations)
    {
    }

    public function index(Request $request): View
    {
        $algorithm = $request->query('algorithm', 'hybrid');

        if (! in_array($algorithm, self::COMPARABLE_ALGORITHMS, true)) {
            $algorithm = 'hybrid';
        }

        $results = $algorithm === 'hybrid'
            ? $this->recommendations->forYou($request->user(), 24)
            : $this->recommendations->algorithmOnly($request->user(), $algorithm, 24);

        $this->recommendations->logShown($request->user(), $results, 'recommendations_page');

        return view('pages.recommendations', [
            'results' => $results,
            'algorithm' => $algorithm,
        ]);
    }

    /**
     * Tracking redirect: every recommendation card links here instead of
     * straight to the product, so a click can be logged before the user
     * lands on the real page.
     */
    public function click(Request $request, Product $product): RedirectResponse
    {
        $module = (string) $request->query('module', 'recommendations_page');

        $this->recommendations->recordClick($request->user(), $product, $module);

        return redirect()->route('products.show', $product->slug);
    }
}
