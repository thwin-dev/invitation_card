<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockWebController extends Controller
{
    public function operations(): View
    {
        return view('management.stock.operations');
    }

    public function branches(): View
    {
        return view('management.branches.index');
    }

    public function branchPrices(): View
    {
        return view('management.branches.prices');
    }

    public function index(Request $request): View
    {
        $mode = $request->string('mode')->trim()->value() ?: 'overview';
        $units = ProductUnit::query()->with('product')->whereHas('product')->when($request->string('search')->trim()->value(), fn ($q, string $search) => $q->where(fn ($q) => $q->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%"))->orWhere('name', 'like', "%{$search}%")))->when($mode === 'reorder', fn ($q) => $q->whereColumn('current_quantity', '<=', 'reorder_quantity'))->orderBy('name')->paginate(20)->withQueryString();
        $reorderCount = ProductUnit::query()->whereHas('product')->whereColumn('current_quantity', '<=', 'reorder_quantity')->count();
        $stockValue = ProductUnit::query()->whereHas('product')->selectRaw('SUM(current_quantity * purchase_price) as value')->value('value') ?? 0;

        return view('management.stock.index', compact('units', 'reorderCount', 'stockValue', 'mode'));
    }

    public function update(Request $request, ProductUnit $unit): RedirectResponse
    {
        if ($request->has('current_quantity') && (int) $request->input('current_quantity') !== $unit->current_quantity) {
            return back()->withErrors(['current_quantity' => 'Use Inventory operations to adjust or count stock.'])->withInput();
        }
        app(InventoryService::class)->saveUnit($unit, $request->validate(['reorder_quantity' => ['required', 'integer', 'min:0'], 'purchase_price' => ['required', 'numeric', 'min:0'], 'normal_sale_price' => ['required', 'numeric', 'min:0'], 'whole_sale_price' => ['required', 'numeric', 'min:0'], 'order_price' => ['required', 'numeric', 'min:0']]), $request->user()->id);

        return to_route('management.stock.index')->with('status', 'Stock and prices updated.');
    }

    public function updateCount(Request $request, ProductUnit $unit): RedirectResponse
    {
        app(InventoryService::class)->saveUnit($unit, $request->validate(['current_quantity' => ['required', 'integer', 'min:0'], 'reorder_quantity' => ['required', 'integer', 'min:0']]), $request->user()->id);

        return back()->with('status', 'Stock quantity updated.');
    }

    public function updatePrice(Request $request, ProductUnit $unit): RedirectResponse
    {
        $unit->update($request->validate(['purchase_price' => ['required', 'numeric', 'min:0'], 'normal_sale_price' => ['required', 'numeric', 'min:0'], 'whole_sale_price' => ['required', 'numeric', 'min:0'], 'order_price' => ['required', 'numeric', 'min:0']]));

        return back()->with('status', 'Stock prices updated.');
    }
}
