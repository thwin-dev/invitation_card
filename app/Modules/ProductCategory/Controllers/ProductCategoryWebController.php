<?php

namespace App\Modules\ProductCategory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ProductCategory\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCategoryWebController extends Controller
{
    public function index(Request $request): View
    {
        $categories = ProductCategory::query()
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->with('subcategories')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $editCategory = $request->filled('edit') ? ProductCategory::query()->find($request->integer('edit')) : null;
        return view('management.product-categories.index', compact('categories', 'editCategory'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255']]);
        ProductCategory::query()->create([...$data, 'created_by' => $request->user()->id]);
        return to_route('management.product-categories.index')->with('status', 'Category created.');
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validate(['code' => ['nullable', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255']]));
        return to_route('management.product-categories.index')->with('status', 'Category updated.');
    }

    public function toggle(ProductCategory $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);
        return back()->with('status', 'Category status updated.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        abort_if($category->subcategories()->exists(), 422, 'Delete subcategories before deleting this category.');
        $category->delete();
        return to_route('management.product-categories.index')->with('status', 'Category deleted.');
    }
}
