<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
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
            'order' => $order->load('items.product'),
        ]);
    }
}
