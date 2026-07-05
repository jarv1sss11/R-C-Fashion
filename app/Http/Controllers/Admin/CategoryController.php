<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Admin\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories)
    {
    }

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => $this->categories->paginated(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'parents' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = $this->categories->create($request->user(), $request->validated());

        return redirect()->route('admin.categories.index')->with('status', "Category \"{$category->name}\" created.");
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parents' => Category::where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($request->user(), $category, $request->validated());

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->categories->archive($request->user(), $category, $request->input('reason'));

        return redirect()->route('admin.categories.index')->with('status', "Category \"{$category->name}\" archived.");
    }

    public function restore(Request $request, int $category): RedirectResponse
    {
        $category = Category::withTrashed()->findOrFail($category);

        $this->categories->restore($request->user(), $category);

        return redirect()->route('admin.categories.index')->with('status', "Category \"{$category->name}\" restored.");
    }
}
