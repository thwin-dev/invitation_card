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
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Purchase &amp; Supplier Report</h1>
            <p class="mt-1 text-sm text-gray-500">Track and analyze purchases, supplier payables, inventory intake, and supplier transaction history.</p>
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

    {{-- Filter Panel --}}
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
                    <label for="purchase_from_date" class="mb-1 block text-xs font-medium text-gray-500">From date</label>
                    <input
                        type="date"
                        id="purchase_from_date"
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
                    <label for="purchase_to_date" class="mb-1 block text-xs font-medium text-gray-500">To date</label>
                    <input
                        type="date"
                        id="purchase_to_date"
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


                {{-- Supplier Filter --}}
                <div>
                    <label for="supplier_filter" class="mb-1 block text-xs font-medium text-gray-500">Supplier</label>
                    <select
                        id="supplier_filter"
                        wire:model.live="supplierId"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                    >
                        <option value="">All Suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">
                                {{ $supplier->name }}{{ $supplier->company_name ? ' (' . $supplier->company_name . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Payment Status Filter --}}
                <div>
                    <label for="status_filter" class="mb-1 block text-xs font-medium text-gray-500">Payment Status</label>
                    <select
                        id="status_filter"
                        wire:model.live="paymentStatus"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                    >
                        <option value="">All Statuses</option>
                        <option value="paid">Fully Paid</option>
                        <option value="partial">Partial</option>
                        <option value="unpaid">Unpaid</option>
                    </select>
                </div>

                {{-- Purchaser Filter --}}
                <div>
                    <label for="purchaser_filter" class="mb-1 block text-xs font-medium text-gray-500">Purchased By</label>
                    <select
                        id="purchaser_filter"
                        wire:model.live="purchaserId"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                    >
                        <option value="">All Staff</option>
                        @foreach ($purchasers as $purchaser)
                            <option value="{{ $purchaser->id }}">{{ $purchaser->name }}</option>
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
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        {{-- Total Purchases --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Total Purchases</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i data-lucide="truck" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold tracking-tight text-gray-900">{{ number_format($totalPurchases, 0) }} <span class="text-xs font-normal text-gray-500">MMK</span></p>
            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2 text-xs text-gray-500">
                <span>Paid: <strong class="text-emerald-600">{{ number_format($totalPaid, 0) }}</strong></span>
                <span>Due: <strong class="text-rose-600">{{ number_format($totalPayable, 0) }}</strong></span>
            </div>
        </div>

        {{-- Purchase Bills Count --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Purchase Bills</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold tracking-tight text-gray-900">{{ number_format($billCount) }} <span class="text-xs font-normal text-gray-500">bills</span></p>
            <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400">Avg bill: {{ number_format($averageBillValue, 0) }} MMK</p>
        </div>

        {{-- Total Units Purchased --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Units Purchased</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i data-lucide="package-plus" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold tracking-tight text-gray-900">{{ number_format($totalUnits) }} <span class="text-xs font-normal text-gray-500">units</span></p>
            <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400">Total inventory stock added</p>
        </div>

        {{-- Outstanding Payables --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Outstanding Payable</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                    <i data-lucide="clock-alert" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold tracking-tight {{ $totalPayable > 0 ? 'text-rose-600' : 'text-gray-900' }}">
                {{ number_format($totalPayable, 0) }} <span class="text-xs font-normal text-gray-500">MMK</span>
            </p>
            <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400">Pending debt to suppliers</p>
        </div>

        {{-- Disbursed Payments --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Payments Disbursed</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i data-lucide="wallet" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold tracking-tight text-gray-900">{{ number_format($paymentsDisbursed, 0) }} <span class="text-xs font-normal text-gray-500">MMK</span></p>
            <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400">Cash paid out in date range</p>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="mb-4 border-b border-gray-200">
        <nav class="-mb-px flex flex-wrap gap-6" aria-label="Tabs">
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
                wire:click="setActiveTab('suppliers')"
                class="inline-flex items-center gap-2 border-b-2 py-3 text-sm font-medium transition {{ $activeTab === 'suppliers' ? 'border-[#465fff] text-[#465fff]' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
            >
                <i data-lucide="users" class="h-4 w-4"></i>
                <span>Supplier Summary ({{ count($supplierBreakdown) }})</span>
            </button>
            <button
                type="button"
                wire:click="setActiveTab('products')"
                class="inline-flex items-center gap-2 border-b-2 py-3 text-sm font-medium transition {{ $activeTab === 'products' ? 'border-[#465fff] text-[#465fff]' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
            >
                <i data-lucide="package" class="h-4 w-4"></i>
                <span>Purchased Products ({{ count($topProducts) }})</span>
            </button>
            <button
                type="button"
                wire:click="setActiveTab('invoices')"
                class="inline-flex items-center gap-2 border-b-2 py-3 text-sm font-medium transition {{ $activeTab === 'invoices' ? 'border-[#465fff] text-[#465fff]' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
            >
                <i data-lucide="receipt" class="h-4 w-4"></i>
                <span>Purchase Bills ({{ count($purchases) }})</span>
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
                            <th class="px-5 py-3.5 text-right">Bills</th>
                            <th class="px-5 py-3.5 text-right">Units Purchased</th>
                            <th class="px-5 py-3.5 text-right">Total Purchases (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Paid (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Payable (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Avg Bill (MMK)</th>
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
                                <td class="px-5 py-4 text-right font-semibold {{ $row['payable'] > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                    {{ number_format($row['payable'], 0) }}
                                </td>
                                <td class="px-5 py-4 text-right text-gray-600">
                                    {{ number_format($row['avg_bill'], 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                        <i data-lucide="inbox" class="h-6 w-6"></i>
                                    </div>
                                    <p class="mt-3 text-sm font-medium text-gray-600">No purchases recorded for this period.</p>
                                    <p class="mt-1 text-xs text-gray-400">Try adjusting your date filters or supplier selection.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($breakdown) > 0)
                        <tfoot class="border-t-2 border-gray-200 bg-gray-50/75 text-xs font-semibold text-gray-900">
                            <tr>
                                <td class="px-5 py-3.5 uppercase tracking-wider">Total Summary</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($billCount) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($totalUnits) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($totalPurchases, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right text-emerald-600">{{ number_format($totalPaid, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right text-rose-600">{{ number_format($totalPayable, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($averageBillValue, 0) }} MMK</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif

    {{-- Tab 2: Supplier Breakdown Table --}}
    @if ($activeTab === 'suppliers')
        <div class="pos-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3.5">Supplier</th>
                            <th class="px-5 py-3.5">Contact / Phone</th>
                            <th class="px-5 py-3.5 text-right">Bills</th>
                            <th class="px-5 py-3.5 text-right">Units</th>
                            <th class="px-5 py-3.5 text-right">Purchased (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Paid (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Payable (MMK)</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($supplierBreakdown as $row)
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-gray-900">{{ $row['name'] }}</div>
                                    @if ($row['company'])
                                        <div class="text-xs text-gray-400">{{ $row['company'] }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs text-gray-600">
                                    {{ $row['phone'] ?: '—' }}
                                </td>
                                <td class="px-5 py-4 text-right font-medium text-gray-700">
                                    {{ number_format($row['bills_count']) }}
                                </td>
                                <td class="px-5 py-4 text-right font-medium text-gray-700">
                                    {{ number_format($row['units_count']) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-900">
                                    {{ number_format($row['total_purchased'], 0) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-emerald-600">
                                    {{ number_format($row['total_paid'], 0) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold {{ $row['total_payable'] > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                    {{ number_format($row['total_payable'], 0) }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if ($row['status'] === 'paid')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                            Fully Paid
                                        </span>
                                    @elseif ($row['status'] === 'partial')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                                            Partial Due
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700">
                                            Unpaid Due
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($row['supplier_id'])
                                        <a
                                            href="{{ route('management.suppliers.show', $row['supplier_id']) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 hover:text-[#465fff]"
                                        >
                                            <span>Ledger</span>
                                            <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-12 text-center text-gray-400">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                        <i data-lucide="users" class="h-6 w-6"></i>
                                    </div>
                                    <p class="mt-3 text-sm font-medium text-gray-600">No supplier purchase activity found for this period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($supplierBreakdown) > 0)
                        <tfoot class="border-t-2 border-gray-200 bg-gray-50/75 text-xs font-semibold text-gray-900">
                            <tr>
                                <td colspan="2" class="px-5 py-3.5 uppercase tracking-wider">Total Summary</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($billCount) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($totalUnits) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($totalPurchases, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right text-emerald-600">{{ number_format($totalPaid, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right text-rose-600">{{ number_format($totalPayable, 0) }} MMK</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif

    {{-- Tab 3: Top Purchased Products Table --}}
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
                            <th class="px-5 py-3.5 text-right">Units Purchased</th>
                            <th class="px-5 py-3.5 text-right">Total Spend (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Avg Unit Cost (MMK)</th>
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
                                    {{ number_format($item['total_cost'], 0) }} MMK
                                </td>
                                <td class="px-5 py-4 text-right text-gray-600">
                                    {{ number_format($item['avg_cost'], 0) }} MMK
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                        <i data-lucide="inbox" class="h-6 w-6"></i>
                                    </div>
                                    <p class="mt-3 text-sm font-medium text-gray-600">No product item data found for this period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Tab 4: Purchase Bills / Invoices Table --}}
    @if ($activeTab === 'invoices')
        <div class="pos-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3.5">Bill #</th>
                            <th class="px-5 py-3.5">Date</th>
                            <th class="px-5 py-3.5">Supplier</th>
                            <th class="px-5 py-3.5">Purchaser</th>
                            <th class="px-5 py-3.5 text-right">Quantity</th>
                            <th class="px-5 py-3.5 text-right">Total (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Paid (MMK)</th>
                            <th class="px-5 py-3.5 text-right">Payable (MMK)</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($purchases as $p)
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="px-5 py-4 font-mono text-xs font-semibold text-[#465fff]">
                                    #PO-{{ str_pad((string) $p->id, 5, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-5 py-4 text-xs text-gray-700">
                                    {{ $p->purchase_date ? \Illuminate\Support\Carbon::parse($p->purchase_date)->format('d M Y') : '—' }}
                                </td>
                                <td class="px-5 py-4 font-medium text-gray-900">
                                    {{ $p->supplier?->name ?? $p->supplier_name }}
                                </td>
                                <td class="px-5 py-4 text-xs text-gray-600">
                                    {{ $p->buyer?->name ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-right font-medium text-gray-700">
                                    {{ number_format($p->total_quantity) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-900">
                                    {{ number_format((float) $p->total_price, 0) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-emerald-600">
                                    {{ number_format((float) $p->paid_amount, 0) }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold {{ (float) $p->payable_amount > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                    {{ number_format((float) $p->payable_amount, 0) }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @php $st = strtolower($p->payment_status ?: 'paid'); @endphp
                                    @if ($st === 'paid')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                            Paid
                                        </span>
                                    @elseif ($st === 'partial')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                                            Partial
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700">
                                            Unpaid
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a
                                        href="{{ route('management.purchases.show', $p->id) }}"
                                        class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 hover:text-[#465fff]"
                                    >
                                        <span>View</span>
                                        <i data-lucide="eye" class="h-3 w-3"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-5 py-12 text-center text-gray-400">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                        <i data-lucide="receipt" class="h-6 w-6"></i>
                                    </div>
                                    <p class="mt-3 text-sm font-medium text-gray-600">No purchase invoices found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($purchases->count() > 0)
                        <tfoot class="border-t-2 border-gray-200 bg-gray-50/75 text-xs font-semibold text-gray-900">
                            <tr>
                                <td colspan="4" class="px-5 py-3.5 uppercase tracking-wider">Total ({{ $purchases->count() }} bills)</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($totalUnits) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ number_format($totalPurchases, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right text-emerald-600">{{ number_format($totalPaid, 0) }} MMK</td>
                                <td class="px-5 py-3.5 text-right text-rose-600">{{ number_format($totalPayable, 0) }} MMK</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif
</div>
