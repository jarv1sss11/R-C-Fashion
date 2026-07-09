<?php

namespace App\Http\Controllers;

use App\Mail\OrderDeliveredMail;
use App\Models\Order;
use App\Models\VendorProfile;
use App\Notifications\OrderReceivedConfirmedNotification;
use App\Services\AuditLogService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly AuditLogService $audit,
    ) {
    }

    public function index(Request $request): View
    {
        return view('pages.orders.index', [
            'orders' => $this->orders->ordersForBuyer($request->user()),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('pages.orders.show', [
            'order' => $order->load(['items.product', 'payment']),
        ]);
    }

    public function receipt(Order $order): View
    {
        $this->authorize('view', $order);

        $order->loadMissing(['items', 'payment']);

        abort_if(! $order->payment || $order->payment->status !== 'paid', 404);

        return view('pages.orders.receipt', [
            'order'   => $order,
            'payment' => $order->payment,
        ]);
    }

    /**
     * Buyer-driven counterpart to the admin delivery-status dropdown — an
     * additional path to 'delivered', not a replacement. Admin can still set
     * delivery_status directly at any time regardless of buyer action.
     */
    public function confirmReceived(Order $order): RedirectResponse
    {
        $this->authorize('confirmReceived', $order);

        abort_if(
            $order->delivery_status !== 'out_for_delivery',
            422,
            'This order cannot be confirmed as received right now.'
        );

        $old = $order->only('delivery_status');

        $order->update(['delivery_status' => 'delivered']);
        $order->deliveryAssignment?->update(['status' => 'delivered']);

        $this->audit->log('delivery_confirmed_by_buyer', $order, [
            'delivery_status' => 'delivered',
        ], $old);

        try {
            $order->loadMissing(['user', 'items']);
            Mail::to($order->user->email)->send(new OrderDeliveredMail($order));
            Log::info('Order delivered confirmation email sent', [
                'order_id' => $order->id,
                'mailable' => OrderDeliveredMail::class,
            ]);
            $this->notifyVendors($order);
        } catch (\Throwable $e) {
            // notification/email failure must not break the confirmation action
            Log::error('Order delivered confirmation notification/email failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'mailable' => OrderDeliveredMail::class,
                'exception' => $e->getMessage(),
            ]);
        }

        return back()->with('status', 'Thanks for confirming — your order is marked as delivered.');
    }

    private function notifyVendors(Order $order): void
    {
        foreach ($order->items->pluck('vendor_id')->unique() as $vendorId) {
            $profile = VendorProfile::where('user_id', $vendorId)->first();

            if ($profile?->user) {
                $profile->user->notify(new OrderReceivedConfirmedNotification($order));
            }
        }
    }
}
