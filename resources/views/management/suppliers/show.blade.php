@extends('layouts.management')

@section('title', 'Supplier details')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('management.suppliers.index') }}" class="text-sm font-medium text-brand-500 hover:underline">← Back to suppliers</a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $supplier->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            @if($supplier->company_name) <span class="font-medium text-brand-600">{{ $supplier->company_name }}</span> · @endif
            {{ $supplier->phone ?: 'No phone' }} · {{ $supplier->address ?: 'No address' }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if(auth('web')->user()?->hasPermissionTo('purchases', 'create'))<a href="{{ route('management.purchases.create', ['supplier_id' => $supplier->id]) }}" class="pos-button">
            <i data-lucide="truck" class="mr-2 h-4 w-4"></i>New purchase
        </a>
        @endif
    </div>
</div>

@if($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
@endif

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total orders</p>
        <p class="mt-2 text-2xl font-bold text-gray-900">{{ $purchases->count() }}</p>
    </div>
    <div class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total purchased</p>
        <p class="mt-2 text-2xl font-bold font-mono text-gray-900">{{ number_format($totalPurchased, 2) }}</p>
    </div>
    <div class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total paid</p>
        <p class="mt-2 text-2xl font-bold font-mono text-emerald-600">{{ number_format($totalPaid, 2) }}</p>
    </div>
    <div class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Payable balance</p>
        <p class="mt-2 text-2xl font-bold font-mono {{ $payableBalance > 0 ? 'text-red-600' : 'text-gray-800' }}">
            {{ number_format($payableBalance, 2) }}
        </p>
    </div>
</div>

@if(auth('web')->user()?->hasPermissionTo('suppliers', 'create'))<section class="pos-panel mt-6 p-5">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Record supplier payment</h2>
            <p class="mt-1 text-sm text-gray-500">Pay off outstanding purchase balances or record payment voucher.</p>
        </div>
    </div>
    <form method="post" action="{{ route('management.suppliers.payments.store', $supplier) }}" class="grid gap-4 lg:grid-cols-5">
        @csrf
        <label class="block text-sm font-medium text-gray-700 lg:col-span-2">Purchase invoice
            <select name="purchase_id" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">General payment / Any open purchase</option>
                @foreach($unpaidPurchases as $p)
                    <option value="{{ $p->id }}">Purchase #{{ $p->id }} ({{ $p->purchase_date?->format('Y-m-d') }}) · Due {{ number_format((float) $p->payable_amount, 2) }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm font-medium text-gray-700">Amount
            <input type="number" name="amount" step="0.01" min="0.01" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </label>
        <label class="block text-sm font-medium text-gray-700">Payment date
            <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </label>
        <label class="block text-sm font-medium text-gray-700">Method
            <select name="payment_method" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="mobile">Mobile</option>
            </select>
        </label>
        <label class="block text-sm font-medium text-gray-700 lg:col-span-4">Note
            <input name="note" placeholder="Payment reference or note" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </label>
        <div class="flex items-end">
            <button class="pos-button w-full">
                <i data-lucide="receipt" class="mr-2 h-4 w-4"></i>Submit payment
            </button>
        </div>
    </form>
</section>
@endif

<section class="pos-panel mt-6 overflow-hidden">
    <div class="border-b border-gray-100 px-5 py-4">
        <h2 class="text-lg font-semibold text-gray-900">Payment history</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Invoice</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                    <th class="px-5 py-3">Method</th>
                    <th class="px-5 py-3">Note</th>
                    <th class="px-5 py-3">Paid by</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $pmt)
                    <tr>
                        <td class="px-5 py-4 text-gray-600">{{ $pmt->payment_date?->format('Y-m-d') }}</td>
                        <td class="px-5 py-4">
                            @if($pmt->purchase && auth('web')->user()?->hasPermissionTo('purchases', 'view'))
                                <a href="{{ route('management.purchases.show', $pmt->purchase) }}" class="font-medium text-brand-600 hover:underline">
                                    Purchase #{{ $pmt->purchase->id }}
                                </a>
                            @elseif(! $pmt->purchase)
                                <span class="text-gray-400">General</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right font-mono font-semibold text-gray-900">{{ number_format((float) $pmt->amount, 2) }}</td>
                        <td class="px-5 py-4"><span class="capitalize">{{ $pmt->payment_method }}</span></td>
                        <td class="max-w-xs truncate px-5 py-4 text-gray-500">{{ $pmt->note ?: '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $pmt->payer?->name ?: 'System' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No payment records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="pos-panel mt-6 overflow-hidden">
    <div class="border-b border-gray-100 px-5 py-4">
        <h2 class="text-lg font-semibold text-gray-900">Purchase order history</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3">Purchase #</th>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3 text-center">Items</th>
                    <th class="px-5 py-3 text-right">Total Price</th>
                    <th class="px-5 py-3 text-right">Paid</th>
                    <th class="px-5 py-3 text-right">Payable</th>
                    <th class="px-5 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($purchases as $p)
                    <tr>
                        <td class="px-5 py-4 font-medium">
                            @if(auth('web')->user()?->hasPermissionTo('purchases', 'view'))<a href="{{ route('management.purchases.show', $p) }}" class="text-brand-600 hover:underline">
                                #{{ $p->id }}
                            </a>@else #{{ $p->id }}@endif
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $p->purchase_date?->format('Y-m-d') }}</td>
                        <td class="px-5 py-4 text-center">{{ $p->total_quantity }}</td>
                        <td class="px-5 py-4 text-right font-mono font-medium text-gray-900">{{ number_format((float) $p->total_price, 2) }}</td>
                        <td class="px-5 py-4 text-right font-mono text-emerald-600">{{ number_format((float) $p->paid_amount, 2) }}</td>
                        <td class="px-5 py-4 text-right font-mono {{ (float) $p->payable_amount > 0 ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                            {{ number_format((float) $p->payable_amount, 2) }}
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if((float) $p->payable_amount <= 0)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Paid</span>
                            @elseif((float) $p->paid_amount > 0)
                                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Partial</span>
                            @else
                                <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700">Unpaid</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">No purchases recorded for this supplier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
