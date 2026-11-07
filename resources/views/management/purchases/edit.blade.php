@extends('layouts.management')

@section('title', 'Edit Purchase')

@section('content')
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('management.purchases.show', $purchase) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition hover:text-brand-700">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to purchase
            </a>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900">Edit purchase #{{ $purchase->id }}</h1>
            <p class="mt-1 text-sm text-gray-500">Adjust supplier details and reconcile purchased stock lines.</p>
        </div>
        <a href="{{ route('management.purchases.index') }}" class="pos-button-secondary">
            <i data-lucide="list" class="mr-2 h-4 w-4"></i>
            Purchase history
        </a>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('management.purchases.update', $purchase) }}" class="pos-panel overflow-hidden">
        @csrf
        @method('put')

        <div class="grid gap-5 border-b border-gray-100 p-5 sm:grid-cols-2 lg:grid-cols-3">
            <label class="text-sm font-medium text-gray-700">
                Registered supplier
                <select
                    name="supplier_id"
                    class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                >
                    <option value="">Choose registered supplier</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ old('supplier_id', $purchase->supplier_id) == $sup->id ? 'selected' : '' }}>
                            {{ $sup->name }}{{ $sup->company_name ? ' ('.$sup->company_name.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700">
                Supplier name / Brand
                <input
                    name="supplier_name"
                    value="{{ old('supplier_name', $purchase->supplier_name) }}"
                    placeholder="Supplier name"
                    class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                >
            </label>
            <label class="text-sm font-medium text-gray-700">
                Purchase date
                <input
                    type="date"
                    name="purchase_date"
                    value="{{ old('purchase_date', $purchase->purchase_date?->toDateString()) }}"
                    required
                    class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                >
            </label>
            <label class="text-sm font-medium text-gray-700">
                Paid amount
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="paid_amount"
                    value="{{ old('paid_amount', (float) $purchase->paid_amount) }}"
                    class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                >
            </label>
            <label class="text-sm font-medium text-gray-700">
                Payment due date
                <input
                    type="date"
                    name="payment_due_date"
                    value="{{ old('payment_due_date', $purchase->payment_due_date?->toDateString()) }}"
                    class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                >
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3">Quantity</th>
                        <th class="px-5 py-3">Purchase price</th><th class="px-5 py-3">Tracking</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($purchase->items as $item)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <select
                                    name="unit[]"
                                    required
                                    class="w-full min-w-72 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                                >
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" @selected((int) $item->product_unit_id === (int) $unit->id)>
                                            {{ $unit->product?->name }} · {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-4">
                                <input
                                    type="number"
                                    name="qty[]"
                                    value="{{ old('qty.'.$loop->index, $item->quantity) }}"
                                    min="1"
                                    required
                                    class="w-40 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                                >
                            </td>
                            <td class="px-5 py-4">
                                <input
                                    type="number"
                                    step="0.01"
                                    name="price[]"
                                    value="{{ old('price.'.$loop->index, $item->price) }}"
                                    min="0"
                                    required
                                    class="w-48 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                                >
                            </td>
                            <td class="px-5 py-4"><div class="space-y-2">
                                <label class="block text-xs">Batch<input name="batch_number[]" value="{{ old('batch_number.'.$loop->index, $item->batch_number) }}" class="block rounded-lg border border-gray-300 p-2"></label>
                                <label class="block text-xs">Serial<input name="serial_number[]" value="{{ old('serial_number.'.$loop->index, $item->serial_number) }}" class="block rounded-lg border border-gray-300 p-2"></label>
                                <label class="block text-xs">Expiry<input type="date" name="expires_at[]" value="{{ old('expires_at.'.$loop->index, $item->expires_at ? substr($item->expires_at, 0, 10) : '') }}" class="block rounded-lg border border-gray-300 p-2"></label>
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 p-5">
            <a href="{{ route('management.purchases.show', $purchase) }}" class="pos-button-secondary">
                Cancel
            </a>
            <button class="pos-button">
                <i data-lucide="save" class="mr-2 h-4 w-4"></i>
                Update purchase
            </button>
        </div>
    </form>
@endsection
