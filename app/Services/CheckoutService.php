<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Services\Recommendation\InteractionTrackingService;
use App\Services\Recommendation\RecommendationCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orchestrates the one irreversible step in the shopping workflow: turning
 * a cart into a real order. Delegates instead of reimplementing — stock
 * re-validation to CartService, stock deduction to InventoryService, order
 * numbering to OrderService, and the post-purchase recommendation signal to
 * the existing InteractionTrackingService (no recommendation logic here).
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly OrderRepository $orderRepository,
        private readonly InventoryService $inventory,
        private readonly InteractionTrackingService $tracking,
        private readonly RecommendationCacheService $cache,
    ) {
    }

    /**
     * @param  array{shipping_name: string, shipping_line1: string, shipping_city: string, shipping_phone: string, delivery_option: string, payment_method: string}  $shippingData
     *
     * @throws ValidationException if the cart is empty or any item is out of stock
     */
    public function placeOrder(User $user, Cart $cart, array $shippingData): Order
    {
        $cart->loadMissing('items.product');

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        $stockErrors = $this->cartService->validateForCheckout($cart);

        if (! empty($stockErrors)) {
            throw ValidationException::withMessages(['cart' => $stockErrors]);
        }

        $order = DB::transaction(function () use ($user, $cart, $shippingData) {
            $subtotal = round((float) $cart->items->sum(fn (CartItem $item) => $item->lineTotal), 2);
            $deliveryCosts = config('shipping.delivery_costs');
            $shippingCost = $deliveryCosts[$shippingData['delivery_option']] ?? $deliveryCosts['standard'];
            $tax = 0.0; // no VAT/tax modeling in this phase — see DATABASE_BLUEPRINT.md
            $total = round($subtotal + $shippingCost + $tax, 2);

            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'order_number' => $this->orderService->generateOrderNumber(),
                'shipping_name' => $shippingData['shipping_name'],
                'shipping_line1' => $shippingData['shipping_line1'],
                'shipping_city' => $shippingData['shipping_city'],
                'shipping_phone' => $shippingData['shipping_phone'],
                'delivery_option' => $shippingData['delivery_option'],
                'payment_method' => $shippingData['payment_method'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'order_status' => 'processing',
                'payment_status' => $shippingData['payment_method'] === 'cash_on_delivery' ? 'pending' : 'paid',
                'delivery_status' => 'pending',
            ]);

            foreach ($cart->items as $item) {
                $this->inventory->deduct($item->product, $item->quantity);

                $this->orderRepository->addItem($order, [
                    'product_id' => $item->product->id,
                    'vendor_id' => $item->product->vendor_id,
                    'product_name' => $item->product->name,
                    'unit_price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'fulfillment_status' => 'pending',
                ]);

                // Purchase is the strongest positive recommendation signal —
                // feeds the existing engine unchanged, per the feature freeze.
                $this->tracking->recordPurchase($user, $item->product);
            }

            $this->cartService->clearCart($user);

            return $order;
        });

        // Stock just changed for every purchased product — invalidate every
        // user's cached recommendation output, same as a vendor stock edit.
        $this->cache->bumpGlobalVersion();

        return $order->load('items');
    }
}
