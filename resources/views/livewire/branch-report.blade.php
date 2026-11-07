<div class="space-y-5">
    <section class="pos-panel p-4 sm:p-5">
        <div class="mb-4">
            <h2 class="font-semibold text-gray-900">Branch sales</h2>
            <p class="mt-1 text-sm text-gray-500">Compare completed sales and collected payments across shops.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(12rem,1.2fr)_minmax(10rem,0.8fr)_minmax(10rem,0.8fr)_auto] lg:items-end">
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Shop</span>
                <select wire:model.live="branchId" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <option value="">All shops</option>
                    @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">From</span>
                <input type="date" wire:model.live="from" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">To</span>
                <input type="date" wire:model.live="to" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </label>
            <button type="button" wire:click="resetFilters" class="pos-button-secondary"><i data-lucide="rotate-ccw" class="mr-2 h-4 w-4"></i>Reset</button>
        </div>
        <div wire:loading role="status" class="mt-3 inline-flex items-center gap-2 text-xs font-medium text-brand-600">
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-500"></span>Updating report…
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Branch report totals">
        @foreach([
            ['label' => 'Completed sales', 'value' => number_format($saleCount), 'icon' => 'receipt-text', 'tone' => 'bg-brand-50 text-brand-600'],
            ['label' => 'Units sold', 'value' => number_format($quantity), 'icon' => 'package', 'tone' => 'bg-sky-50 text-sky-600'],
            ['label' => 'Sales total', 'value' => number_format($totalSales, 2), 'icon' => 'banknote', 'tone' => 'bg-indigo-50 text-indigo-600'],
            ['label' => 'Collected', 'value' => number_format($paidTotal, 2), 'icon' => 'circle-check', 'tone' => 'bg-emerald-50 text-emerald-600'],
            ['label' => 'Credit due', 'value' => number_format($creditTotal, 2), 'icon' => 'clock-3', 'tone' => 'bg-amber-50 text-amber-600'],
        ] as $metric)
            <article class="pos-panel flex items-start gap-3 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $metric['tone'] }}"><i data-lucide="{{ $metric['icon'] }}" class="h-5 w-5"></i></span>
                <div class="min-w-0"><p class="text-xs font-medium text-gray-500">{{ $metric['label'] }}</p><p class="mt-1 truncate text-lg font-bold tabular-nums text-gray-900">{{ $metric['value'] }}</p></div>
            </article>
        @endforeach
    </section>

    <section class="pos-panel overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Sales by shop</h2>
            <p class="mt-1 text-xs text-gray-500">Amounts use each shop’s configured currency.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead><tr><th>Shop</th><th class="text-right">Sales</th><th class="text-right">Units</th><th class="text-right">Sales total</th><th class="text-right">Collected</th><th class="text-right">Credit due</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr wire:key="branch-report-{{ $row->branch->id }}">
                            <td><div class="flex min-w-44 items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600"><i data-lucide="store" class="h-4 w-4"></i></span><span class="font-semibold text-gray-900">{{ $row->branch->name }}</span></div></td>
                            <td class="text-right tabular-nums">{{ number_format($row->sale_count) }}</td>
                            <td class="text-right tabular-nums">{{ number_format($row->quantity) }}</td>
                            <td class="text-right"><span class="font-semibold tabular-nums text-gray-900">{{ number_format($row->total_sales, 2) }}</span><span class="ml-1 text-xs text-gray-500">{{ $row->branch->currency_code ?: 'MMK' }}</span></td>
                            <td class="text-right"><span class="font-medium tabular-nums text-emerald-700">{{ number_format($row->paid_total, 2) }}</span><span class="ml-1 text-xs text-gray-500">{{ $row->branch->currency_code ?: 'MMK' }}</span></td>
                            <td class="text-right"><span class="font-medium tabular-nums {{ $row->credit_total > 0 ? 'text-amber-700' : 'text-gray-500' }}">{{ number_format($row->credit_total, 2) }}</span><span class="ml-1 text-xs text-gray-500">{{ $row->branch->currency_code ?: 'MMK' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="!py-14 text-center"><span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-400"><i data-lucide="chart-no-axes-column-increasing" class="h-6 w-6"></i></span><p class="font-semibold text-gray-800">No shops match this report</p><p class="mt-1 text-xs text-gray-500">Choose another shop or date range.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
