<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->cart->getCart($request->user());

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        return view('pages.checkout', [
            'cart' => $cart,
            'totals' => $this->cart->totals($cart),
            'defaultAddress' => $request->user()->addresses()->where('is_default', true)->first(),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cart = $this->cart->getCart($request->user());

        $order = $this->checkout->placeOrder($request->user(), $cart, $request->validated());

        return redirect()->route('orders.show', $order)
            ->with('status', "Order {$order->order_number} placed successfully!");
    }
}
