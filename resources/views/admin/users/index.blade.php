<x-layouts.app title="Users — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="users" />

            <div class="admin-content">
                <h1 class="admin-heading">Users</h1>

                <x-flash-status />

                <form method="GET" action="{{ route('admin.users.index') }}" class="admin-filter-form">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or email" class="input-field-input">

                    <select name="role" class="input-field-input">
                        <option value="">All Roles</option>
                        <option value="buyer" @selected(($filters['role'] ?? '') === 'buyer')>Buyer</option>
                        <option value="vendor" @selected(($filters['role'] ?? '') === 'vendor')>Vendor</option>
                        <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                    </select>

                    <select name="status" class="input-field-input">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                        <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspended</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                @if ($users->isEmpty())
                    <x-empty-state title="No users found" message="Try adjusting your search or filters." />
                @else
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="product-table-actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ ucfirst($user->role) }}</td>
                                    <td><x-status-badge :status="$user->status" /></td>
                                    <td class="product-table-actions-col">
                                        <div class="product-table-actions">
                                            @if ($user->status === 'suspended')
                                                <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                                                    @csrf
                                                    <button type="submit" class="product-table-action">Activate</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.suspend', $user) }}" data-confirm="Suspend {{ $user->name }}?">
                                                    @csrf
                                                    <button type="submit" class="product-table-action product-table-action--danger">Suspend</button>
                                                </form>
                                            @endif

                                            @if ($user->role !== 'admin')
                                                <form method="POST" action="{{ route('admin.users.assign-admin', $user) }}" data-confirm="Make {{ $user->name }} an administrator?">
                                                    @csrf
                                                    <button type="submit" class="product-table-action">Make Admin</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <x-pagination :paginator="$users" />
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
