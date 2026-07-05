<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Recommendation\InteractionTrackingService;
use App\Services\Recommendation\RecommendationCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(
        private readonly InteractionTrackingService $tracking,
        private readonly RecommendationCacheService $cache,
    ) {
    }

    public function index(Request $request): View
    {
        $wishlists = $request->user()->wishlists()
            ->with([
                'product.images',
                'product.vendor.vendorProfile',
                'product' => fn ($query) => $query->withAvg('reviews', 'rating')->withCount('reviews'),
            ])
            ->latest()
            ->get();

        return view('pages.account.wishlist', [
            'products' => $wishlists->pluck('product'),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'published', 404);

        $request->user()->wishlists()->firstOrCreate(['product_id' => $product->id]);

        $this->tracking->recordWishlist($request->user(), $product);
        $this->cache->forgetForUser($request->user()->id);

        return back()->with('status', "Added \"{$product->name}\" to your wishlist.");
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        $this->tracking->recordWishlistRemoval($request->user(), $product);
        $this->cache->forgetForUser($request->user()->id);

        return back()->with('status', "Removed \"{$product->name}\" from your wishlist.");
    }
}
