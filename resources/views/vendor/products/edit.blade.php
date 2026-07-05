<x-layouts.app title="Edit Product — R&C Fashion">
    <x-navbar variant="full" />

    <main class="vendor">
        <div class="container vendor-inner">
            <x-vendor-sidebar active="products" />

            <div class="vendor-content">
                <h1 class="vendor-heading">Edit Product</h1>

                <x-flash-status />

                @if ($product->images->isNotEmpty())
                    <div class="vendor-product-images">
                        @foreach ($product->images as $image)
                            <div class="vendor-product-image">
                                <img src="{{ $image->url }}" alt="{{ $product->name }}">
                                <form method="POST" action="{{ route('vendor.products.images.destroy', [$product, $image]) }}" data-confirm="Remove this image?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="vendor-product-image-remove">Remove</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('vendor.products.update', $product) }}" enctype="multipart/form-data" class="auth-form vendor-form">
                    @csrf
                    @method('PUT')

                    <x-input-field label="Product Name" name="name" :value="$product->name" />

                    <x-select-field
                        label="Category"
                        name="category_id"
                        :options="$categories->pluck('name', 'id')"
                        :value="$product->category_id"
                    />

                    <x-textarea-field label="Description" name="description" :value="$product->description" />

                    <x-input-field label="Price (KES)" type="number" name="price" :value="$product->price" step="0.01" min="0" />

                    <x-input-field label="Stock Quantity" type="number" name="stock_quantity" :value="$product->stock_quantity" min="0" />

                    <x-input-field label="Primary Color" name="primary_color" :value="$product->primary_color" />

                    <x-input-field label="Available Sizes" name="sizes" :value="implode(', ', $product->sizes ?? [])" placeholder="e.g. S, M, L, XL" />

                    <x-select-field
                        label="Status"
                        name="status"
                        :value="$product->status"
                        :options="['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']"
                    />

                    <x-checkbox label="Feature this product on the catalogue homepage" name="is_featured" :checked="$product->is_featured" />

                    <x-file-field label="Add More Images" name="images" :multiple="true" />

                    <x-button type="submit" variant="primary" class="btn-block">Save Changes</x-button>
                </form>
            </div>
        </div>
    </main>
</x-layouts.app>
