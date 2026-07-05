<x-layouts.app title="Add Category — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="categories" />

            <div class="admin-content">
                <h1 class="admin-heading">Add a Category</h1>

                <x-flash-status />

                <form method="POST" action="{{ route('admin.categories.store') }}" class="auth-form admin-form">
                    @csrf

                    <x-input-field label="Category Name" name="name" placeholder="e.g. Menswear" />

                    <x-select-field
                        label="Parent Category"
                        name="parent_id"
                        :options="$parents->pluck('name', 'id')"
                        placeholder="None"
                    />

                    <x-input-field label="Display Order" type="number" name="display_order" placeholder="0" min="0" />

                    <x-button type="submit" variant="primary" class="btn-block">Create Category</x-button>
                </form>
            </div>
        </div>
    </main>
</x-layouts.app>
