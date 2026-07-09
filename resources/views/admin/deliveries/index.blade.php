<x-layouts.app title="Delivery Management — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="deliveries" />

            <div class="admin-content">
                <h1 class="admin-heading">Delivery Management</h1>
                <p class="admin-subheading">Orders awaiting rider assignment (payment confirmed).</p>

                <x-flash-status />

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>City</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th>Assign Rider</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}" class="admin-link">{{ $order->order_number }}</a>
                                </td>
                                <td>{{ $order->user?->name ?? $order->shipping_name }}</td>
                                <td>{{ $order->shipping_city }}</td>
                                <td><x-status-badge :status="$order->payment_status" /></td>
                                <td>KES {{ number_format($order->total, 2) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.deliveries.assign', $order) }}"
                                          class="delivery-assign-form">
                                        @csrf
                                        <select name="rider_id" class="admin-filter-select" required>
                                            <option value="">Select Rider…</option>
                                            @foreach($riders as $rider)
                                            <option value="{{ $rider->id }}">{{ $rider->name }} ({{ ucfirst($rider->vehicle_type) }})</option>
                                            @endforeach
                                        </select>
                                        <input type="datetime-local" name="estimated_delivery"
                                               class="admin-filter-input" placeholder="Est. Delivery">
                                        <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="admin-table-empty">No orders awaiting assignment.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-pagination :paginator="$orders" />

                {{-- Active deliveries --}}
                @php
                    $active = \App\Models\DeliveryAssignment::with(['order', 'rider'])
                        ->whereIn('status', ['assigned','picked_up'])
                        ->latest()
                        ->get();
                @endphp

                @if($active->isNotEmpty())
                <h2 class="admin-heading" style="margin-top:2rem;font-size:1.15rem;">Active Deliveries</h2>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Rider</th>
                                <th>Assigned</th>
                                <th>Est. Delivery</th>
                                <th>Status</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($active as $assignment)
                            <tr>
                                <td>
                                    <a href="{{ route('orders.show', $assignment->order) }}" class="admin-link">
                                        {{ $assignment->order->order_number }}
                                    </a>
                                </td>
                                <td>{{ $assignment->rider->name }} / {{ $assignment->rider->phone }}</td>
                                <td>{{ $assignment->assigned_at->format('d M, H:i') }}</td>
                                <td>{{ $assignment->estimated_delivery?->format('d M, H:i') ?? '—' }}</td>
                                <td><x-status-badge :status="$assignment->status" /></td>
                                <td>
                                    <form method="POST" action="{{ route('admin.deliveries.status', $assignment) }}">
                                        @csrf @method('PATCH')
                                        <select name="status" class="admin-filter-select" onchange="this.form.submit()">
                                            <option value="assigned" @selected($assignment->status === 'assigned')>Assigned</option>
                                            <option value="picked_up" @selected($assignment->status === 'picked_up')>Picked Up</option>
                                            <option value="delivered" @selected($assignment->status === 'delivered')>Delivered</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
