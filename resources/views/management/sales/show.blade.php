@extends('layouts.management')

@section('title', 'Sale details')

@section('content')
<div x-data="{ cancelOpen: false }">
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('management.sales.index') }}" class="text-sm font-medium text-brand-500">Back to sales</a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $sale->voucher_code }}</h1>
        <p class="mt-2 text-sm text-gray-500">{{ $sale->branch?->name ?? $sale->branch_name_snapshot ?? 'Main' }} · {{ $sale->sale_date->format('d M Y') }} · {{ $sale->customer?->name ?: 'Walk-in customer' }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('management.sales.print', $sale) }}" target="_blank" class="pos-button-secondary"><i data-lucide="printer" class="mr-2 h-4 w-4"></i>Print voucher</a>
        @if($sale->status !== 'cancelled' && auth('web')->user()?->hasPermissionTo('sales', 'edit'))
            <a href="{{ route('management.sales.edit', $sale) }}" class="pos-button-secondary"><i data-lucide="pencil" class="mr-2 h-4 w-4"></i>Edit</a>
        @endif
        @if($sale->status !== 'cancelled' && auth('web')->user()?->hasPermissionTo('sales', 'edit'))
            <form x-ref="cancelForm" method="post" action="{{ route('management.sales.cancel', $sale) }}">
                @csrf @method('put')
                <button type="button" @click="cancelOpen = true" style="background-color:#dc2626;color:#ffffff;" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm hover:opacity-90"><i data-lucide="ban" class="mr-2 h-4 w-4"></i>Cancel sale</button>
            </form>
        @endif
    </div>
</div>

<section class="pos-panel mt-6 overflow-hidden">
    <div class="grid gap-5 border-b border-gray-100 p-5 lg:grid-cols-[1fr_280px]">
        <div class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div><span class="block text-gray-500">Status</span><span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $sale->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">{{ ucfirst($sale->status) }}</span></div>
            <div><span class="block text-gray-500">Customer</span><span class="font-medium text-gray-900">{{ $sale->customer?->name ?: 'Walk-in customer' }}</span></div>
            <div><span class="block text-gray-500">Shop</span><span class="font-medium text-gray-900">{{ $sale->branch?->name ?? $sale->branch_name_snapshot ?? 'Main' }}</span></div>
            <div><span class="block text-gray-500">Sold by</span><span class="font-medium text-gray-900">{{ $sale->seller?->name ?: '-' }}</span></div>
            <div><span class="block text-gray-500">Quantity</span><span class="font-medium text-gray-900">{{ $sale->total_quantity }}</span></div>
            <div><span class="block text-gray-500">Sale date</span><span class="font-medium text-gray-900">{{ $sale->sale_date->format('d M Y') }}</span></div>
            <div><span class="block text-gray-500">Repayment date</span><span class="font-medium text-gray-900">{{ $sale->repayment_date?->format('d M Y') ?: '-' }}</span></div>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm">
            <div class="flex justify-between py-1"><span class="text-gray-500">Subtotal</span><span>{{ number_format((float) $sale->subtotal, 2) }}</span></div>
            <div class="flex justify-between py-1 text-emerald-700"><span>Item discounts</span><span>−{{ number_format((float) $sale->discount_amount, 2) }}</span></div>
            <div class="flex justify-between py-1"><span class="text-gray-500">Total</span><strong>{{ number_format((float) $sale->total_price, 2) }}</strong></div>
            <div class="flex justify-between py-1"><span class="text-gray-500">Paid</span><span class="font-medium text-emerald-700">{{ number_format((float) $sale->paid_amount, 2) }}</span></div>
            <div class="flex justify-between border-t border-gray-200 pt-2"><span class="text-gray-500">Credit</span><span class="font-semibold text-red-700">{{ number_format((float) $sale->credit_amount, 2) }}</span></div>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3">Quantity</th><th class="px-5 py-3">Price</th><th class="px-5 py-3">Discount</th><th class="px-5 py-3">Net amount</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($sale->items as $item)
                    <tr><td class="px-5 py-4 font-medium text-gray-900">{{ $item->unit?->product?->name ?: 'Unknown product' }}</td><td class="px-5 py-4 text-gray-600">{{ $item->unit?->name ?: 'Unknown unit' }}</td><td class="px-5 py-4">{{ $item->quantity }}</td><td class="px-5 py-4">{{ number_format((float) $item->price, 2) }}</td><td class="px-5 py-4 text-emerald-700">{{ number_format((float) $item->discount_amount, 2) }}<span class="block text-xs">{{ $item->discount_name }}</span></td><td class="px-5 py-4 font-medium">{{ number_format($item->net_total, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@if($sale->status !== 'cancelled' && auth('web')->user()?->hasPermissionTo('sales', 'edit'))
    <div x-show="cancelOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[80] flex items-center justify-center bg-gray-900/55 px-4">
        <div @click.outside="cancelOpen = false" x-transition class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl">
            <div class="text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <i data-lucide="circle-alert" class="h-6 w-6"></i>
                </span>
                <h2 class="mt-4 text-lg font-semibold text-gray-900">Cancel this sale?</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">Stock will be restored and related customer credit will be removed.</p>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button type="button" @click="cancelOpen = false" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Keep sale</button>
                <button type="button" @click="$refs.cancelForm.submit()" style="background-color:#dc2626;color:#ffffff;" class="rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm hover:opacity-90">Cancel sale</button>
            </div>
        </div>
    </div>
@endif
</div>
@endsection
