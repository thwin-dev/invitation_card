<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-500">Store overview</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">Operations dashboard</h1>
            <p class="mt-2 text-sm text-gray-500">Sales and stock for {{ $branch?->name ?: 'all shops' }} · {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth('web')->user()?->hasPermissionTo('purchases', 'create'))<a href="{{ route('management.purchases.create') }}" class="pos-button-secondary"><i data-lucide="truck" class="mr-2 h-4 w-4"></i>New purchase</a>@endif
            @if(auth('web')->user()?->hasPermissionTo('sales', 'create'))<a href="{{ route('management.sales.create') }}" class="pos-button"><i data-lucide="plus" class="mr-2 h-4 w-4"></i>New sale</a>@endif
        </div>
    </div>

    <section class="pos-panel mb-5 p-4 sm:p-5" aria-label="Dashboard filters">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-wrap items-end gap-3">
                <label class="block text-xs font-medium text-gray-600">
                    Shop
                    <select wire:model.live="branchId" class="mt-1 block h-10 min-w-48 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        @forelse($branches as $shop)
                            <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                        @empty
                            <option value="">All shops</option>
                        @endforelse
                    </select>
                </label>
                <label class="block text-xs font-medium text-gray-600">
                    From
                    <input type="date" data-native-date wire:model.live="fromDate" class="mt-1 block h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                    @error('fromDate') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-xs font-medium text-gray-600">
                    To
                    <input type="date" data-native-date wire:model.live="toDate" class="mt-1 block h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                    @error('toDate') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ([['today', 'Today'], ['7days', 'Last 7 days'], ['month', 'This month'], ['last_month', 'Last month']] as [$period, $label])
                    <button type="button" wire:click="setPeriod('{{ $period }}')" wire:loading.attr="disabled" class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ $activePreset === $period ? 'bg-brand-500 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">{{ $label }}</button>
                @endforeach
                <span wire:loading role="status" class="ml-1 inline-flex items-center gap-2 text-xs font-medium text-brand-600"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-500"></span>Updating…</span>
            </div>
        </div>
        @if (! $branch)
            <p class="mt-3 text-xs text-gray-500">Create or activate a shop to filter sales and stock by location.</p>
        @endif
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Sales summary">
        <article class="pos-panel p-5">
            <div class="flex items-center justify-between"><p class="text-sm text-gray-500">Sales</p><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i data-lucide="banknote" class="h-5 w-5"></i></span></div>
            <p class="mt-4 text-2xl font-semibold tabular-nums text-gray-900">{{ number_format((float) ($salesMetrics->total_sales ?? 0), 2) }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $currencyCode }} · selected period</p>
        </article>
        <article class="pos-panel p-5">
            <div class="flex items-center justify-between"><p class="text-sm text-gray-500">Transactions</p><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i data-lucide="receipt-text" class="h-5 w-5"></i></span></div>
            <p class="mt-4 text-2xl font-semibold tabular-nums text-gray-900">{{ number_format((int) ($salesMetrics->transaction_count ?? 0)) }}</p>
            <p class="mt-1 text-xs text-gray-400">Completed sales</p>
        </article>
        <article class="pos-panel p-5">
            <div class="flex items-center justify-between"><p class="text-sm text-gray-500">Units sold</p><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><i data-lucide="package-check" class="h-5 w-5"></i></span></div>
            <p class="mt-4 text-2xl font-semibold tabular-nums text-gray-900">{{ number_format((int) ($salesMetrics->quantity_sold ?? 0)) }}</p>
            <p class="mt-1 text-xs text-gray-400">Across completed sales</p>
        </article>
        <article class="pos-panel p-5">
            <div class="flex items-center justify-between"><p class="text-sm text-gray-500">Average sale</p><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><i data-lucide="chart-no-axes-combined" class="h-5 w-5"></i></span></div>
            <p class="mt-4 text-2xl font-semibold tabular-nums text-gray-900">{{ number_format((float) ($salesMetrics->average_sale ?? 0), 2) }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $currencyCode }} per transaction</p>
        </article>
    </section>

    <section class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Business summary">
        @foreach ([
            ['Paid on selected sales', (float) ($salesMetrics->paid_total ?? 0), $currencyCode, 'circle-check', 'text-emerald-700', 'bg-emerald-50', 'Sales recorded in this period'],
            ['Unpaid credit', (float) ($salesMetrics->credit_total ?? 0), $currencyCode, 'clock-3', 'text-amber-700', 'bg-amber-50', 'Selected shop · sales in period'],
            ['Customer repayments', $customerPayments, 'MMK', 'hand-coins', 'text-teal-700', 'bg-teal-50', 'All shops · received in this period'],
            ['Purchases', $purchaseTotal, 'MMK', 'truck', 'text-blue-700', 'bg-blue-50', 'All shops · selected period'],
            ['Operating expenses', $expenseTotal, 'MMK', 'receipt', 'text-rose-700', 'bg-rose-50', 'All shops · selected period'],
        ] as [$label, $value, $currency, $icon, $tone, $iconBg, $note])
            <article class="pos-panel p-4">
                <div class="flex items-center justify-between"><p class="text-xs font-medium text-gray-500">{{ $label }}</p><span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $iconBg }} {{ $tone }}"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span></div>
                <p class="mt-2 text-xl font-semibold tabular-nums text-gray-900">{{ number_format($value, 2) }} <span class="text-xs font-medium text-gray-400">{{ $currency }}</span></p>
                <p class="mt-1 text-[11px] text-gray-400">{{ $note }}</p>
            </article>
        @endforeach
    </section>

    @if($canViewBackups)
        <section class="pos-panel mt-5 flex flex-wrap items-center justify-between gap-4 px-5 py-4" aria-label="Backup status">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><i data-lucide="database" class="h-5 w-5"></i></span>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Data backups</h2>
                    @if($latestBackup)
                        <p class="mt-1 text-xs text-gray-500">Last successful backup · {{ $latestBackup->created_at?->format('d M Y, H:i') }}</p>
                    @else
                        <p class="mt-1 text-xs text-gray-500">No successful backup has been created yet.</p>
                    @endif
                    @if($latestBackupAttempt?->status === 'failed')
                        <p class="mt-1 text-xs font-medium text-red-600">Latest attempt failed. Review backup history.</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <span class="text-xs text-gray-500">
                    @if($backupScheduleEnabled)
                        Next scheduled run · {{ $nextBackupAt->format('d M Y, H:i') }}
                    @else
                        Scheduled backups are disabled.
                    @endif
                </span>
                <a href="{{ route('management.backups.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Manage backups <i data-lucide="arrow-up-right" class="inline h-4 w-4"></i></a>
            </div>
        </section>
    @endif

    <section class="pos-panel mt-5 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div><h2 class="font-semibold text-gray-900">Sales trend</h2><p class="mt-1 text-xs text-gray-500">{{ $trendCaption }}</p></div>
            <a href="{{ route('management.reports.financial') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700">Open reports <i data-lucide="arrow-up-right" class="h-4 w-4"></i></a>
        </div>
        @if((int) ($salesMetrics->transaction_count ?? 0) > 0)
            <div class="overflow-x-auto px-5 py-5">
                <div class="flex h-48 min-w-[560px] items-end gap-3">
                    @foreach($salesTrend as $point)
                        @php $barHeight = max(6, (int) round(((float) $point->total_sales / $maxTrendValue) * 100)); @endphp
                        <div class="flex h-full min-w-12 flex-1 flex-col items-center justify-end gap-2" wire:key="dashboard-trend-{{ $point->sales_day }}">
                            <span class="text-[10px] font-semibold tabular-nums text-gray-500">{{ number_format((float) $point->total_sales, 0) }}</span>
                            <div class="w-full rounded-t-md bg-brand-500/90 transition-all" style="height: {{ $barHeight }}%" role="img" aria-label="{{ \Illuminate\Support\Carbon::parse($point->sales_day)->format('d M') }} sales {{ number_format((float) $point->total_sales, 2) }} {{ $currencyCode }}"></div>
                            <span class="text-[10px] font-medium text-gray-500">{{ \Illuminate\Support\Carbon::parse($point->sales_day)->format('D j') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="px-5 py-12 text-center text-sm text-gray-500">No completed sales in this date range.</div>
        @endif
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-2">
        <article class="pos-panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div><h2 class="font-semibold text-gray-900">Stock alerts</h2><p class="mt-1 text-xs text-gray-500">{{ $branch?->name ?: 'Main stock' }} · purchase-cost value {{ number_format($stockValue, 2) }} {{ $currencyCode }}</p></div>
                <a href="{{ route('management.stock.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Manage stock <i data-lucide="arrow-up-right" class="inline h-4 w-4"></i></a>
            </div>
            <div class="flex gap-2 border-b border-gray-100 px-5 py-3 text-xs">
                <span class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700">{{ number_format($lowStockCount) }} at/below reorder</span>
                <span class="rounded-full bg-red-50 px-2.5 py-1 font-semibold text-red-700">{{ number_format($outOfStockCount) }} out of stock</span>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($lowStockUnits as $unit)
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                        <div class="min-w-0"><p class="truncate text-sm font-semibold text-gray-800">{{ $unit->product?->name ?: 'Product' }}</p><p class="mt-0.5 truncate text-xs text-gray-500">{{ $unit->name }}</p></div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ (int) $unit->dashboard_quantity < 1 ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">{{ number_format((int) $unit->dashboard_quantity) }} / {{ number_format((int) $unit->reorder_quantity) }}</span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">No units are at or below their reorder level.</div>
                @endforelse
            </div>
        </article>

        <article class="pos-panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div><h2 class="font-semibold text-gray-900">Expiry alerts</h2><p class="mt-1 text-xs text-gray-500">Tracked lots with stock remaining</p></div>
                <a href="{{ route('management.inventory.operations') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Inventory operations <i data-lucide="arrow-up-right" class="inline h-4 w-4"></i></a>
            </div>
            <div class="flex gap-2 border-b border-gray-100 px-5 py-3 text-xs">
                <span class="rounded-full bg-red-50 px-2.5 py-1 font-semibold text-red-700">{{ number_format($expiredLotCount) }} expired lots</span>
                <span class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700">{{ number_format($expiringLotCount) }} due within 30 days</span>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($expiryLots as $lot)
                    @php $isExpired = $lot->expires_at?->lt(today()); @endphp
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                        <div class="min-w-0"><p class="truncate text-sm font-semibold text-gray-800">{{ $lot->unit?->product?->name ?: 'Product' }} · {{ $lot->unit?->name ?: 'Unit' }}</p><p class="mt-0.5 truncate text-xs text-gray-500">{{ $lot->branch?->name }}{{ $lot->batch_number ? ' · Batch '.$lot->batch_number : '' }}</p></div>
                        <div class="shrink-0 text-right"><span class="block text-xs font-semibold {{ $isExpired ? 'text-red-700' : 'text-amber-700' }}">{{ $isExpired ? 'Expired' : $lot->expires_at?->format('d M Y') }}</span><span class="mt-0.5 block text-[11px] text-gray-500">Qty {{ number_format((int) $lot->quantity) }}</span></div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">No tracked lots are expired or due within 30 days.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-2">
        <article class="pos-panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div><h2 class="font-semibold text-gray-900">Customer receivables</h2><p class="mt-1 text-xs text-gray-500">Unpaid credit for {{ $branch?->name ?: 'all shops' }}</p></div>
                <a href="{{ route('management.customers.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Customers <i data-lucide="arrow-up-right" class="inline h-4 w-4"></i></a>
            </div>
            <div class="flex items-end justify-between gap-4 px-5 py-4">
                <div><p class="text-2xl font-semibold tabular-nums text-gray-900">{{ number_format($receivableTotal, 2) }} <span class="text-xs font-medium text-gray-400">{{ $currencyCode }}</span></p><p class="mt-1 text-xs text-gray-500">{{ number_format($receivableCustomerCount) }} customers with an open balance</p></div>
                <a href="{{ route('management.reports.financial') }}" class="text-xs font-semibold text-brand-600 hover:underline">Financial report</a>
            </div>
            <div class="divide-y divide-gray-100 border-t border-gray-100">
                @forelse($customerDebts as $customer)
                    <a href="{{ route('management.customers.show', $customer) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50">
                        <span class="truncate text-sm font-medium text-gray-800">{{ $customer->name }}</span>
                        <span class="shrink-0 text-sm font-semibold tabular-nums text-amber-700">{{ number_format((float) $customer->outstanding_debt, 2) }} {{ $currencyCode }}</span>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500">No customer credit is outstanding.</p>
                @endforelse
            </div>
        </article>

        <article class="pos-panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div><h2 class="font-semibold text-gray-900">Supplier payables</h2><p class="mt-1 text-xs text-gray-500">Open purchase balances · all shops</p></div>
                <a href="{{ route('management.suppliers.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Suppliers <i data-lucide="arrow-up-right" class="inline h-4 w-4"></i></a>
            </div>
            <div class="flex items-end justify-between gap-4 px-5 py-4">
                <div><p class="text-2xl font-semibold tabular-nums text-gray-900">{{ number_format($payableTotal, 2) }} <span class="text-xs font-medium text-gray-400">MMK</span></p><p class="mt-1 text-xs text-red-600">{{ number_format($overduePayableTotal, 2) }} overdue</p></div>
                <a href="{{ route('management.purchases.index') }}" class="text-xs font-semibold text-brand-600 hover:underline">Purchase history</a>
            </div>
            <div class="divide-y divide-gray-100 border-t border-gray-100">
                @forelse($payables as $purchase)
                    <a href="{{ route('management.purchases.show', $purchase) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50">
                        <span class="min-w-0"><span class="block truncate text-sm font-medium text-gray-800">{{ $purchase->supplier?->name ?: $purchase->supplier_name }}</span><span class="mt-0.5 block text-xs text-gray-500">Due {{ $purchase->payment_due_date?->format('d M Y') ?: 'date not set' }}</span></span>
                        <span class="shrink-0 text-sm font-semibold tabular-nums text-red-700">{{ number_format((float) $purchase->payable_amount, 2) }}</span>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500">No supplier balances are outstanding.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="pos-panel mt-5 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div><h2 class="font-semibold text-gray-900">Shop performance</h2><p class="mt-1 text-xs text-gray-500">Each shop is shown in its configured currency for this period.</p></div>
            <a href="{{ route('management.reports.financial', ['tab' => 'branches']) }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Branch report <i data-lucide="arrow-up-right" class="inline h-4 w-4"></i></a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Shop</th><th class="px-5 py-3 text-right">Sales</th><th class="px-5 py-3 text-right">Revenue</th><th class="px-5 py-3 text-right">Collected</th><th class="px-5 py-3 text-right">Credit</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($branchPerformance as $row)
                        <tr wire:key="dashboard-shop-{{ $row->branch->id }}" class="{{ (int) $branchId === $row->branch->id ? 'bg-brand-50/40' : '' }}">
                            <td class="px-5 py-3.5 font-semibold text-gray-800">{{ $row->branch->name }}</td>
                            <td class="px-5 py-3.5 text-right tabular-nums text-gray-600">{{ number_format($row->transaction_count) }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold tabular-nums text-gray-900">{{ number_format($row->total_sales, 2) }} <span class="text-[10px] font-medium text-gray-400">{{ $row->branch->currency_code ?: 'MMK' }}</span></td>
                            <td class="px-5 py-3.5 text-right tabular-nums text-emerald-700">{{ number_format($row->paid_total, 2) }} <span class="text-[10px] text-gray-400">{{ $row->branch->currency_code ?: 'MMK' }}</span></td>
                            <td class="px-5 py-3.5 text-right tabular-nums text-amber-700">{{ number_format($row->credit_total, 2) }} <span class="text-[10px] text-gray-400">{{ $row->branch->currency_code ?: 'MMK' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">No active shops are available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,1fr)]">
        <article class="pos-panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div><h2 class="font-semibold text-gray-900">Recent activity</h2><p class="mt-1 text-xs text-gray-500">Sales, purchases, expenses, stock movements, and open orders in this period</p></div>
                <span class="text-xs text-gray-400">{{ $recentActivities->count() }} items</span>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recentActivities as $activity)
                    <a href="{{ $activity['url'] }}" wire:key="dashboard-activity-{{ $activity['key'] }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-gray-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-500"><i data-lucide="{{ $activity['icon'] }}" class="h-4 w-4"></i></span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-gray-800">{{ $activity['label'] }}</span><span class="mt-0.5 block truncate text-xs text-gray-500">{{ $activity['detail'] }} · {{ $activity['date'] ?: 'Date not set' }}</span></span>
                        @if(! is_null($activity['amount'])) <span class="shrink-0 text-sm font-semibold tabular-nums text-gray-800">{{ number_format($activity['amount'], 2) }}</span> @endif
                        <i data-lucide="chevron-right" class="h-4 w-4 shrink-0 text-gray-300"></i>
                    </a>
                @empty
                    <div class="px-5 py-12 text-center text-sm text-gray-500">No activity in this date range.</div>
                @endforelse
            </div>
        </article>

        <div class="space-y-5">
            <article class="pos-panel p-5" x-data="{ online: navigator.onLine, pendingCount: 0, async refreshPending() { if (window.OfflinePos) this.pendingCount = await window.OfflinePos.getPendingSalesCount(); }, async init() { await this.refreshPending(); window.addEventListener('pending-sales-updated', () => this.refreshPending()); window.addEventListener('pos-network-status', event => { this.online = event.detail.isOnline; this.refreshPending(); }); window.addEventListener('online', () => { this.online = true; this.refreshPending(); }); window.addEventListener('offline', () => this.online = false); } }">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="font-semibold text-gray-900">Offline sales</h2><p class="mt-1 text-xs text-gray-500">Queue is stored in this browser</p></div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="online ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
                        <span class="h-1.5 w-1.5 rounded-full" :class="online ? 'bg-emerald-500' : 'bg-red-500'"></span><span x-text="online ? 'Online' : 'Offline'"></span>
                    </span>
                </div>
                <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <p class="text-2xl font-semibold tabular-nums text-gray-900" x-text="pendingCount"></p>
                    <p class="mt-1 text-xs text-gray-500" x-text="pendingCount === 1 ? 'sale waiting to sync' : 'sales waiting to sync'"></p>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3 text-xs">
                    <span class="text-gray-500">{{ number_format($offlineSyncedCount) }} offline-created sales in this period are synced</span>
                    @if(auth('web')->user()?->hasPermissionTo('sales', 'create'))<a href="{{ route('management.sales.create') }}" class="shrink-0 font-semibold text-brand-600 hover:underline">Open POS queue</a>@endif
                </div>
                <p class="mt-2 text-[11px] text-gray-400">Pending sales are specific to this browser. Open the POS to review and sync them.</p>
            </article>

            <article class="pos-panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4"><div><h2 class="font-semibold text-gray-900">Orders to follow up</h2><p class="mt-1 text-xs text-gray-500">All pending, confirmed, or changed orders</p></div><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">{{ number_format($openOrderCount) }}</span></div>
                <div class="divide-y divide-gray-100">
                    @forelse($openOrders as $order)
                        <a href="{{ route('management.orders.show', $order) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50"><span class="min-w-0"><span class="block truncate text-sm font-semibold text-gray-800">{{ $order->order_number }}</span><span class="mt-0.5 block truncate text-xs text-gray-500">{{ $order->customer?->name ?: 'Customer' }} · {{ $order->order_date?->format('d M Y') }}</span></span><span class="rounded-full bg-gray-100 px-2 py-1 text-[10px] font-semibold uppercase text-gray-600">{{ $order->status }}</span></a>
                    @empty
                        <p class="px-5 py-7 text-center text-sm text-gray-500">No open orders in this date range.</p>
                    @endforelse
                </div>
                <a href="{{ route('management.orders.index') }}" class="block border-t border-gray-100 px-5 py-3 text-center text-xs font-semibold text-brand-600 hover:bg-gray-50">View all orders</a>
            </article>

            <article class="pos-panel p-5">
                <h2 class="font-semibold text-gray-900">Quick actions</h2>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    @if(auth('web')->user()?->hasPermissionTo('sales', 'create'))<a href="{{ route('management.sales.create') }}" class="pos-button justify-center"><i data-lucide="plus" class="mr-1.5 h-4 w-4"></i>New sale</a>@endif
                    @if(auth('web')->user()?->hasPermissionTo('purchases', 'create'))<a href="{{ route('management.purchases.create') }}" class="pos-button-secondary justify-center"><i data-lucide="truck" class="mr-1.5 h-4 w-4"></i>Purchase</a>@endif
                    @if(auth('web')->user()?->hasPermissionTo('inventory', 'edit'))<a href="{{ route('management.inventory.operations') }}" class="pos-button-secondary justify-center"><i data-lucide="boxes" class="mr-1.5 h-4 w-4"></i>Stock action</a>@endif
                    @if(auth('web')->user()?->hasPermissionTo('expenses', 'view'))<a href="{{ route('management.expenses.index') }}" class="pos-button-secondary justify-center"><i data-lucide="wallet-cards" class="mr-1.5 h-4 w-4"></i>Expenses</a>@endif
                </div>
            </article>
        </div>
    </section>
</div>
