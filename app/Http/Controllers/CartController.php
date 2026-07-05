<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use App\Services\Recommendation\InteractionTrackingService;
use App\Services\Recommendation\RecommendationCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly InteractionTrackingService $tracking,
        private readonly RecommendationCacheService $cache,
    ) {
    }

    public function index(Request $request): View
    {
        $cart = $this->cart->getCart($request->user());

        return view('pages.cart', [
            'cart' => $cart,
            'totals' => $this->cart->totals($cart),
        ]);
    }

    public function store(AddToCartRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'published', 404);

        $this->cart->addToCart($request->user(), $product, (int) $request->input('quantity', 1));

        $this->tracking->recordCartAddition($request->user(), $product);
        $this->cache->forgetForUser($request->user()->id);

        return back()->with('status', "Added \"{$product->name}\" to your cart.");
    }

    public function update(UpdateCartRequest $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorize('update', $cartItem);

        $this->cart->updateQuantity($cartItem, (int) $request->validated('quantity'));

        $this->cache->forgetForUser($request->user()->id);

        return back()->with('status', 'Cart updated.');
    }

    public function increase(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorize('update', $cartItem);

        $this->cart->increaseQuantity($cartItem);

        $this->cache->forgetForUser($request->user()->id);

        return back()->with('status', 'Cart updated.');
    }

    public function decrease(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorize('update', $cartItem);

        $this->cart->decreaseQuantity($cartItem);

        $this->cache->forgetForUser($request->user()->id);

        return back()->with('status', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorize('delete', $cartItem);

        $productName = $cartItem->product->name;
        $this->cart->removeItem($cartItem);

        $this->tracking->recordCartRemoval($request->user(), $cartItem->product);
        $this->cache->forgetForUser($request->user()->id);

        return back()->with('status', "Removed \"{$productName}\" from your cart.");
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->cart->clearCart($request->user());

        $this->cache->forgetForUser($request->user()->id);

        return back()->with('status', 'Cart cleared.');
    }
}
