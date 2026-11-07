<div
    x-data="{
        syncFlatpickr(from, to) {
            if ($refs.fromInput && $refs.fromInput._flatpickr) {
                $refs.fromInput._flatpickr.setDate(from, false);
            }
            if ($refs.toInput && $refs.toInput._flatpickr) {
                $refs.toInput._flatpickr.setDate(to, false);
            }
        }
    }"
    x-init="window.lucide?.createIcons()"
    x-on:date-range-updated.window="syncFlatpickr($event.detail.from, $event.detail.to)"
>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Sales Report</h1>
            <p class="mt-1 text-sm text-gray-500">Track and analyze daily, weekly, and monthly sales performance, cash flow, and top-selling items.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="setToday"
                wire:loading.attr="disabled"
                class="pos-button-secondary text-xs"
            >
                Today
            </button>
            <button
                type="button"
                wire:click="setThisWeek"
                wire:loading.attr="disabled"
                class="pos-button-secondary text-xs"
            >
                This week
            </button>
            <button
                type="button"
                wire:click="setThisMonth"
                wire:loading.attr="disabled"
                class="pos-button-secondary text-xs"
            >
                This month
            </button>
            <button
                type="button"
                wire:click="setLastMonth"
                wire:loading.attr="disabled"
                class="pos-button-secondary text-xs"
            >
                Last month
            </button>
            <button
                type="button"
                wire:click="setLast90Days"
                wire:loading.attr="disabled"
                class="pos-button-secondary text-xs"
            >
                Last 90 days
            </button>
            <button
                type="button"
                wire:click="exportCsv"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#465fff]/20 disabled:opacity-50"
            >
                <i data-lucide="download" class="h-3.5 w-3.5 text-gray-500"></i>
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv">Exporting...</span>
            </button>
        </div>
    </div>

    {{-- Filter & Period Selector Panel --}}
    <section class="pos-panel mb-6 p-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Period Mode Switcher --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">View By</label>
                    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                        <button
                            type="button"
                            wire:click="setPeriodMode('daily')"
                            class="rounded-md px-3 py-1 text-xs font-medium transition {{ $periodMode === 'daily' ? 'bg-white text-[#465fff] shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
                        >
                            Daily
                        </button>
                        <button
                            type="button"
                            wire:click="setPeriodMode('weekly')"
                            class="rounded-md px-3 py-1 text-xs font-medium transition {{ $periodMode === 'weekly' ? 'bg-white text-[#465fff] shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
                        >
                            Weekly
                        </button>
                        <button
                            type="button"
                            wire:click="setPeriodMode('monthly')"
                            class="rounded-md px-3 py-1 text-xs font-medium transition {{ $periodMode === 'monthly' ? 'bg-white text-[#465fff] shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
                        >
                            Monthly
                        </button>
                    </div>
                </div>

                {{-- From Date Picker --}}
                <div wire:ignore>
                    <label for="sales_from" class="mb-1 block text-xs font-medium text-gray-500">From date</label>
                    <input
                        type="date"
                        id="sales_from"
                        wire:model.live="from"
                        x-ref="fromInput"
                        data-picker="custom"
                        placeholder="From date"
                        onclick="this.showPicker?.()"
                        x-init="const ph = $el.getAttribute('placeholder') || 'From date';
                        if (window.flatpickr) {
                            if ($el._flatpickr) $el._flatpickr.destroy();
                            flatpickr($el, {
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd/m/Y',
                                altInputPlaceholder: ph,
                                altInputClass: 'rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15',
                                allowInput: true,
                                defaultDate: $wire.from || null,
                                onReady: (_, __, instance) => {
                                    if (instance.altInput) instance.altInput.placeholder = ph;
                                },
                                onClose: (_, __, instance) => {
                                    if (!instance.input.value && instance.altInput) instance.altInput.placeholder = ph;
                                },
                                onChange: (dates, dateStr) => {
                                    $wire.set('from', dateStr);
                                }
                            });
                        }"
                        x-on:change="$wire.set('from', $event.target.value)"
                        x-on:input="$wire.set('from', $event.target.value)"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                    >
                </div>

                {{-- To Date Picker --}}
                <div wire:ignore>
                    <label for="sales_to" class="mb-1 block text-xs font-medium text-gray-500">To date</label>
                    <input
                        type="date"
                        id="sales_to"
                        wire:model.live="to"
                        x-ref="toInput"
                        data-picker="custom"
                        placeholder="To date"
                        onclick="this.showPicker?.()"
                        x-init="const ph = $el.getAttribute('placeholder') || 'To date';
                        if (window.flatpickr) {
                            if ($el._flatpickr) $el._flatpickr.destroy();
                            flatpickr($el, {
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd/m/Y',
                                altInputPlaceholder: ph,
                                altInputClass: 'rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15',
                                allowInput: true,
                                defaultDate: $wire.to || null,
                                onReady: (_, __, instance) => {
                                    if (instance.altInput) instance.altInput.placeholder = ph;
                                },
                                onClose: (_, __, instance) => {
                                    if (!instance.input.value && instance.altInput) instance.altInput.placeholder = ph;
                                },
                                onChange: (dates, dateStr) => {
                                    $wire.set('to', dateStr);
                                }
                            });
                        }"
                        x-on:change="$wire.set('to', $event.target.value)"
                        x-on:input="$wire.set('to', $event.target.value)"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                    >
                </div>

                {{-- Cashier Filter --}}
                <div>
                    <label for="seller_filter" class="mb-1 block text-xs font-medium text-gray-500">Cashier</label>
                    <select
                        id="seller_filter"
                        wire:model.live="sellerId"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                    >
                        <option value="">All Cashiers</option>
                        @foreach ($sellers as $seller)
                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer Filter --}}
                <div>
                    <label for="customer_filter" class="mb-1 block text-xs font-medium text-gray-500">Customer</label>
                    <select
                        id="customer_filter"
                        wire:model.live="customerId"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                    >
                        <option value="">All Customers</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span wire:loading class="text-xs font-medium text-[#465fff]">
                    Updating report...
                </span>
                <div class="text-xs text-gray-500">
                    Period: <span class="font-medium text-gray-900">{{ $fromDate->format('d M Y') }}</span> — <span class="font-medium text-gray-900">{{ $toDate->format('d M Y') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Key KPI Summary Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Sales --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Total Sales</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ number_format($salesTotal, 0) }} <span class="text-xs font-normal text-gray-500">MMK</span></p>
            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2 text-xs text-gray-500">
                <span>Paid: <strong class="text-emerald-600">{{ number_format($paidTotal, 0) }}</strong></span>
                <span>Credit: <strong class="text-amber-600">{{ number_format($creditTotal, 0) }}</strong></span>
            </div>
        </div>

        {{-- Total Invoices --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Completed Orders</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <i data-lucide="receipt" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ number_format($invoiceCount) }} <span class="text-xs font-normal text-gray-500">orders</span></p>
            <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400">Total completed customer transactions</p>
        </div>

        {{-- Total Units Sold --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Units Sold</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i data-lucide="package-check" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ number_format($quantity) }} <span class="text-xs font-normal text-gray-500">units</span></p>
            <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400">Items deducted from store stock</p>
        </div>

        {{-- Average Order Value --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Avg. Order Value</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i data-lucide="calculator" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ number_format($averageOrderValue, 0) }} <span class="text-xs font-normal text-gray-500">MMK</span></p>
            <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400">Average revenue per order</p>
        </div>
    </div>

    {{-- Tabs (Period Breakdown vs Top Products) --}}
    <div class="mb-4 border-b border-gray-200">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            <button
                type="button"
                wire:click="setActiveTab('breakdown')"
                class="inline-flex items-center gap-2 border-b-2 py-3 text-sm font-medium transition {{ $activeTab === 'breakdown' ? 'border-[#465fff] text-[#465fff]' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
            >
                <i data-lucide="calendar" class="h-4 w-4"></i>
                <span>{{ ucfirst($periodMode) }} Breakdown ({{ count($breakdown) }})</span>
            </button>
            <button
                type="button"
                wire:click="setActiveTab('products')"
                class="inline-flex items-center gap-2 border-b-2 py-3 text-sm font-medium transition {{ $activeTab === 'products' ? 'border-[#465fff] text-[#465fff]' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
            >
                <i data-lucide="star" class="h-4 w-4"></i>
                <span>Top Selling Products ({{ count($topProducts) }})</span>
            </button>
        </nav>
    </div>

    {{-- Tab 1: Period Breakdown Table --}}
    @if ($activeTab === 'breakdown')
        <div class="pos-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3.5">Period / Date</th>
                            <th class="px-5 py-3.5 text-right">Invoices</th>
                            <th class="px-5 py-3.5 text-right">Units Sold</th>
                            <th class="px-5 py-3.5 text-right">Total Sales (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Paid (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Credit (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Avg Order (MMK)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($breakdown as $row)
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="px-5 py-4 font-medium text-gray-900">
                                    {{ $row['label'] }}
                                </td>
                                <td class="px-5 py-4 text-right font-medium text-gray-700">
                                    {{ number_format($row['count']) }}
                                </td>
                                <td class="px-5 py-4 text-right font-medium text-gray-700">
                                    {{ number_format($row['qty']) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-900">
                                    {{ number_format($row['total'], 0) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-emerald-600">
                                    {{ number_format($row['paid'], 0) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold {{ $row['credit'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">
                                    {{ number_format($row['credit'], 0) }}
                                </td>
                                <td class="px-5 py-4 text-right text-gray-600">
                                    {{ number_format($row['avg_order'], 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                        <i data-lucide="inbox" class="h-6 w-6"></i>
                                    </div>
                                    <p class="mt-3 text-sm font-medium text-gray-600">No completed sales recorded for this period.</p>
                                    <p class="mt-1 text-xs text-gray-400">Try adjusting your date filters or cashier selection.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($breakdown) > 0)
                        <tfoot class="border-t-2 border-gray-200 bg-gray-50/75 text-xs font-semibold text-gray-900">
                            <tr>
                                <td class="px-5 py-3.5 uppercase tracking-wider">Total Summary</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($invoiceCount) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($quantity) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($salesTotal, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right text-emerald-600">{{ number_format($paidTotal, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right text-amber-600">{{ number_format($creditTotal, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($averageOrderValue, 0) }} MMK</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif

    {{-- Tab 2: Top Products Table --}}
    @if ($activeTab === 'products')
        <div class="pos-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3.5">Rank</th>
                            <th class="px-5 py-3.5">Product Name</th>
                            <th class="px-5 py-3.5">Code / SKU</th>
                            <th class="px-5 py-3.5">Unit</th>
                            <th class="px-5 py-3.5 text-right">Units Sold</th>
                            <th class="px-5 py-3.5 text-right">Revenue (MMK)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($topProducts as $index => $item)
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="px-5 py-4 font-semibold text-gray-500">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-800' : ($index === 1 ? 'bg-slate-200 text-slate-800' : ($index === 2 ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-600')) }} text-xs font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-medium text-gray-900">
                                    {{ $item['product_name'] }}
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-gray-500">
                                    {{ $item['code'] }}
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $item['unit_name'] }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-800">
                                    {{ number_format($item['quantity']) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-900">
                                    {{ number_format($item['revenue'], 0) }} MMK
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                        <i data-lucide="inbox" class="h-6 w-6"></i>
                                    </div>
                                    <p class="mt-3 text-sm font-medium text-gray-600">No product sales found for this period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
