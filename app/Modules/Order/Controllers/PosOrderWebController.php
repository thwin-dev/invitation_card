<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Order\Models\PosOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Modules\Inventory\Support\StockMovementRecorder;

class PosOrderWebController extends Controller
{
    public function index(Request $request): View
    {
        $orders = PosOrder::query()->with('customer')
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('order_number', 'like', "%{$search}%")->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))))
            ->when($request->string('status')->trim()->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('order_date', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('order_date', '<=', $to))
            ->latest('order_date')->paginate(15)->withQueryString();
        return view('management.orders.index', compact('orders'));
    }
    public function create(Request $request): View { $units = ProductUnit::query()->with('product')->whereHas('product')->orderBy('name')->get(); $customers = PosCustomer::query()->orderBy('name')->get(); $selectedCustomer = $request->integer('customer_id'); return view('management.orders.create', compact('units', 'customers', 'selectedCustomer')); }
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['customer_id' => ['required', 'exists:pos_customers,id'], 'address' => ['required', 'string'], 'phone' => ['nullable', 'string', 'max:255'], 'order_date' => ['required', 'date'], 'unit' => ['required', 'array', 'min:1'], 'unit.*' => ['required', 'exists:product_units,id'], 'qty' => ['required', 'array'], 'qty.*' => ['required', 'integer', 'min:1'], 'price' => ['required', 'array'], 'price.*' => ['required', 'numeric', 'min:0']]);
        $order = DB::transaction(function () use ($data): PosOrder { $totalQuantity = array_sum($data['qty']); $estimatedPrice = 0; foreach ($data['qty'] as $index => $quantity) { $estimatedPrice += $quantity * $data['price'][$index]; } $order = PosOrder::query()->create(['order_number' => 'ORD-'.str_pad((string) (PosOrder::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT), 'customer_id' => $data['customer_id'], 'address' => $data['address'], 'phone' => $data['phone'] ?? null, 'order_date' => $data['order_date'], 'total_quantity' => $totalQuantity, 'estimated_price' => $estimatedPrice]); foreach ($data['unit'] as $index => $unitId) { $order->items()->create(['product_unit_id' => $unitId, 'quantity' => $data['qty'][$index], 'estimated_price' => $data['price'][$index]]); } return $order; });
        return to_route('management.orders.show', $order)->with('status', 'Order created.');
    }
    public function show(PosOrder $order): View { return view('management.orders.show', ['order' => $order->load(['customer', 'items.unit.product', 'deliveryBy'])]); }
    public function print(PosOrder $order): View { return view('management.orders.print', ['order' => $order->load(['customer', 'items.unit.product', 'deliveryBy'])]); }
    public function updateStatus(Request $request, PosOrder $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,confirmed,changed,delivered,cancelled'], 'delivered_date' => ['nullable', 'date']]);
        DB::transaction(function () use ($data, $order, $request): void {
            $wasDelivered = $order->status === 'delivered'; $willBeDelivered = $data['status'] === 'delivered'; $order->load('items');
            if ($wasDelivered && ! $willBeDelivered) foreach ($order->items as $item) { $unit = ProductUnit::query()->lockForUpdate()->findOrFail($item->product_unit_id); $before = $unit->current_quantity; $unit->increment('current_quantity', $item->quantity); StockMovementRecorder::record($unit, 'IN', $before, $before + $item->quantity, $request->user()->id, 'order', $order->id, 'Order delivery reversed'); }
            if (! $wasDelivered && $willBeDelivered) foreach ($order->items as $item) { $unit = ProductUnit::query()->lockForUpdate()->findOrFail($item->product_unit_id); $before = $unit->current_quantity; abort_if($before < $item->quantity, 422, 'Not enough stock for '.$unit->name.'.'); $unit->decrement('current_quantity', $item->quantity); StockMovementRecorder::record($unit, 'OUT', $before, $before - $item->quantity, $request->user()->id, 'order', $order->id, 'Order delivered'); }
            $order->update(['status' => $data['status'], 'delivered_date' => $willBeDelivered ? ($data['delivered_date'] ?? now()) : null, 'delivery_by' => $willBeDelivered ? $request->user()->id : null]);
        });
        return back()->with('status', 'Order status updated.');
    }
    public function edit(PosOrder $order): View
    {
        abort_if($order->status === 'delivered', 422, 'Delivered orders cannot be edited.');
        $units = ProductUnit::query()->with('product')->whereHas('product')->orderBy('name')->get(); $customers = PosCustomer::query()->orderBy('name')->get();
        return view('management.orders.edit', compact('order', 'units', 'customers'));
    }
    public function update(Request $request, PosOrder $order): RedirectResponse
    {
        abort_if(in_array($order->status, ['delivered', 'cancelled'], true), 422, 'This order can no longer be edited.');
        $data = $request->validate(['customer_id' => ['required', 'exists:pos_customers,id'], 'address' => ['required', 'string'], 'phone' => ['nullable', 'string', 'max:255'], 'order_date' => ['required', 'date'], 'unit' => ['required', 'array', 'min:1'], 'unit.*' => ['required', 'exists:product_units,id'], 'qty' => ['required', 'array'], 'qty.*' => ['required', 'integer', 'min:1'], 'price' => ['required', 'array'], 'price.*' => ['required', 'numeric', 'min:0']]);
        $totalQuantity = array_sum($data['qty']); $estimatedPrice = 0; foreach ($data['qty'] as $index => $quantity) $estimatedPrice += $quantity * $data['price'][$index];
        DB::transaction(function () use ($data, $order, $totalQuantity, $estimatedPrice): void { $order->update(['customer_id' => $data['customer_id'], 'address' => $data['address'], 'phone' => $data['phone'] ?? null, 'order_date' => $data['order_date'], 'total_quantity' => $totalQuantity, 'estimated_price' => $estimatedPrice]); $order->items()->get()->each(fn ($item) => $item->delete()); foreach ($data['unit'] as $index => $unitId) $order->items()->create(['product_unit_id' => $unitId, 'quantity' => $data['qty'][$index], 'estimated_price' => $data['price'][$index]]); });
        return to_route('management.orders.show', $order)->with('status', 'Order updated.');
    }
    public function destroy(PosOrder $order): RedirectResponse
    {
        abort_if($order->status === 'delivered', 422, 'Delivered orders must be cancelled through the status workflow.');
        $order->delete();
        return to_route('management.orders.index')->with('status', 'Order deleted.');
    }
    public function export(Request $request)
    {
        $orders = PosOrder::query()->with('customer')->when($request->string('status')->trim()->value(), fn ($q, string $status) => $q->where('status', $status))->latest('order_date')->get();
        \App\Support\ActivityLogger::record('orders', 'exported', 'Exported orders CSV');
        return response()->streamDownload(function () use ($orders): void { $out = fopen('php://output', 'w'); fputcsv($out, ['Order', 'Date', 'Customer', 'Quantity', 'Estimated price', 'Status']); foreach ($orders as $order) fputcsv($out, [$order->order_number, $order->order_date?->toDateTimeString(), $order->customer?->name, $order->total_quantity, $order->estimated_price, $order->status]); fclose($out); }, 'orders-'.now()->format('Y-m-d').'.csv');
    }
}
