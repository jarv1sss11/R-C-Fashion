<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreProductRequest;
use App\Http\Requests\Vendor\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Recommendation\RecommendationCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly RecommendationCacheService $cache)
    {
    }

    public function index(Request $request): View
    {
        return view('vendor.products.index', [
            'products' => $request->user()->products()
                ->with(['category', 'images'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('vendor.products.create', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $images = $validated['images'] ?? [];
        unset($validated['images']);

        $product = DB::transaction(function () use ($request, $validated, $images) {
            $product = $request->user()->products()->create([
                ...$validated,
                'slug' => $this->uniqueSlug($validated['name']),
            ]);

            $this->attachImages($product, $images);

            return $product;
        });

        return redirect()->route('vendor.products.index')->with('status', "Product \"{$product->name}\" created.");
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('vendor.products.edit', [
            'product' => $product->load('images'),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validated();
        $images = $validated['images'] ?? [];
        unset($validated['images']);

        DB::transaction(function () use ($product, $validated, $images) {
            $product->update($validated);
            $this->attachImages($product, $images);
        });

        $this->cache->bumpGlobalVersion();

        return back()->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();

        $this->cache->bumpGlobalVersion();

        return redirect()->route('vendor.products.index')->with('status', 'Product removed.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_unless($image->product_id === $product->id, 404);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return back()->with('status', 'Image removed.');
    }

    private function attachImages(Product $product, array $images): void
    {
        $nextOrder = ($product->images()->max('display_order') ?? -1) + 1;

        foreach ($images as $index => $image) {
            $product->images()->create([
                'image_path' => $image->store('products', 'public'),
                'display_order' => $nextOrder + $index,
            ]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
