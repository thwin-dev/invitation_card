@extends('layouts.management')

@section('title', 'Edit sale')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('management.sales.show', $sale) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to sale</a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">Edit {{ $sale->voucher_code }}</h1>
            <p class="mt-1 text-sm text-gray-500">Stock and pricing will be reconciled at <strong class="font-semibold text-gray-700">{{ $branch->name }}</strong>.</p>
        </div>
    </div>

    <form method="post" action="{{ route('management.sales.update', $sale) }}" class="pos-panel overflow-hidden">
        @csrf
        @method('put')
        <div class="border-b border-gray-100 p-5 sm:p-6">
            <div class="mb-4"><h2 class="font-semibold text-gray-900">Sale details</h2><p class="mt-1 text-xs text-gray-500">Update the customer, dates, and payment information.</p></div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Customer</span><select name="customer_id" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"><option value="">Walk-in customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected($sale->customer_id === $customer->id)>{{ $customer->name }} · {{ $customer->phone }}</option>@endforeach</select></label>
                <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Sale date</span><input type="date" name="sale_date" value="{{ $sale->sale_date->toDateString() }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Paid amount</span><input type="number" step="0.01" name="paid_amount" value="{{ $sale->paid_amount }}" min="0" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Repayment date <span class="font-normal text-gray-400">· optional</span></span><input type="date" name="repayment_date" value="{{ $sale->repayment_date?->toDateString() }}" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
            </div>
        </div>

        @if($errors->any())<div class="p-5 text-sm text-red-600" role="alert">{{ $errors->first() }}</div>@endif
        <div class="p-5 sm:p-6">
            <div class="mb-4"><h2 class="font-semibold text-gray-900">Sale items</h2><p class="mt-1 text-xs text-gray-500">Available quantity and branch-specific prices are shown for {{ $branch->name }}.</p></div>
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="min-w-full text-left">
                    <thead><tr><th>Product / unit</th><th class="w-36">Quantity</th><th class="w-48">Price</th><th>Item discount</th></tr></thead>
                    <tbody>
                        @foreach($sale->items as $item)
                            <tr>
                                <td class="min-w-72"><label class="sr-only" for="sale-unit-{{ $loop->index }}">Product and unit</label><select id="sale-unit-{{ $loop->index }}" name="unit[]" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">@foreach($units as $unit)<option value="{{ $unit->id }}" @selected($item->product_unit_id === $unit->id)>{{ $unit->product->name }} · {{ $unit->name }} ({{ number_format((float) $unit->branch_quantity) }} at {{ $branch->name }})</option>@endforeach</select></td>
                                <td><label class="sr-only" for="sale-qty-{{ $loop->index }}">Quantity</label><input id="sale-qty-{{ $loop->index }}" type="number" name="qty[]" value="{{ $item->quantity }}" min="1" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm tabular-nums text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></td>
                                <td><label class="sr-only" for="sale-price-{{ $loop->index }}">Price</label><input id="sale-price-{{ $loop->index }}" type="number" step="0.01" name="price[]" value="{{ $item->price }}" min="0" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm tabular-nums text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></td>
                                <td class="min-w-56"><input type="hidden" name="item_id[]" value="{{ $item->id }}"><input type="hidden" name="discount_rule[]" value="{{ $item->discount_rule_id }}"><span>{{ $item->discount_name ? $item->discount_name.' — saved '.$item->discount_value.' '.($item->discount_type === 'percentage' ? '%' : '/ unit') : 'Auto apply eligible branch discount' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 bg-gray-50/70 px-5 py-4 sm:px-6">
            <a href="{{ route('management.sales.show', $sale) }}" class="pos-button-secondary">Cancel</a>
            <button type="submit" class="pos-button"><i data-lucide="save" class="mr-2 h-4 w-4"></i>Update sale</button>
        </div>
    </form>
@endsection
