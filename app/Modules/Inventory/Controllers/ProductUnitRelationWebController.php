<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Models\ProductUnitConversion;
use App\Modules\Inventory\Models\ProductUnitRelation;
use App\Modules\Product\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Modules\Inventory\Support\StockMovementRecorder;

class ProductUnitRelationWebController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()->with('units')->orderBy('name')->get();
        $product = Product::query()->find($request->integer('product_id'));
        $units = $product?->units ?? collect();
        $relations = $product ? ProductUnitRelation::query()->with(['fromUnit', 'toUnit'])->where('product_id', $product->id)->get() : collect();
        $conversions = $product ? ProductUnitConversion::query()->with(['fromUnit', 'toUnit'])->where('product_id', $product->id)->latest()->limit(20)->get() : collect();
        return view('management.product-unit-relations.index', compact('products', 'product', 'units', 'relations', 'conversions'));
    }

    public function storeRelation(Request $request): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'exists:products,id'], 'from_unit_id' => ['required', 'different:to_unit_id', 'exists:product_units,id'], 'to_unit_id' => ['required', 'exists:product_units,id'], 'quantity' => ['required', 'integer', 'min:1']]);
        abort_if(ProductUnitRelation::query()->where(fn ($q) => $q->where(['from_unit_id' => $data['from_unit_id'], 'to_unit_id' => $data['to_unit_id']])->orWhere(fn ($q) => $q->where('from_unit_id', $data['to_unit_id'])->where('to_unit_id', $data['from_unit_id'])))->exists(), 422, 'This unit relation already exists.');
        ProductUnitRelation::query()->create($data);
        return to_route('management.product-unit-relations.index', ['product_id' => $data['product_id']])->with('status', 'Unit relation created.');
    }

    public function convert(Request $request): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'exists:products,id'], 'from_unit_id' => ['required', 'exists:product_units,id'], 'to_unit_id' => ['required', 'different:from_unit_id', 'exists:product_units,id'], 'quantity' => ['required', 'integer', 'min:1']]);
        $relation = ProductUnitRelation::query()->where('product_id', $data['product_id'])->where('from_unit_id', $data['from_unit_id'])->where('to_unit_id', $data['to_unit_id'])->firstOrFail();
        DB::transaction(function () use ($data, $relation): void {
            $from = ProductUnit::query()->lockForUpdate()->findOrFail($data['from_unit_id']);
            $to = ProductUnit::query()->lockForUpdate()->findOrFail($data['to_unit_id']);
            abort_if($from->current_quantity < $data['quantity'], 422, 'Not enough quantity in the source unit.');
            $fromQuantity = $from->current_quantity - $data['quantity'];
            $toQuantity = $to->current_quantity + ($data['quantity'] * $relation->quantity);
            $fromBefore = $from->current_quantity; $toBefore = $to->current_quantity;
            $from->decrement('current_quantity', $data['quantity']);
            $to->increment('current_quantity', $data['quantity'] * $relation->quantity);
            StockMovementRecorder::record($from, 'OUT', $fromBefore, $fromBefore - $data['quantity'], auth('web')->id(), 'conversion', null, 'Unit conversion');
            StockMovementRecorder::record($to, 'IN', $toBefore, $toBefore + ($data['quantity'] * $relation->quantity), auth('web')->id(), 'conversion', null, 'Unit conversion');
            ProductUnitConversion::query()->create(['product_id' => $data['product_id'], 'from_unit_id' => $from->id, 'from_unit_quantity' => $fromQuantity, 'to_unit_id' => $to->id, 'to_unit_quantity' => $toQuantity]);
        });
        return to_route('management.product-unit-relations.index', ['product_id' => $data['product_id']])->with('status', 'Unit quantity converted.');
    }

    public function destroy(ProductUnitRelation $relation): RedirectResponse
    {
        $productId = $relation->product_id;
        $relation->delete();
        return to_route('management.product-unit-relations.index', ['product_id' => $productId])->with('status', 'Unit relation deleted.');
    }
}
