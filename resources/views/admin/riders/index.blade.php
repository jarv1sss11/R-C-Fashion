<x-layouts.app title="Rider Management — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="riders" />

            <div class="admin-content">
                <div class="admin-heading-row">
                    <h1 class="admin-heading">Riders</h1>
                    <a href="{{ route('admin.riders.create') }}" class="btn btn-primary btn-sm">Add Rider</a>
                </div>

                <x-flash-status />

                <form method="GET" action="{{ route('admin.riders.index') }}" class="admin-filter-form">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email…" class="admin-filter-input" />
                    <select name="status" class="admin-filter-select">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="{{ route('admin.riders.index') }}" class="btn btn-ghost btn-sm">Clear</a>
                </form>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Vehicle</th>
                                <th>Plate</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riders as $rider)
                            <tr>
                                <td>{{ $rider->name }}</td>
                                <td>{{ $rider->email }}</td>
                                <td>{{ $rider->phone }}</td>
                                <td>{{ ucfirst($rider->vehicle_type) }}</td>
                                <td>{{ $rider->number_plate ?? '—' }}</td>
                                <td>
                                    @if($rider->available)
                                        <span class="status-badge status-badge--success">Yes</span>
                                    @else
                                        <span class="status-badge status-badge--neutral">No</span>
                                    @endif
                                </td>
                                <td><x-status-badge :status="$rider->status" /></td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.riders.edit', $rider) }}" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('admin.riders.destroy', $rider) }}"
                                          onsubmit="return confirm('Delete rider {{ $rider->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="admin-table-empty">No riders found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-pagination :paginator="$riders" />
            </div>
        </div>
    </main>
</x-layouts.app>
