<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Mail\VendorOrderNotificationMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\NewOrderVendorNotification;
use App\Notifications\OrderPlacedNotification;
use App\Repositories\OrderRepository;
use App\Services\AuditLogService;
use App\Services\Recommendation\InteractionTrackingService;
use App\Services\Recommendation\RecommendationCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        private readonly PaymentService $paymentService,
        private readonly AuditLogService $audit,
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

        $this->cache->bumpGlobalVersion();

        $this->paymentService->createForOrder($order);

        $this->audit->log('order_created', $order, [
            'order_number'   => $order->order_number,
            'payment_method' => $order->payment_method,
            'total'          => $order->total,
        ]);

        try {
            $order->loadMissing(['items', 'user']);
            $order->user->notify(new OrderPlacedNotification($order));
            Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
            Log::info('Order confirmation email sent', [
                'order_id' => $order->id,
                'mailable' => OrderConfirmationMail::class,
            ]);
            $this->sendVendorNotifications($order);
        } catch (\Throwable $e) {
            // notification/email failure must not break checkout
            Log::error('Order confirmation notification/email failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'mailable' => OrderConfirmationMail::class,
                'exception' => $e->getMessage(),
            ]);
        }

        return $order->load('items');
    }

    private function sendVendorNotifications(Order $order): void
    {
        $byVendor = $order->items->groupBy('vendor_id');

        foreach ($byVendor as $vendorId => $items) {
            $profile = VendorProfile::where('user_id', $vendorId)->first();

            if (! $profile) {
                continue;
            }

            // In-app notification to vendor user
            if ($profile->user) {
                $profile->user->notify(new NewOrderVendorNotification($order, $items));
            }

            if ($profile->email) {
                Mail::to($profile->email)->send(
                    new VendorOrderNotificationMail($order, $items, $profile->store_name ?? 'Your Store')
                );
            }
        }
    }
}
