<x-layouts.app title="Products — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="products" />

            <div class="admin-content">
                <h1 class="admin-heading">Product Moderation</h1>

                <x-flash-status />

                <form method="GET" action="{{ route('admin.products.index') }}" class="admin-filter-form">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search product name" class="input-field-input">

                    <select name="status" class="input-field-input">
                        <option value="">All Statuses</option>
                        <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                        <option value="published" @selected(($filters['status'] ?? '') === 'published')>Published</option>
                        <option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Archived</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                @if ($products->isEmpty())
                    <x-empty-state title="No products found" message="Try adjusting your search or filters." />
                @else
                    <form method="POST" class="admin-bulk-form" data-bulk-form>
                        @csrf
                        <input type="text" name="reason" placeholder="Reason for bulk action" required class="input-field-input">
                        <button type="submit" formaction="{{ route('admin.products.bulk-approve') }}" class="btn btn-outline">Approve Selected</button>
                        <button type="submit" formaction="{{ route('admin.products.bulk-archive') }}" class="btn btn-outline">Archive Selected</button>
                        <button type="submit" formaction="{{ route('admin.products.bulk-delete') }}" class="btn btn-outline product-table-action--danger">Delete Selected</button>
                    </form>

                    <table class="product-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" data-bulk-select-all aria-label="Select all products"></th>
                                <th>Product</th>
                                <th>Vendor</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th class="product-table-actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td><input type="checkbox" value="{{ $product->id }}" data-bulk-checkbox aria-label="Select {{ $product->name }}"></td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->vendor->name }}</td>
                                    <td>{{ $product->category->name }}</td>
                                    <td><x-status-badge :status="$product->status" /></td>
                                    <td class="product-table-actions-col">
                                        <div class="admin-moderation-actions">
                                            @if ($product->status !== 'published')
                                                <form method="POST" action="{{ route('admin.products.approve', $product) }}" class="admin-moderation-form">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" required class="input-field-input">
                                                    <button type="submit" class="product-table-action">Approve</button>
                                                </form>
                                            @endif

                                            @if ($product->status === 'published')
                                                <form method="POST" action="{{ route('admin.products.reject', $product) }}" class="admin-moderation-form">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" required class="input-field-input">
                                                    <button type="submit" class="product-table-action product-table-action--danger">Reject</button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.products.hide', $product) }}" class="admin-moderation-form">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" required class="input-field-input">
                                                    <button type="submit" class="product-table-action">Hide</button>
                                                </form>
                                            @endif

                                            @if ($product->status === 'archived')
                                                <form method="POST" action="{{ route('admin.products.restore', $product) }}" class="admin-moderation-form">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" required class="input-field-input">
                                                    <button type="submit" class="product-table-action">Restore</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.products.archive', $product) }}" class="admin-moderation-form">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" required class="input-field-input">
                                                    <button type="submit" class="product-table-action product-table-action--danger">Archive</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <x-pagination :paginator="$products" />
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
