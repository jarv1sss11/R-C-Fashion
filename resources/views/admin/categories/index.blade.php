<x-layouts.app title="Categories — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="categories" />

            <div class="admin-content">
                <div class="admin-heading-row">
                    <h1 class="admin-heading">Categories</h1>
                    <x-button :href="route('admin.categories.create')" variant="primary">Add Category</x-button>
                </div>

                <x-flash-status />

                @if ($categories->isEmpty())
                    <x-empty-state
                        title="No categories yet"
                        message="Create your first category to organise the catalogue."
                    >
                        <x-button :href="route('admin.categories.create')" variant="outline">Add a Category</x-button>
                    </x-empty-state>
                @else
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Parent</th>
                                <th>Products</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th class="product-table-actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr class="{{ $category->trashed() ? 'admin-row--archived' : '' }}">
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->parent?->name ?? '—' }}</td>
                                    <td>{{ $category->products_count }}</td>
                                    <td>{{ $category->display_order }}</td>
                                    <td>{{ $category->trashed() ? 'Archived' : 'Active' }}</td>
                                    <td class="product-table-actions-col">
                                        <div class="product-table-actions">
                                            @if ($category->trashed())
                                                <form method="POST" action="{{ route('admin.categories.restore', $category->id) }}">
                                                    @csrf
                                                    <button type="submit" class="product-table-action">Restore</button>
                                                </form>
                                            @else
                                                <a href="{{ route('admin.categories.edit', $category) }}" class="product-table-action">Edit</a>
                                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Archive this category?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="product-table-action product-table-action--danger">Archive</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <x-pagination :paginator="$categories" />
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
