<x-layouts.app :title="$order->order_number . ' — R&C Fashion'">
    <x-navbar variant="full" />

    <main class="vendor">
        <div class="container vendor-inner">
            <x-vendor-sidebar active="orders" />

            <div class="vendor-content">
                <div class="vendor-heading-row">
                    <h1 class="vendor-heading">{{ $order->order_number }}</h1>
                    <x-status-badge :status="$order->delivery_status" />
                </div>

                <x-flash-status />

                <p class="order-detail-meta">
                    Customer: {{ $order->shipping_name }} &middot; {{ $order->shipping_line1 }}, {{ $order->shipping_city }} &middot; {{ $order->shipping_phone }}
                </p>

                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Fulfillment</th>
                            <th class="product-table-actions-col">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>KES {{ number_format($item->unit_price, 2) }}</td>
                                <td>KES {{ number_format($item->lineTotal, 2) }}</td>
                                <td><x-status-badge :status="$item->fulfillment_status" /></td>
                                <td class="product-table-actions-col">
                                    <form method="POST" action="{{ route('vendor.orders.fulfillment', $item) }}" class="order-fulfillment-form">
                                        @csrf
                                        @method('PATCH')
                                        <select name="fulfillment_status" class="input-field-input" data-fulfillment-auto>
                                            <option value="pending" @selected($item->fulfillment_status === 'pending')>Pending</option>
                                            <option value="shipped" @selected($item->fulfillment_status === 'shipped')>Shipped</option>
                                            <option value="delivered" @selected($item->fulfillment_status === 'delivered')>Delivered</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</x-layouts.app>
