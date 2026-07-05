<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): View
    {
        return view('vendor.orders.index', [
            'items' => $this->orders->orderItemsForVendor($request->user()),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        return view('vendor.orders.show', [
            'order' => $order,
            'items' => $this->orders->vendorItemsInOrder($order, $request->user()),
        ]);
    }

    public function updateFulfillment(Request $request, OrderItem $orderItem): RedirectResponse
    {
        $this->authorize('updateFulfillment', $orderItem);

        $validated = $request->validate([
            'fulfillment_status' => ['required', 'string', 'in:pending,shipped,delivered'],
        ]);

        $this->orders->updateFulfillmentStatus($orderItem, $validated['fulfillment_status']);

        return back()->with('status', 'Fulfillment status updated.');
    }
}
