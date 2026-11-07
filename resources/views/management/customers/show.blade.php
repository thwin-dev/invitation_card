@extends('layouts.management')

@section('title', 'Customer details')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('management.customers.index') }}" class="text-sm font-medium text-brand-500">Back to customers</a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $customer->name }}</h1>
        <p class="mt-2 text-sm text-gray-500">{{ $customer->phone ?: 'No phone' }} · {{ $customer->address ?: 'No address' }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('management.customers.statement', $customer) }}" class="pos-button-secondary"><i data-lucide="file-text" class="mr-2 h-4 w-4"></i>Statement</a>
        @if(auth('web')->user()?->hasPermissionTo('orders', 'create'))<a href="{{ route('management.orders.create', ['customer_id' => $customer->id]) }}" class="pos-button-secondary"><i data-lucide="clipboard-list" class="mr-2 h-4 w-4"></i>New order</a>@endif
        @if(auth('web')->user()?->hasPermissionTo('sales', 'create'))<a href="{{ route('management.sales.create', ['customer_id' => $customer->id]) }}" class="pos-button"><i data-lucide="shopping-cart" class="mr-2 h-4 w-4"></i>New sale</a>@endif
    </div>
</div>

@if($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
@endif

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
    <div class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Customer level</p>
        <p class="mt-2 text-xl font-bold">
            @if($customer->customer_level == 3)
                <span class="text-amber-600">Level 3 (VIP)</span>
            @elseif($customer->customer_level == 2)
                <span class="text-blue-600">Level 2 (Silver)</span>
            @else
                <span class="text-gray-800">Level 1 (Standard)</span>
            @endif
        </p>
    </div>
    <div class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Credit status</p>
        <p class="mt-2">
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $customer->allow_credit ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">{{ $customer->allow_credit ? 'Allowed' : 'Not allowed' }}</span>
        </p>
    </div>
    <div class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Credit Limit</p>
        <p class="mt-2 text-xl font-bold font-mono text-gray-900">
            @if($customer->allow_credit)
                {{ number_format((float) $customer->credit_limit, 2) }}
                @if((float) $customer->credit_limit <= 0)
                    <span class="block text-xs font-normal text-gray-500">Unlimited</span>
                @endif
            @else
                <span class="text-sm font-normal text-gray-400">0.00</span>
            @endif
        </p>
    </div>
    <div class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Outstanding credit</p>
        <p class="mt-2 text-xl font-bold font-mono text-red-600">{{ number_format($creditTotal, 2) }}</p>
    </div>
    <div class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Available credit</p>
        <p class="mt-2 text-xl font-bold font-mono text-emerald-600">
            @if(! $customer->allow_credit)
                <span class="text-sm font-normal text-gray-400">N/A</span>
            @elseif((float) $customer->credit_limit <= 0)
                <span class="text-sm font-normal text-emerald-700">Unlimited</span>
            @else
                {{ number_format($availableCredit, 2) }}
            @endif
        </p>
    </div>
</div>

@if(auth('web')->user()?->hasPermissionTo('customers', 'create'))<section class="pos-panel mt-6 p-5">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Add payment income</h2>
            <p class="mt-1 text-sm text-gray-500">Record partial or full customer repayment.</p>
        </div>
    </div>
    <form method="post" action="{{ route('management.customer-payments.store', $customer) }}" class="grid gap-4 lg:grid-cols-5">
        @csrf
        <label class="block text-sm font-medium text-gray-700 lg:col-span-2">Credit balance
            <select name="credit_record_id" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">General customer payment</option>
                @foreach($credits->whereNull('repayment_date') as $credit)
                    <option value="{{ $credit->id }}">{{ $credit->sale?->voucher_code ?: 'Credit #'.$credit->id }} · Balance {{ number_format((float) $credit->amount, 2) }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm font-medium text-gray-700">Amount
            <input type="number" name="amount" step="0.01" min="0.01" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </label>
        <label class="block text-sm font-medium text-gray-700">Payment date
            <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </label>
        <label class="block text-sm font-medium text-gray-700">Method
            <select name="payment_method" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile">Mobile</option></select>
        </label>
        <label class="block text-sm font-medium text-gray-700 lg:col-span-4">Note
            <input name="note" placeholder="Optional note" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </label>
        <div class="flex items-end"><button class="pos-button w-full"><i data-lucide="receipt" class="mr-2 h-4 w-4"></i>Record payment</button></div>
    </form>
</section>
@endif

<section class="pos-panel mt-6 overflow-hidden">
    <div class="border-b border-gray-100 px-5 py-4"><h2 class="text-lg font-semibold text-gray-900">Payment history</h2></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Method</th><th class="px-5 py-3">Note</th><th class="px-5 py-3 text-right">Receipt</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $payment)
                    <tr><td class="px-5 py-4 text-gray-500">{{ $payment->payment_date->format('Y-m-d') }}</td><td class="px-5 py-4 font-medium">{{ number_format((float) $payment->amount, 2) }}</td><td class="px-5 py-4">{{ ucfirst($payment->payment_method) }}</td><td class="max-w-xs truncate px-5 py-4 text-gray-500">{{ $payment->note ?: '-' }}</td><td class="px-5 py-4 text-right"><a target="_blank" href="{{ route('management.customer-payments.receipt', [$customer, $payment]) }}" class="inline-flex rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100" title="Print receipt"><i data-lucide="printer" class="h-4 w-4"></i></a></td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">No payments recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if($canViewSales)<section class="pos-panel mt-6 overflow-hidden">
    <div class="border-b border-gray-100 px-5 py-4"><h2 class="text-lg font-semibold text-gray-900">Recent sales</h2></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Voucher</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Credit</th><th class="px-5 py-3">Status</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sales as $sale)
                    <tr><td class="px-5 py-4 font-medium"><a href="{{ route('management.sales.show', $sale) }}" class="text-brand-500">{{ $sale->voucher_code }}</a></td><td class="px-5 py-4 text-gray-500">{{ $sale->sale_date?->format('Y-m-d') }}</td><td class="px-5 py-4">{{ number_format((float) $sale->total_price, 2) }}</td><td class="px-5 py-4">{{ number_format((float) $sale->credit_amount, 2) }}</td><td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">{{ ucfirst($sale->status) }}</span></td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">No sales recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
