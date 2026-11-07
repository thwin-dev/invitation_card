<?php

namespace App\Modules\ProductCategory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductSubcategoryWebController extends Controller
{
    public function toggle(ProductSubcategory $subcategory): RedirectResponse
    {
        $subcategory->update(['is_active' => ! $subcategory->is_active]);
        return back()->with('status', 'Subcategory status updated.');
    }
    public function index(Request $request): View
    {
        $categories = ProductCategory::query()->orderBy('name')->get();
        $subcategories = ProductSubcategory::query()->with('category')
            ->when($request->integer('category_id'), fn ($query, int $id) => $query->where('category_id', $id))
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('name')->paginate(15)->withQueryString();

        $editSubcategory = $request->filled('edit') ? ProductSubcategory::query()->find($request->integer('edit')) : null;
        return view('management.product-subcategories.index', compact('categories', 'subcategories', 'editSubcategory'));
    }

    public function store(Request $request): RedirectResponse
    {
        ProductSubcategory::query()->create($request->validate(['code' => ['nullable', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'category_id' => ['required', 'exists:product_categories,id']]));
        return to_route('management.product-subcategories.index')->with('status', 'Subcategory created.');
    }

    public function update(Request $request, ProductSubcategory $subcategory): RedirectResponse
    {
        $subcategory->update($request->validate(['code' => ['nullable', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'category_id' => ['required', 'exists:product_categories,id']]));
        return to_route('management.product-subcategories.index')->with('status', 'Subcategory updated.');
    }

    public function destroy(ProductSubcategory $subcategory): RedirectResponse
    {
        abort_if($subcategory->products()->withTrashed()->exists(), 422, 'Move products before deleting this subcategory.');
        $subcategory->delete();
        return to_route('management.product-subcategories.index')->with('status', 'Subcategory deleted.');
    }
}
