<x-layouts.app :title="$product->name . ' — R&C Fashion'">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner">
            <x-breadcrumb :items="[
                ['label' => 'Home', 'href' => route('home')],
                ['label' => $product->category->name, 'href' => route('categories.show', $product->category->slug)],
                ['label' => $product->name],
            ]" />

            <x-flash-status />

            <div class="product-detail">
                <x-product-gallery :product="$product" />

                <div class="product-detail-info">
                    <h1 class="product-detail-name">{{ $product->name }}</h1>

                    <x-rating-stars :rating="$product->reviews_avg_rating" :count="$product->reviews_count ?? 0" />

                    <x-price-badge :price="$product->price" :currency="$product->currency" class="product-detail-price" />

                    @if ($product->brand)
                        <p class="product-detail-brand">
                            Brand:
                            <span class="product-detail-brand-name">{{ $product->brand->name }}</span>
                        </p>
                    @endif

                    <p class="product-detail-vendor">
                        Sold by:
                        <a href="{{ route('vendors.show', $product->vendor->vendorProfile->store_slug) }}">
                            {{ $product->vendor->vendorProfile->store_name }}
                        </a>
                    </p>

                    @if ($product->description)
                        <p class="product-detail-description">{{ $product->description }}</p>
                    @endif

                    <dl class="product-detail-specs">
                        <div>
                            <dt>Category</dt>
                            <dd>{{ $product->category->name }}</dd>
                        </div>
                        @if ($product->primary_color)
                            <div>
                                <dt>Colour</dt>
                                <dd>{{ $product->primary_color }}</dd>
                            </div>
                        @endif
                        @if ($product->material)
                            <div>
                                <dt>Material</dt>
                                <dd>{{ $product->material }}</dd>
                            </div>
                        @endif
                        @if (! empty($product->sizes))
                            <div>
                                <dt>Available Sizes</dt>
                                <dd>{{ implode(', ', $product->sizes) }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt>Stock</dt>
                            <dd>{{ $product->stock_quantity > 0 ? $product->stock_quantity . ' available' : 'Out of stock' }}</dd>
                        </div>
                    </dl>

                    <div class="product-detail-actions">
                        @if ($product->stock_quantity > 0)
                            <form method="POST" action="{{ route('cart.store', $product) }}" class="product-detail-add-to-cart">
                                @csrf
                                <label for="product-detail-quantity" class="visually-hidden">Quantity</label>
                                <input
                                    type="number"
                                    name="quantity"
                                    id="product-detail-quantity"
                                    value="1"
                                    min="1"
                                    max="{{ $product->stock_quantity }}"
                                    class="input-field-input product-detail-quantity"
                                >
                                <x-button type="submit" variant="primary" class="btn-block">Add to Cart</x-button>
                            </form>
                        @else
                            <p class="product-detail-out-of-stock">Out of Stock</p>
                        @endif

                        @if ($isWishlisted)
                            <form method="POST" action="{{ route('wishlist.destroy', $product) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="wishlist-toggle is-active">
                                    <x-icon name="heart" class="wishlist-toggle-icon" />
                                    Remove from Wishlist
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('wishlist.store', $product) }}">
                                @csrf
                                <button type="submit" class="wishlist-toggle">
                                    <x-icon name="heart" class="wishlist-toggle-icon" />
                                    Add to Wishlist
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <x-titled-product-section title="Complete the Look" :products="$completeTheLook" />

            <x-recommendation-section
                title="Similar Products"
                :results="$similar"
                module="product_detail"
            />

            @auth
                <x-recommendation-section
                    title="Recommended For You"
                    :results="$recommendedForYou"
                    module="product_detail_for_you"
                />
            @endauth

            <x-titled-product-section title="Customers Also Viewed" :products="$customersAlsoViewed" />

            @auth
                <x-titled-product-section title="Recently Viewed" :products="$recentlyViewed" />
            @endauth

            <x-titled-product-section title="More From This Brand" :products="$moreFromBrand" />
        </div>
    </main>
</x-layouts.app>
