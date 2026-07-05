<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkProductModerationRequest;
use App\Http\Requests\Admin\ProductModerationRequest;
use App\Models\Product;
use App\Services\Admin\ProductModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductModerationService $products)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.products.index', [
            'products' => $this->products->paginated($request->only(['search', 'status'])),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function approve(ProductModerationRequest $request, Product $product): RedirectResponse
    {
        $this->products->approve($request->user(), $product, $request->validated('reason'));

        return back()->with('status', "\"{$product->name}\" approved.");
    }

    public function reject(ProductModerationRequest $request, Product $product): RedirectResponse
    {
        $this->products->reject($request->user(), $product, $request->validated('reason'));

        return back()->with('status', "\"{$product->name}\" rejected and returned to draft.");
    }

    public function hide(ProductModerationRequest $request, Product $product): RedirectResponse
    {
        $this->products->hide($request->user(), $product, $request->validated('reason'));

        return back()->with('status', "\"{$product->name}\" hidden.");
    }

    public function archive(ProductModerationRequest $request, Product $product): RedirectResponse
    {
        $this->products->archive($request->user(), $product, $request->validated('reason'));

        return back()->with('status', "\"{$product->name}\" archived.");
    }

    public function restore(ProductModerationRequest $request, Product $product): RedirectResponse
    {
        $this->products->restore($request->user(), $product, $request->validated('reason'));

        return back()->with('status', "\"{$product->name}\" restored.");
    }

    public function bulkApprove(BulkProductModerationRequest $request): RedirectResponse
    {
        $count = $this->products->bulkApprove($request->user(), $request->validated('product_ids'), $request->validated('reason'));

        return back()->with('status', "{$count} product(s) approved.");
    }

    public function bulkArchive(BulkProductModerationRequest $request): RedirectResponse
    {
        $count = $this->products->bulkArchive($request->user(), $request->validated('product_ids'), $request->validated('reason'));

        return back()->with('status', "{$count} product(s) archived.");
    }

    public function bulkDelete(BulkProductModerationRequest $request): RedirectResponse
    {
        $count = $this->products->bulkDelete($request->user(), $request->validated('product_ids'), $request->validated('reason'));

        return back()->with('status', "{$count} product(s) removed from the catalogue.");
    }
}
