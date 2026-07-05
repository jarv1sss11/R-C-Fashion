<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ProductCatalogueService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly ProductCatalogueService $catalogue)
    {
    }

    public function show(Category $category, Request $request): View
    {
        $filters = $request->only(['gender', 'color', 'size', 'min_price', 'max_price']);
        $options = $this->catalogue->filterOptions();

        return view('catalog.category', [
            'category' => $category,
            'products' => $this->catalogue->forCategory($category, $filters),
            'colors' => $options['colors'],
            'sizes' => $options['sizes'],
            'filters' => $filters,
        ]);
    }
}
