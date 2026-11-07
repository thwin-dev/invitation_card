<div x-data="posSaleTable()">
    <section class="pos-panel overflow-hidden">
        <div class="border-b border-gray-100 p-4 sm:p-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="font-semibold text-gray-900">Sales</h2><p class="mt-1 text-xs text-gray-500">Search sales and filter by date, shop, or status.</p></div>
                <span wire:loading role="status" class="inline-flex items-center gap-2 text-xs font-medium text-brand-600"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-500"></span>Updating sales…</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(14rem,1.5fr)_minmax(9rem,0.8fr)_minmax(9rem,0.8fr)_minmax(10rem,0.9fr)_minmax(10rem,0.9fr)_auto] xl:items-end">
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span><span class="relative block"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i><input wire:model.live.debounce.400ms="search" placeholder="Voucher or customer" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></span></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">From</span><input type="date" wire:model.live="from" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">To</span><input type="date" wire:model.live="to" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Shop</span><select wire:model.live="branchId" aria-label="Filter sales by shop" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"><option value="">All shops</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span><select wire:model.live="status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"><option value="">All statuses</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></label>
                @if($search || $from || $to || $status || $branchId)<button type="button" wire:click="resetFilters" class="pos-button-secondary whitespace-nowrap"><i data-lucide="rotate-ccw" class="mr-2 h-4 w-4"></i>Clear</button>@endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Voucher</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Shop</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Quantity</th>
                        <th class="px-5 py-3">Total</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    {{-- Offline Pending Sales (Loaded from local browser IndexedDB) --}}
                    <template x-for="os in offlinePendingSales" :key="os.client_transaction_id">
                        <tr class="bg-amber-50/50 hover:bg-amber-50/80 transition">
                            <td class="px-5 py-4 font-medium text-gray-900">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-amber-900" x-text="os.voucher_code"></span>
                                    <span class="inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 ring-1 ring-amber-600/30">
                                        <i data-lucide="cloud-off" class="h-3 w-3"></i>
                                        Offline
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-600" x-text="new Date(os.offline_created_at || os.sale_date).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })"></td>
                            <td class="px-5 py-4 text-xs font-medium text-gray-700" x-text="os.branch_name_snapshot || 'Main'"></td>
                            <td class="px-5 py-4 text-xs font-semibold text-gray-800" x-text="os.customer_name || 'Walk-in customer'"></td>
                            <td class="px-5 py-4 text-xs font-mono" x-text="os.items ? os.items.reduce((s, it) => s + (parseInt(it.qty) || 0), 0) : 0"></td>
                            <td class="px-5 py-4 font-mono text-xs font-bold text-amber-900" x-text="Number(os.total_price || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })"></td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Offline</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    @click="printOfflineReceipt(os)"
                                    class="inline-flex rounded-lg bg-amber-100 p-2 text-amber-800 hover:bg-amber-200 transition"
                                    title="Print offline voucher"
                                >
                                    <i data-lucide="printer" class="h-4 w-4"></i>
                                </button>
                            </td>
                        </tr>
                    </template>

                    {{-- Server Sales Records --}}
                    @forelse($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ $sale->voucher_code }}</td>
                            <td class="px-5 py-4 text-gray-500">{{ $sale->sale_date?->format('d M Y') }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $sale->branch?->name ?? $sale->branch_name_snapshot ?? 'Main' }}</td>
                            <td class="px-5 py-4">{{ $sale->customer?->name ?: 'Walk-in customer' }}</td>
                            <td class="px-5 py-4">{{ $sale->total_quantity }}</td>
                            <td class="px-5 py-4 font-medium">{{ number_format((float) $sale->total_price, 2) }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $sale->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">{{ ucfirst($sale->status) }}</span></td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('management.sales.show', $sale) }}" class="inline-flex rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100" title="View sale"><i data-lucide="eye" class="h-4 w-4"></i></a>
                            </td>
                        </tr>
                    @empty
                        <template x-if="offlinePendingSales.length === 0">
                            <tr><td colspan="8" class="px-5 py-12 text-center"><span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-400"><i data-lucide="receipt-text" class="h-6 w-6"></i></span><p class="font-semibold text-gray-800">No sales found</p><p class="mt-1 text-xs text-gray-500">Try another search or filter.</p></td></tr>
                        </template>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $sales->links() }}</div>
    </section>
</div>
