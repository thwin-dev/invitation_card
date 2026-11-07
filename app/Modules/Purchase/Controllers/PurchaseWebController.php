<?php

namespace App\Modules\Purchase\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Support\StockMovementRecorder;
use App\Modules\Purchase\Models\PosSupplier;
use App\Modules\Purchase\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseWebController extends Controller
{
    private function tracking(array $data, int $index): array
    {
        return ['batch_number' => ($data['batch_number'][$index] ?? '') ?: null,
            'serial_number' => ($data['serial_number'][$index] ?? '') ?: null,
            'expires_at' => ($data['expires_at'][$index] ?? '') ?: null];
    }

    public function index(Request $request): View
    {
        return view('management.purchases.index');
    }

    public function create(Request $request): View
    {
        $units = ProductUnit::query()->with('product')->whereHas('product')->orderBy('name')->get();
        $suppliers = PosSupplier::query()->orderBy('name')->get();
        $selectedSupplierId = $request->integer('supplier_id') ?: null;

        return view('management.purchases.create', compact('units', 'suppliers', 'selectedSupplierId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:pos_suppliers,id'],
            'supplier_name' => ['required_without:supplier_id', 'nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_due_date' => ['nullable', 'date'],
            'unit' => ['required', 'array', 'min:1'],
            'unit.*' => ['required', 'exists:product_units,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['required', 'integer', 'min:1'],
            'batch_number' => ['nullable', 'array'], 'batch_number.*' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'array'], 'serial_number.*' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'array'], 'expires_at.*' => ['nullable', 'date'],
            'price' => ['required', 'array'],
            'price.*' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $totalQuantity = array_sum($data['qty']);
            $totalPrice = 0;
            foreach ($data['qty'] as $index => $quantity) {
                $totalPrice += $quantity * $data['price'][$index];
            }

            $supplierId = ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
            $supplierName = $data['supplier_name'] ?? '';
            if ($supplierId && empty($supplierName)) {
                $supplier = PosSupplier::query()->find($supplierId);
                $supplierName = $supplier ? $supplier->name.($supplier->company_name ? " ({$supplier->company_name})" : '') : 'Supplier';
            }

            $tenderedPaid = isset($data['paid_amount']) ? (float) $data['paid_amount'] : $totalPrice;
            $paidAmount = min($tenderedPaid, $totalPrice);
            $payableAmount = max(0, $totalPrice - $paidAmount);
            $paymentStatus = $payableAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $purchase = Purchase::query()->create([
                'supplier_id' => $supplierId,
                'supplier_name' => $supplierName,
                'purchase_date' => $data['purchase_date'],
                'total_quantity' => $totalQuantity,
                'total_price' => $totalPrice,
                'paid_amount' => $paidAmount,
                'payable_amount' => $payableAmount,
                'payment_status' => $paymentStatus,
                'payment_due_date' => $data['payment_due_date'] ?? null,
                'purchase_by' => $request->user()->id,
            ]);

            foreach ($data['unit'] as $index => $unitId) {
                $unit = ProductUnit::query()->lockForUpdate()->findOrFail($unitId);
                $quantity = $data['qty'][$index];
                $price = $data['price'][$index];
                $purchase->items()->create(['product_unit_id' => $unit->id, 'quantity' => $quantity, 'price' => $price, ...$this->tracking($data, $index)]);
                $before = $unit->current_quantity;
                $unit->increment('current_quantity', $quantity);
                StockMovementRecorder::record($unit, 'IN', $before, $before + $quantity, $request->user()->id, 'purchase', $purchase->id, 'Purchase received', $this->tracking($data, $index));
                $unit->update(['purchase_price' => $price]);
            }
        });

        return to_route('management.purchases.index')->with('status', 'Purchase recorded and stock updated.');
    }

    public function show(Purchase $purchase): View
    {
        return view('management.purchases.show', ['purchase' => $purchase->load(['items.unit.product', 'buyer', 'supplier', 'payments.payer'])]);
    }

    public function print(Purchase $purchase): View
    {
        return view('management.purchases.print', ['purchase' => $purchase->load(['items.unit.product', 'buyer', 'supplier'])]);
    }

    public function edit(Purchase $purchase): View
    {
        $units = ProductUnit::query()->with('product')->whereHas('product')->orderBy('name')->get();
        $suppliers = PosSupplier::query()->orderBy('name')->get();

        return view('management.purchases.edit', compact('purchase', 'units', 'suppliers'));
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:pos_suppliers,id'],
            'supplier_name' => ['required_without:supplier_id', 'nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_due_date' => ['nullable', 'date'],
            'unit' => ['required', 'array', 'min:1'],
            'unit.*' => ['required', 'exists:product_units,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['required', 'integer', 'min:1'],
            'batch_number' => ['nullable', 'array'], 'batch_number.*' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'array'], 'serial_number.*' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'array'], 'expires_at.*' => ['nullable', 'date'],
            'price' => ['required', 'array'],
            'price.*' => ['required', 'numeric', 'min:0'],
        ]);
        DB::transaction(function () use ($data, $purchase): void {
            $purchase->load('items');
            foreach ($purchase->items as $item) {
                $unit = ProductUnit::query()->lockForUpdate()->findOrFail($item->product_unit_id);
                $before = $unit->current_quantity;
                abort_if($before < $item->quantity, 422, 'Stock is already lower than this purchase quantity.');
                $unit->decrement('current_quantity', $item->quantity);
                StockMovementRecorder::record($unit, 'OUT', $before, $before - $item->quantity, auth('web')->id(), 'purchase', $purchase->id, 'Purchase edit rollback');
            }
            $totalQuantity = array_sum($data['qty']);
            $totalPrice = 0;
            foreach ($data['qty'] as $index => $quantity) {
                $totalPrice += $quantity * $data['price'][$index];
            }

            $supplierId = ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
            $supplierName = $data['supplier_name'] ?? '';
            if ($supplierId && empty($supplierName)) {
                $supplier = PosSupplier::query()->find($supplierId);
                $supplierName = $supplier ? $supplier->name.($supplier->company_name ? " ({$supplier->company_name})" : '') : 'Supplier';
            }

            $tenderedPaid = isset($data['paid_amount']) ? (float) $data['paid_amount'] : (float) $purchase->paid_amount;
            $paidAmount = min($tenderedPaid, $totalPrice);
            $payableAmount = max(0, $totalPrice - $paidAmount);
            $paymentStatus = $payableAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $purchase->update([
                'supplier_id' => $supplierId,
                'supplier_name' => $supplierName,
                'purchase_date' => $data['purchase_date'],
                'total_quantity' => $totalQuantity,
                'total_price' => $totalPrice,
                'paid_amount' => $paidAmount,
                'payable_amount' => $payableAmount,
                'payment_status' => $paymentStatus,
                'payment_due_date' => $data['payment_due_date'] ?? null,
            ]);
            $purchase->items()->get()->each(fn ($item) => $item->delete());
            foreach ($data['unit'] as $index => $unitId) {
                $unit = ProductUnit::query()->lockForUpdate()->findOrFail($unitId);
                $quantity = $data['qty'][$index];
                $before = $unit->current_quantity;
                $unit->increment('current_quantity', $quantity);
                StockMovementRecorder::record($unit, 'IN', $before, $before + $quantity, auth('web')->id(), 'purchase', $purchase->id, 'Purchase updated', $this->tracking($data, $index));
                $unit->update(['purchase_price' => $data['price'][$index]]);
                $purchase->items()->create(['product_unit_id' => $unitId, 'quantity' => $quantity, 'price' => $data['price'][$index], ...$this->tracking($data, $index)]);
            }
        });

        return to_route('management.purchases.show', $purchase)->with('status', 'Purchase updated and stock reconciled.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        DB::transaction(function () use ($purchase): void {
            $purchase->load('items');
            foreach ($purchase->items as $item) {
                $unit = ProductUnit::query()->lockForUpdate()->findOrFail($item->product_unit_id);
                $before = $unit->current_quantity;
                abort_if($before < $item->quantity, 422, 'Stock is already lower than this purchase quantity.');
                $unit->decrement('current_quantity', $item->quantity);
                StockMovementRecorder::record($unit, 'OUT', $before, $before - $item->quantity, auth('web')->id(), 'purchase', $purchase->id, 'Purchase deleted');
            } $purchase->delete();
        });

        return to_route('management.purchases.index')->with('status', 'Purchase deleted and stock reconciled.');
    }

    public function export(Request $request)
    {
        $purchases = Purchase::query()->with('buyer')->when($request->date('from'), fn ($q, $date) => $q->whereDate('purchase_date', '>=', $date))->when($request->date('to'), fn ($q, $date) => $q->whereDate('purchase_date', '<=', $date))->latest('purchase_date')->get();
        \App\Support\ActivityLogger::record('purchases', 'exported', 'Exported purchases CSV');

        return response()->streamDownload(function () use ($purchases): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Date', 'Supplier', 'Quantity', 'Total', 'Recorded by']);
            foreach ($purchases as $purchase) {
                fputcsv($out, [$purchase->id, $purchase->purchase_date?->toDateString(), $purchase->supplier_name, $purchase->total_quantity, $purchase->total_price, $purchase->buyer?->name]);
            } fclose($out);
        }, 'purchases-'.now()->format('Y-m-d').'.csv');
    }
}
