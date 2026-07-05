<x-layouts.app title="Orders — R&C Fashion">
    <x-navbar variant="full" />

    <main class="vendor">
        <div class="container vendor-inner">
            <x-vendor-sidebar active="orders" />

            <div class="vendor-content">
                <h1 class="vendor-heading">Orders</h1>

                <x-flash-status />

                @if ($items->isEmpty())
                    <x-empty-state
                        title="No orders yet"
                        message="Orders containing your products will show up here."
                    />
                @else
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Fulfillment</th>
                                <th class="product-table-actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>{{ $item->order->order_number }}</td>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>KES {{ number_format($item->lineTotal, 2) }}</td>
                                    <td><x-status-badge :status="$item->fulfillment_status" /></td>
                                    <td class="product-table-actions-col">
                                        <a href="{{ route('vendor.orders.show', $item->order) }}" class="product-table-action">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <x-pagination :paginator="$items" />
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
