@props(['products'])

<table class="product-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th class="product-table-actions-col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($products as $product)
            <tr>
                <td>
                    <div class="product-table-name-cell">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url }}" alt="" class="product-table-thumb">
                        @else
                            <span class="product-table-thumb product-table-thumb--empty"></span>
                        @endif
                        <span>{{ $product->name }}</span>
                    </div>
                </td>
                <td>{{ $product->category->name }}</td>
                <td>KES {{ number_format($product->price, 2) }}</td>
                <td>{{ $product->stock_quantity }}</td>
                <td><x-status-badge :status="$product->status" /></td>
                <td class="product-table-actions-col">
                    <div class="product-table-actions">
                        <a href="{{ route('vendor.products.edit', $product) }}" class="product-table-action">Edit</a>
                        <form method="POST" action="{{ route('vendor.products.destroy', $product) }}" data-confirm="Delete this product? This cannot be undone.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="product-table-action product-table-action--danger">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
