@extends('layouts.management')

@section('title', 'Purchase Details')

@section('content')
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('management.purchases.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition hover:text-brand-700">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to purchases
            </a>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900">Purchase #{{ $purchase->id }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $purchase->supplier_name }} · {{ $purchase->purchase_date?->format('d M Y') ?: '-' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('management.purchases.print', $purchase) }}" target="_blank" class="pos-button-secondary">
                <i data-lucide="printer" class="mr-2 h-4 w-4"></i>
                Print
            </a>
            @if(auth('web')->user()?->hasPermissionTo('purchases', 'edit'))<a href="{{ route('management.purchases.edit', $purchase) }}" class="pos-button-secondary">
                <i data-lucide="pencil" class="mr-2 h-4 w-4"></i>
                Edit
            </a>
            @endif
            @if(auth('web')->user()?->hasPermissionTo('purchases', 'delete'))<form method="post" action="{{ route('management.purchases.destroy', $purchase) }}" onsubmit="return confirm('Delete this purchase and restore stock?')">
                @csrf
                @method('delete')
                <button class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                    <i data-lucide="trash-2" class="mr-2 h-4 w-4"></i>
                    Delete
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div
            x-data
            x-init="$dispatch('pos-toast', { type: 'success', title: 'Success', message: @js(session('status')) })"
        ></div>
    @endif

    <section class="pos-panel overflow-hidden">
        <div class="grid gap-6 border-b border-gray-100 p-5 lg:grid-cols-[1fr_260px]">
            <div class="grid gap-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <span class="block text-gray-500">Supplier</span>
                    @if($purchase->supplier && auth('web')->user()?->hasPermissionTo('suppliers', 'view'))
                        <a href="{{ route('management.suppliers.show', $purchase->supplier) }}" class="mt-1 inline-block font-semibold text-brand-600 hover:underline">
                            {{ $purchase->supplier_name }}
                        </a>
                    @else
                        <strong class="mt-1 block text-gray-900">{{ $purchase->supplier_name }}</strong>
                    @endif
                </div>
                <div>
                    <span class="block text-gray-500">Purchase date</span>
                    <strong class="mt-1 block text-gray-900">{{ $purchase->purchase_date?->format('d M Y') ?: '-' }}</strong>
                </div>
                <div>
                    <span class="block text-gray-500">Recorded by</span>
                    <strong class="mt-1 block text-gray-900">{{ $purchase->buyer?->name ?: '-' }}</strong>
                </div>
                <div>
                    <span class="block text-gray-500">Quantity</span>
                    <strong class="mt-1 block text-gray-900">{{ $purchase->total_quantity }}</strong>
                </div>
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Total amount</span>
                    <strong class="font-mono">{{ number_format((float) $purchase->total_price, 2) }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Paid amount</span>
                    <strong class="font-mono text-emerald-600">{{ number_format((float) $purchase->paid_amount, 2) }}</strong>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-1.5">
                    <span class="text-gray-500">Payable balance</span>
                    <strong class="font-mono {{ (float) $purchase->payable_amount > 0 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ number_format((float) $purchase->payable_amount, 2) }}
                    </strong>
                </div>
                <div class="flex justify-between items-center border-t border-gray-200 pt-1.5">
                    <span class="text-gray-500">Status</span>
                    @if((float) $purchase->payable_amount <= 0)
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Paid</span>
                    @elseif((float) $purchase->paid_amount > 0)
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">Partial</span>
                    @else
                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Unpaid</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3">Quantity</th>
                        <th class="px-5 py-3">Price</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($purchase->items as $item)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ $item->unit?->product?->name ?: 'Unknown product' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $item->unit?->name ?: 'Unknown unit' }}</td>
                            <td class="px-5 py-4">{{ $item->quantity }}</td>
                            <td class="px-5 py-4">{{ number_format((float) $item->price, 2) }}</td>
                            <td class="px-5 py-4 text-right font-medium">{{ number_format((float) $item->price * $item->quantity, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">No purchase items recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
