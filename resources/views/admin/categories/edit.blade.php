<x-layouts.app title="Edit Category — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="categories" />

            <div class="admin-content">
                <h1 class="admin-heading">Edit Category</h1>

                <x-flash-status />

                <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="auth-form admin-form">
                    @csrf
                    @method('PUT')

                    <x-input-field label="Category Name" name="name" :value="$category->name" />

                    <x-select-field
                        label="Parent Category"
                        name="parent_id"
                        :options="$parents->pluck('name', 'id')"
                        :value="$category->parent_id"
                        placeholder="None"
                    />

                    <x-input-field label="Display Order" type="number" name="display_order" :value="$category->display_order" min="0" />

                    <x-button type="submit" variant="primary" class="btn-block">Save Changes</x-button>
                </form>
            </div>
        </div>
    </main>
</x-layouts.app>
