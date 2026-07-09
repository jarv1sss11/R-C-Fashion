<x-layouts.app title="Payment Management — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="payments" />

            <div class="admin-content">
                <h1 class="admin-heading">Payment Management</h1>

                <x-flash-status />

                <form method="GET" action="{{ route('admin.payments.index') }}" class="admin-filter-form">
                    <select name="method" class="admin-filter-select">
                        <option value="">All Methods</option>
                        <option value="mpesa" @selected(request('method') === 'mpesa')>M-Pesa</option>
                        <option value="cash_on_delivery" @selected(request('method') === 'cash_on_delivery')>Cash on Delivery</option>
                        <option value="mock_card" @selected(request('method') === 'mock_card')>Card</option>
                    </select>
                    <select name="status" class="admin-filter-select">
                        <option value="">All Statuses</option>
                        <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-ghost btn-sm">Clear</a>
                </form>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th>Paid At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                            <tr>
                                <td>
                                    <a href="{{ route('orders.show', $payment->order) }}" class="admin-link">
                                        {{ $payment->order->order_number }}
                                    </a>
                                </td>
                                <td>{{ $payment->order->user?->name ?? '—' }}</td>
                                <td>
                                    @if($payment->payment_method === 'mpesa')
                                        <span class="badge-pill badge-pill--info">M-Pesa</span>
                                    @elseif($payment->payment_method === 'cash_on_delivery')
                                        <span class="badge-pill badge-pill--warning">COD</span>
                                    @else
                                        <span class="badge-pill">Card</span>
                                    @endif
                                </td>
                                <td>KES {{ number_format($payment->amount, 2) }}</td>
                                <td><x-status-badge :status="$payment->status" /></td>
                                <td>{{ $payment->payment_reference ?? '—' }}</td>
                                <td>{{ $payment->paid_at?->format('d M Y, H:i') ?? '—' }}</td>
                                <td>
                                    @if($payment->payment_method === 'cash_on_delivery' && $payment->status === 'pending')
                                        <form method="POST" action="{{ route('admin.payments.confirm-cod', $payment) }}"
                                              onsubmit="return confirm('Confirm COD payment for {{ $payment->order->order_number }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-primary btn-sm">Confirm Payment</button>
                                        </form>
                                    @else
                                        <span class="text-muted-sm">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="admin-table-empty">No payments found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-pagination :paginator="$payments" />
            </div>
        </div>
    </main>

</x-layouts.app>
