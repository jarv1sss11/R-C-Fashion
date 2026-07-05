<x-layouts.app title="Add Product — R&C Fashion">
    <x-navbar variant="full" />

    <main class="vendor">
        <div class="container vendor-inner">
            <x-vendor-sidebar active="products" />

            <div class="vendor-content">
                <h1 class="vendor-heading">Add a Product</h1>

                <x-flash-status />

                <form method="POST" action="{{ route('vendor.products.store') }}" enctype="multipart/form-data" class="auth-form vendor-form">
                    @csrf

                    <x-input-field label="Product Name" name="name" placeholder="e.g. Maasai Print Shirt" />

                    <x-select-field
                        label="Category"
                        name="category_id"
                        :options="$categories->pluck('name', 'id')"
                        placeholder="Select a category"
                    />

                    <x-textarea-field label="Description" name="description" placeholder="Describe this product" />

                    <x-input-field label="Price (KES)" type="number" name="price" placeholder="0.00" step="0.01" min="0" />

                    <x-input-field label="Stock Quantity" type="number" name="stock_quantity" placeholder="0" min="0" />

                    <x-input-field label="Primary Color" name="primary_color" placeholder="e.g. Black" />

                    <x-input-field label="Available Sizes" name="sizes" placeholder="e.g. S, M, L, XL" />

                    <x-select-field
                        label="Status"
                        name="status"
                        :value="'draft'"
                        :options="['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']"
                    />

                    <x-checkbox label="Feature this product on the catalogue homepage" name="is_featured" />

                    <x-file-field label="Product Images" name="images" :multiple="true" />

                    <x-button type="submit" variant="primary" class="btn-block">Create Product</x-button>
                </form>
            </div>
        </div>
    </main>
</x-layouts.app>
