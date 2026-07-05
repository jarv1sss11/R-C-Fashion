<?php

namespace App\Http\Controllers;

use App\Models\VendorProfile;
use App\Services\ProductCatalogueService;
use Illuminate\View\View;

/**
 * Minimal public storefront — just enough for the catalogue's "Sold by"
 * link to resolve to something real. A full vendor storefront (filters,
 * store banner, etc.) is out of scope for Step 8.
 */
class VendorController extends Controller
{
    public function __construct(private readonly ProductCatalogueService $catalogue)
    {
    }

    public function show(VendorProfile $vendor): View
    {
        $productCount = $vendor->user->products()->published()->count();

        $averageRating = $vendor->user->products()
            ->published()
            ->withAvg('reviews', 'rating')
            ->get()
            ->avg('reviews_avg_rating');

        return view('vendors.show', [
            'vendor' => $vendor,
            'products' => $this->catalogue->forVendor($vendor->user_id),
            'productCount' => $productCount,
            'averageRating' => $averageRating,
        ]);
    }
}
