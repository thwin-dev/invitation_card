<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductWebController extends Controller
{
    public function brands(): View
    {
        return view('management.brands.index');
    }

    public function photo(Product $product): View
    {
        return view('management.products.photo', compact('product'));
    }

    public function updatePhoto(Request $request, Product $product): RedirectResponse
    {
        $request->validate(['photo' => ['required', 'image', 'max:5120']]);
        if ($product->photo_path) {
            Storage::disk('public')->delete($product->photo_path);
        }
        $product->update(['photo_path' => $request->file('photo')->store('products', 'public')]);

        return to_route('management.products.photo', $product)->with('status', 'Product photo updated.');
    }

    public function deletePhoto(Product $product): RedirectResponse
    {
        if ($product->photo_path) {
            Storage::disk('public')->delete($product->photo_path);
        }
        $product->update(['photo_path' => null]);

        return to_route('management.products.photo', $product)->with('status', 'Product photo removed.');
    }

    public function index(Request $request): View
    {
        $categories = ProductCategory::query()->orderBy('name')->get();
        $subcategories = ProductSubcategory::query()->orderBy('name')->get();
        $products = Product::query()->with(['category', 'subcategory'])
            ->when($request->integer('category_id'), fn ($query, int $id) => $query->where('category_id', $id))
            ->when($request->integer('subcategory_id'), fn ($query, int $id) => $query->where('subcategory_id', $id))
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('name')->paginate(15)->withQueryString();
        $editProduct = $request->integer('edit') ? Product::query()->find($request->integer('edit')) : null;

        return view('management.products.index', compact('categories', 'subcategories', 'products', 'editProduct'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:255', 'unique:products,code'], 'name' => ['required', 'string', 'max:255'], 'category_id' => ['required', 'exists:product_categories,id'], 'subcategory_id' => ['required', Rule::exists('product_subcategories', 'id')->where('category_id', $request->input('category_id'))], 'customer_visible' => ['sometimes', 'boolean'], 'photo' => ['nullable', 'image', 'max:5120']]);
        $data['photo_path'] = $request->file('photo')?->store('products', 'public');
        unset($data['photo']);
        Product::query()->create([...$data, 'created_by' => $request->user()->id]);

        return to_route('management.products.index')->with('status', 'Product created.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:255', 'unique:products,code,'.$product->id], 'name' => ['required', 'string', 'max:255'], 'category_id' => ['required', 'exists:product_categories,id'], 'subcategory_id' => ['required', Rule::exists('product_subcategories', 'id')->where('category_id', $request->input('category_id'))], 'customer_visible' => ['sometimes', 'boolean'], 'photo' => ['nullable', 'image', 'max:5120']]);
        $data['customer_visible'] = $request->boolean('customer_visible');
        if ($request->hasFile('photo')) {
            if ($product->photo_path) {
                Storage::disk('public')->delete($product->photo_path);
            } $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }
        unset($data['photo']);
        $product->update($data);

        return to_route('management.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('management.products.index')->with('status', 'Product deleted.');
    }
}
