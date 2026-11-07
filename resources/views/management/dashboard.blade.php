@extends('layouts.management')
@section('title', 'Operations Dashboard')

@section('content')
{{-- Dashboard Header --}}
<div class="mb-7 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Operations &amp; Retail Dashboard</h1>
        <span class="sr-only">Operations dashboard</span>
        <p class="mt-1 text-sm text-gray-500">Live operational overview, inventory valuation health, and daily revenue metrics.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        <a href="{{ route('management.reports.inventory-valuation') }}" class="pos-button-secondary text-xs flex items-center gap-1.5">
            <i data-lucide="calculator" class="h-4 w-4"></i>
            Inventory Valuation
        </a>
        <a href="{{ route('management.sales.create') }}" class="pos-button text-xs flex items-center gap-1.5">
            <i data-lucide="plus" class="h-4 w-4"></i>
            New Sale
        </a>
    </div>
</div>

{{-- Top Row: Core Financial & Asset KPIs --}}
<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    {{-- Total Sales Revenue --}}
    <div class="pos-panel p-5 border-l-4 border-l-brand-500">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Sales</p>
            <span class="rounded-lg bg-brand-50 p-2 text-brand-600"><i data-lucide="circle-dollar-sign" class="h-4 w-4"></i></span>
        </div>
        <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($salesTotal, 2) }}</p>
        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
            <span>Today: <strong class="text-brand-600">{{ number_format($todaySales, 2) }}</strong> ({{ $todayOrdersCount }} orders)</span>
        </div>
    </div>

    {{-- Total Purchases --}}
    <div class="pos-panel p-5 border-l-4 border-l-blue-500">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Purchases</p>
            <span class="rounded-lg bg-blue-50 p-2 text-blue-600"><i data-lucide="truck" class="h-4 w-4"></i></span>
        </div>
        <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($purchaseTotal, 2) }}</p>
        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
            <span>Supplier purchasing expenditures</span>
        </div>
    </div>

    {{-- Inventory Cost Valuation --}}
    <div class="pos-panel p-5 border-l-4 border-l-emerald-500">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Stock Valuation (Cost)</p>
            <span class="rounded-lg bg-emerald-50 p-2 text-emerald-600"><i data-lucide="boxes" class="h-4 w-4"></i></span>
        </div>
        <p class="mt-3 text-2xl font-bold text-emerald-700">{{ number_format($stockValue, 2) }}</p>
        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
            <span>Retail value: <strong>{{ number_format($stockRetailValue, 2) }}</strong></span>
            <a href="{{ route('management.reports.inventory-valuation') }}" class="font-medium text-brand-600 hover:underline">Details &rarr;</a>
        </div>
    </div>

    {{-- Total Expenses --}}
    <div class="pos-panel p-5 border-l-4 border-l-amber-500">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Store Expenses</p>
            <span class="rounded-lg bg-amber-50 p-2 text-amber-600"><i data-lucide="receipt" class="h-4 w-4"></i></span>
        </div>
        <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($expenseTotal, 2) }}</p>
        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
            <span>Operating overheads</span>
            <a href="{{ route('management.expenses.index') }}" class="font-medium text-brand-600 hover:underline">View &rarr;</a>
        </div>
    </div>
</div>

{{-- Secondary Row: Quick Health Summary Bar --}}
<div class="mt-5 grid gap-4 sm:grid-cols-3">
    <div class="pos-panel flex items-center gap-4 p-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
            <i data-lucide="layers" class="h-6 w-6"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Registered SKU Variants</p>
            <p class="text-lg font-bold text-gray-900">{{ $totalSkus }} <span class="text-xs font-normal text-gray-400">variants</span></p>
        </div>
    </div>

    <div class="pos-panel flex items-center gap-4 p-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
            <i data-lucide="package-check" class="h-6 w-6"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Total Units in Stock</p>
            <p class="text-lg font-bold text-gray-900">{{ number_format($totalItemsInStock) }} <span class="text-xs font-normal text-gray-400">units available</span></p>
        </div>
    </div>

    <div class="pos-panel flex items-center gap-4 p-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
            <i data-lucide="alert-octagon" class="h-6 w-6"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Out of Stock Variants</p>
            <p class="text-lg font-bold text-red-600">{{ $outOfStockCount }} <span class="text-xs font-normal text-gray-400">need restock</span></p>
        </div>
    </div>
