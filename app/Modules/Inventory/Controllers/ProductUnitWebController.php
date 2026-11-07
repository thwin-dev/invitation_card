<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Support\Ean13;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductUnitWebController extends Controller
{
    public function barcode(ProductUnit $unit): View
    {
        return view('management.products.barcode', ['unit' => $unit->load('product'), 'bars' => Ean13::bars((string) $unit->original_code)]);
    }

    public function index(Request $request): View
    {
        $products = Product::query()->orderBy('name')->get();
        $units = ProductUnit::query()->with('product')->whereHas('product')->when($request->integer('product_id'), fn ($query, int $id) => $query->where('product_id', $id))->orderBy('name')->paginate(15)->withQueryString();

        return view('management.product-units.index', compact('products', 'units'));
    }

    public function store(Request $request): RedirectResponse
    {
        app(InventoryService::class)->saveUnit(new ProductUnit, $request->validate(['product_id' => ['required', 'exists:products,id'], 'name' => ['required', 'string', 'max:255'], 'code' => ['nullable', 'string', 'max:255'], 'original_code' => ['nullable', 'string', 'max:255'], 'current_quantity' => ['required', 'integer', 'min:0'], 'reorder_quantity' => ['required', 'integer', 'min:0'], 'normal_sale_price' => ['required', 'numeric', 'min:0'], 'whole_sale_price' => ['required', 'numeric', 'min:0'], 'order_price' => ['required', 'numeric', 'min:0'], 'purchase_price' => ['required', 'numeric', 'min:0']]), $request->user()->id);

        return to_route('management.product-units.index')->with('status', 'Counting unit created.');
    }

    public function update(Request $request, ProductUnit $unit): RedirectResponse
    {
        $applicable = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['nullable', 'string', 'max:255'], 'original_code' => ['nullable', 'string', 'max:255'], 'reorder_quantity' => ['required', 'integer', 'min:0'], 'normal_sale_price' => ['required', 'numeric', 'min:0'], 'whole_sale_price' => ['required', 'numeric', 'min:0'], 'order_price' => ['required', 'numeric', 'min:0'], 'purchase_price' => ['required', 'numeric', 'min:0']]);
        if ($request->has('current_quantity') && (int) $request->input('current_quantity') !== $unit->current_quantity) {
            return back()->withErrors(['current_quantity' => 'Use Inventory operations to adjust or count stock.'])->withInput();
        }
        app(InventoryService::class)->saveUnit($unit, $applicable, $request->user()->id);

        return to_route('management.product-units.index')->with('status', 'Counting unit updated.');
    }

    public function updateCode(Request $request, ProductUnit $unit): RedirectResponse
    {
        $unit->update($request->validate(['code' => ['nullable', 'string', 'max:255']]));

        return back()->with('status', 'Unit code updated.');
    }

    public function updateOriginalCode(Request $request, ProductUnit $unit): RedirectResponse
    {
        $unit->update($request->validate(['original_code' => ['nullable', 'string', 'max:255']]));

        return back()->with('status', 'Original unit code updated.');
    }

    public function destroy(ProductUnit $unit): RedirectResponse
    {
        $unit->delete();

        return to_route('management.product-units.index')->with('status', 'Counting unit deleted.');
    }
}
