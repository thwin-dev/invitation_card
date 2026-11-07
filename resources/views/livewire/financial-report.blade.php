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
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Financial report</h1>
            <p class="mt-1 text-sm text-gray-500">Compare sales, customer payments, purchases, and expenses for a selected period.</p>
        </div>
        <div class="flex flex-wrap gap-2">
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
                wire:click="setToday"
                wire:loading.attr="disabled"
                class="pos-button-secondary text-xs"
            >
                Today
            </button>
        </div>
    </div>

    {{-- Filter Bar --}}
    <section class="pos-panel mb-6 p-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <div wire:ignore>
                    <label for="from" class="mb-1 block text-xs font-medium text-gray-500">From date</label>
                    <input
                        type="date"
                        id="from"
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
                <div wire:ignore>
                    <label for="to" class="mb-1 block text-xs font-medium text-gray-500">To date</label>
                    <input
                        type="date"
                        id="to"
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
            </div>
            <div class="flex items-center gap-3">
                <span wire:loading class="text-xs font-medium text-brand-600">
                    Updating report...
                </span>
                <div class="text-xs text-gray-500">
                    Period: <span class="font-medium text-gray-900">{{ $fromDate->format('d M Y') }}</span> — <span class="font-medium text-gray-900">{{ $toDate->format('d M Y') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Key Metrics Grid (Compact) --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Total Sales --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Total Sales</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i data-lucide="shopping-cart" class="h-3.5 w-3.5"></i>
                </span>
            </div>
            <p class="mt-1 text-xl font-bold tracking-tight text-gray-900">{{ number_format((float) $salesTotal, 2) }}</p>
            <div class="mt-2 flex items-center justify-between border-t border-gray-100 pt-1.5 text-[11px] text-gray-500">
                <span>Paid: <strong class="text-emerald-600">{{ number_format((float) $paidTotal, 2) }}</strong></span>
                <span>Credit: <strong class="text-amber-600">{{ number_format((float) $creditTotal, 2) }}</strong></span>
            </div>
        </div>

        {{-- Card 2: Purchases --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Purchases</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <i data-lucide="truck" class="h-3.5 w-3.5"></i>
                </span>
            </div>
            <p class="mt-1 text-xl font-bold tracking-tight text-gray-900">{{ number_format((float) $purchases, 2) }}</p>
            <p class="mt-2 border-t border-gray-100 pt-1.5 text-[11px] text-gray-400">Stock purchase costs</p>
        </div>

        {{-- Card 3: Expenses --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Operating Expenses</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <i data-lucide="receipt" class="h-3.5 w-3.5"></i>
                </span>
            </div>
            <p class="mt-1 text-xl font-bold tracking-tight text-gray-900">{{ number_format((float) $expenses, 2) }}</p>
            <p class="mt-2 border-t border-gray-100 pt-1.5 text-[11px] text-gray-400">Overheads & utilities</p>
        </div>

        {{-- Card 4: Net Result --}}
        <div class="pos-panel p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Net Result</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $net >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                    <i data-lucide="{{ $net >= 0 ? 'trending-up' : 'trending-down' }}" class="h-3.5 w-3.5"></i>
                </span>
            </div>
            <p class="mt-1 text-xl font-bold tracking-tight {{ $net >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $net, 2) }}</p>
            <p class="mt-2 border-t border-gray-100 pt-1.5 text-[11px] text-gray-400">Sales - Purchases - Expenses</p>
        </div>
    </div>

    {{-- Secondary Metrics Grid (Compact) --}}
    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <div class="pos-panel px-4 py-3">
            <span class="text-xs text-gray-500">Customer Payments</span>
            <p class="mt-0.5 text-base font-semibold text-brand-600">{{ number_format((float) $paymentIncome, 2) }}</p>
        </div>
        <div class="pos-panel px-4 py-3">
            <span class="text-xs text-gray-500">Credit Issued</span>
            <p class="mt-0.5 text-base font-semibold text-amber-600">{{ number_format((float) $creditTotal, 2) }}</p>
        </div>
        <div class="pos-panel px-4 py-3">
            <span class="text-xs text-gray-500">Total Vouchers</span>
            <p class="mt-0.5 text-base font-semibold text-gray-900">{{ $sales->count() }}</p>
        </div>
    </div>

    {{-- Sales in Period Table --}}
    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Sales in period</h2>
                <p class="mt-0.5 text-xs text-gray-500">All completed sales transactions between {{ $fromDate->format('d M Y') }} and {{ $toDate->format('d M Y') }}.</p>
            </div>
            <div class="text-sm">
                <span class="text-gray-500">Total:</span>
                <strong class="ml-1 text-gray-900">{{ number_format((float) $salesTotal, 2) }}</strong>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Voucher</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Quantity</th>
                        <th class="px-5 py-3">Paid</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sales as $sale)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4 font-semibold text-gray-900">
                                <a href="{{ route('management.sales.show', $sale) }}" class="text-brand-600 hover:text-brand-700 hover:underline">
                                    {{ $sale->voucher_code }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $sale->sale_date->format('d M Y') }}</td>
                            <td class="px-5 py-4 text-gray-900">{{ $sale->customer?->name ?: 'Walk-in' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $sale->total_quantity }}</td>
                            <td class="px-5 py-4 font-medium text-emerald-600">{{ number_format((float) $sale->paid_amount, 2) }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-gray-900">{{ number_format((float) $sale->total_price, 2) }}</td>
                            <td class="px-5 py-4 text-right">
                                <a
                                    href="{{ route('management.sales.show', $sale) }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600 transition hover:bg-brand-100"
                                    title="View voucher"
                                >
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-500">
                                <i data-lucide="receipt" class="mx-auto mb-2 h-8 w-8 text-gray-300"></i>
                                <p class="font-medium text-gray-600">No sales in this period.</p>
                                <p class="mt-1 text-xs text-gray-400">Try adjusting the date range filters above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