</div>

{{-- Main Content Section: Low Stock Alerts & Recent Sales & Top Products --}}
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    {{-- Low Stock Items Section (1 Column) --}}
    <section class="pos-panel flex flex-col overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Low stock alert</h2>
                <p class="mt-0.5 text-xs text-gray-500">Items at or below reorder level</p>
            </div>
            <a href="{{ route('management.stock.index', ['mode' => 'reorder']) }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all</a>
        </div>
        <div class="flex-1 divide-y divide-gray-100">
            @forelse($lowStockUnits as $unit)
                <div class="flex items-center justify-between px-5 py-3 text-sm hover:bg-gray-50/50">
                    <div class="min-w-0 pr-3">
                        <span class="block truncate font-medium text-gray-800">{{ $unit->product?->name }}</span>
                        <span class="block text-xs text-gray-400">{{ $unit->name }} · {{ $unit->code ?: '-' }}</span>
                    </div>
                    <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                        {{ $unit->current_quantity }} / {{ $unit->reorder_quantity }}
                    </span>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-gray-500">
                    <i data-lucide="check-circle" class="mx-auto mb-2 h-7 w-7 text-emerald-400"></i>
                    All stock units are above reorder threshold.
                </div>
            @endforelse
        </div>
    </section>

    {{-- Top Fast-Moving Products (1 Column) --}}
    <section class="pos-panel flex flex-col overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Fast-moving items</h2>
                <p class="mt-0.5 text-xs text-gray-500">Top sellers by sold quantity</p>
            </div>
            <a href="{{ route('management.reports.sales') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Sales report</a>
        </div>
        <div class="flex-1 divide-y divide-gray-100">
            @forelse($topProducts as $item)
                <div class="flex items-center justify-between px-5 py-3 text-sm hover:bg-gray-50/50">
                    <div class="min-w-0 pr-3">
                        <span class="block truncate font-medium text-gray-800">{{ $item->unit?->product?->name }}</span>
                        <span class="block text-xs text-gray-400">{{ $item->unit?->name }}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold text-gray-900">{{ (int) $item->total_qty }} sold</span>
                        <span class="block text-xs text-emerald-600">{{ number_format((float) $item->total_revenue, 2) }}</span>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-gray-500">
                    <i data-lucide="shopping-bag" class="mx-auto mb-2 h-7 w-7 text-gray-300"></i>
                    No sales data recorded yet.
                </div>
            @endforelse
        </div>
    </section>

    {{-- Recent Sales Receipts (1 Column) --}}
    <section class="pos-panel flex flex-col overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Recent vouchers</h2>
                <p class="mt-0.5 text-xs text-gray-500">Latest completed checkout slips</p>
            </div>
            <a href="{{ route('management.sales.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all</a>
        </div>
        <div class="flex-1 divide-y divide-gray-100">
            @forelse($recentSales as $sale)
                <a href="{{ route('management.sales.show', $sale) }}" class="group flex items-center gap-3 px-5 py-3 transition hover:bg-brand-50/40">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-500 group-hover:bg-brand-100">
                        <i data-lucide="receipt" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-semibold text-gray-800 group-hover:text-brand-600">{{ $sale->voucher_code }}</span>
                        <span class="mt-0.5 block truncate text-[11px] text-gray-500">{{ $sale->customer?->name ?: 'Walk-in' }} · {{ $sale->sale_date?->format('d M') }}</span>
                    </span>
                    <span class="text-xs font-bold text-gray-900">{{ number_format((float)$sale->total_price, 2) }}</span>
                    <i data-lucide="chevron-right" class="h-4 w-4 text-gray-400 group-hover:text-brand-500"></i>
                </a>
            @empty
                <div class="px-5 py-12 text-center text-sm text-gray-500">
                    <i data-lucide="receipt" class="mx-auto mb-2 h-7 w-7 text-gray-300"></i>
                    No recent sales.
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
