<x-layouts.app title="Vendors — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="vendors" />

            <div class="admin-content">
                <h1 class="admin-heading">Vendors</h1>

                <x-flash-status />

                <form method="GET" action="{{ route('admin.vendors.index') }}" class="admin-filter-form">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search store or owner" class="input-field-input">

                    <select name="approval_status" class="input-field-input">
                        <option value="">All Statuses</option>
                        <option value="pending" @selected(($filters['approval_status'] ?? '') === 'pending')>Pending</option>
                        <option value="approved" @selected(($filters['approval_status'] ?? '') === 'approved')>Approved</option>
                        <option value="rejected" @selected(($filters['approval_status'] ?? '') === 'rejected')>Rejected</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                @if ($vendors->isEmpty())
                    <x-empty-state title="No vendors found" message="Try adjusting your search or filters." />
                @else
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Store</th>
                                <th>Owner</th>
                                <th>Approval</th>
                                <th>Account</th>
                                <th class="product-table-actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vendors as $vendor)
                                <tr>
                                    <td><a href="{{ route('admin.vendors.show', $vendor) }}">{{ $vendor->store_name }}</a></td>
                                    <td>{{ $vendor->user->name }}<br>{{ $vendor->user->email }}</td>
                                    <td><x-status-badge :status="$vendor->approval_status" /></td>
                                    <td><x-status-badge :status="$vendor->user->status" /></td>
                                    <td class="product-table-actions-col">
                                        <div class="admin-moderation-actions">
                                            @if ($vendor->approval_status === 'pending')
                                                <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}" class="admin-moderation-form">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" required class="input-field-input">
                                                    <button type="submit" class="product-table-action">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.vendors.reject', $vendor) }}" class="admin-moderation-form">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" required class="input-field-input">
                                                    <button type="submit" class="product-table-action product-table-action--danger">Reject</button>
                                                </form>
                                            @endif

                                            @if ($vendor->user->status === 'suspended')
                                                <form method="POST" action="{{ route('admin.vendors.restore', $vendor) }}" class="admin-moderation-form">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" required class="input-field-input">
                                                    <button type="submit" class="product-table-action">Restore</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}" class="admin-moderation-form">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" required class="input-field-input">
                                                    <button type="submit" class="product-table-action product-table-action--danger">Suspend</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <x-pagination :paginator="$vendors" />
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
