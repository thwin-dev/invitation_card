<div x-data x-init="window.lucide?.createIcons()">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Inventory Valuation &amp; Health</h1>
            <p class="mt-1 text-sm text-gray-500">Real-time asset valuation at purchase cost, retail value, wholesale potential, and category margins.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="exportCsv"
                wire:loading.attr="disabled"
                class="pos-button-secondary text-xs flex items-center gap-1.5"
            >
                <i data-lucide="download" class="h-3.5 w-3.5"></i>
                Export CSV
            </button>
            <button
                type="button"
                wire:click="resetFilters"
                wire:loading.attr="disabled"
                class="pos-button-secondary text-xs flex items-center gap-1.5"
            >
                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                Reset filters
            </button>
        </div>
    </div>

    {{-- Top Metrics KPI Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Cost Valuation --}}
        <div class="pos-panel p-5 border-l-4 border-l-brand-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Cost Valuation (Asset)</span>
                <span class="rounded-lg bg-brand-50 p-2 text-brand-600"><i data-lucide="wallet" class="h-4 w-4"></i></span>
            </div>
            <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($totalValuationCost, 2) }}</p>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                <span>Total purchase capital tied</span>
                <span class="font-medium text-gray-700">{{ $totalQuantityInStock }} units</span>
            </div>
        </div>

        {{-- Total Retail Valuation --}}
        <div class="pos-panel p-5 border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Retail Valuation</span>
                <span class="rounded-lg bg-emerald-50 p-2 text-emerald-600"><i data-lucide="badge-dollar-sign" class="h-4 w-4"></i></span>
            </div>
            <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($totalValuationRetail, 2) }}</p>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                <span>Wholesale: {{ number_format($totalValuationWholesale, 2) }}</span>
                <span class="font-medium text-emerald-600 font-semibold">+{{ $overallMarginPercent }}%</span>
            </div>
        </div>

        {{-- Potential Profit --}}
        <div class="pos-panel p-5 border-l-4 border-l-blue-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Potential Gross Profit</span>
                <span class="rounded-lg bg-blue-50 p-2 text-blue-600"><i data-lucide="trending-up" class="h-4 w-4"></i></span>
            </div>
            <p class="mt-3 text-2xl font-bold text-blue-700">{{ number_format($totalPotentialProfit, 2) }}</p>
            <div class="mt-2 text-xs text-gray-500">
                Estimated margin upon 100% stock liquidation
            </div>
        </div>

        {{-- Stock Health Status --}}
        <div class="pos-panel p-5 border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Stock Health Alerts</span>
                <span class="rounded-lg bg-amber-50 p-2 text-amber-600"><i data-lucide="alert-triangle" class="h-4 w-4"></i></span>
            </div>
            <div class="mt-3 flex items-baseline gap-3">
                <span class="text-2xl font-bold text-amber-600">{{ $lowStockCount }}</span>
                <span class="text-xs text-amber-700 font-medium">Low stock</span>
                <span class="text-gray-300">|</span>
                <span class="text-2xl font-bold text-red-600">{{ $outOfStockCount }}</span>
                <span class="text-xs text-red-700 font-medium">Out of stock</span>
            </div>
            <div class="mt-2 text-xs text-gray-500">
                Across {{ $totalUnitsTracked }} registered product variants
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <section class="pos-panel mb-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-1 flex-wrap items-center gap-3">
                {{-- Search Bar --}}
                <div class="min-w-[240px] flex-1">
                    <label for="inv-search" class="mb-1 block text-xs font-medium text-gray-500">Search</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i data-lucide="search" class="h-4 w-4"></i>
                        </span>
                        <input
                            id="inv-search"
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Search by name, SKU, or barcode..."
                            class="w-full rounded-lg border border-gray-300 pl-9 pr-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                        >
                    </div>
                </div>

                {{-- Category Filter --}}
                <div class="w-48">
                    <label for="inv-category" class="mb-1 block text-xs font-medium text-gray-500">Category</label>
                    <select
                        id="inv-category"
                        wire:model.live="categoryId"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Stock Status Filter --}}
                <div class="w-44">
                    <label for="inv-status" class="mb-1 block text-xs font-medium text-gray-500">Status</label>
                    <select
                        id="inv-status"
                        wire:model.live="stockStatus"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="in_stock">In Stock (&gt; 0)</option>
                        <option value="low_stock">Low Stock (At or Below Reorder)</option>
                        <option value="out_of_stock">Out of Stock (&le; 0)</option>
                    </select>
                </div>
            </div>

            {{-- Quick Filter Tags --}}
            <div class="flex items-center gap-2 pt-5">
                <button
                    type="button"
                    wire:click="$set('stockStatus', 'low_stock')"
                    class="rounded-full px-3 py-1 text-xs font-medium transition {{ $stockStatus === 'low_stock' ? 'bg-amber-100 text-amber-800 ring-2 ring-amber-500' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    Low Stock ({{ $lowStockCount }})
                </button>
                <button
                    type="button"
                    wire:click="$set('stockStatus', 'out_of_stock')"
                    class="rounded-full px-3 py-1 text-xs font-medium transition {{ $stockStatus === 'out_of_stock' ? 'bg-red-100 text-red-800 ring-2 ring-red-500' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    Out of Stock ({{ $outOfStockCount }})
                </button>
            </div>
        </div>
    </section>

    {{-- Category Valuation Breakdown Section --}}
    @if ($categoryBreakdown->isNotEmpty() && !$categoryId)
        <div class="pos-panel mb-6 overflow-hidden">
            <div class="border-b border-gray-100 bg-gray-50/50 px-5 py-3">
                <h3 class="text-sm font-semibold text-gray-900">Valuation by Category</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs">
                    <thead class="bg-gray-50 uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-2.5">Category</th>
                            <th class="px-5 py-2.5 text-center">Variants</th>
                            <th class="px-5 py-2.5 text-right">In Stock Qty</th>
                            <th class="px-5 py-2.5 text-right">Cost Value</th>
                            <th class="px-5 py-2.5 text-right">Retail Value</th>
                            <th class="px-5 py-2.5 text-right">Potential Margin</th>
                            <th class="px-5 py-2.5 text-right">Margin %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($categoryBreakdown as $row)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-2.5 font-medium text-gray-900">{{ $row['category'] }}</td>
                                <td class="px-5 py-2.5 text-center text-gray-600">{{ $row['sku_count'] }}</td>
                                <td class="px-5 py-2.5 text-right font-medium text-gray-700">{{ number_format($row['total_quantity']) }}</td>
                                <td class="px-5 py-2.5 text-right font-semibold text-gray-900">{{ number_format($row['valuation_cost'], 2) }}</td>
                                <td class="px-5 py-2.5 text-right text-emerald-600">{{ number_format($row['valuation_retail'], 2) }}</td>
                                <td class="px-5 py-2.5 text-right text-blue-600">{{ number_format($row['potential_profit'], 2) }}</td>
                                <td class="px-5 py-2.5 text-right font-medium text-gray-700">{{ $row['margin_percent'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Detailed Inventory Table --}}
    <section class="pos-panel overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h3 class="font-semibold text-gray-900">Product Variants Valuation Table</h3>
                <p class="mt-0.5 text-xs text-gray-500">Click column headers to sort by valuation or stock levels</p>
            </div>
            <div wire:loading class="text-xs text-brand-600 font-medium flex items-center gap-1.5">
                <i data-lucide="loader" class="h-3.5 w-3.5 animate-spin"></i>
                Updating...
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 cursor-pointer select-none hover:text-gray-700" wire:click="setSort('name')">
                            <div class="flex items-center gap-1">
                                Product / Variant
                                @if ($sortBy === 'name')
                                    <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-3.5 w-3.5 text-brand-600"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-5 py-3 cursor-pointer select-none hover:text-gray-700 text-center" wire:click="setSort('current_quantity')">
                            <div class="flex items-center justify-center gap-1">
                                Current Stock
                                @if ($sortBy === 'current_quantity')
                                    <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-3.5 w-3.5 text-brand-600"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-5 py-3 text-right">Cost Price</th>
                        <th class="px-5 py-3 cursor-pointer select-none hover:text-gray-700 text-right" wire:click="setSort('valuation_cost')">
                            <div class="flex items-center justify-end gap-1">
                                Cost Valuation
                                @if ($sortBy === 'valuation_cost')
                                    <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-3.5 w-3.5 text-brand-600"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-5 py-3 text-right">Retail Price</th>
                        <th class="px-5 py-3 cursor-pointer select-none hover:text-gray-700 text-right" wire:click="setSort('valuation_retail')">
                            <div class="flex items-center justify-end gap-1">
                                Retail Valuation
                                @if ($sortBy === 'valuation_retail')
                                    <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-3.5 w-3.5 text-brand-600"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-5 py-3 cursor-pointer select-none hover:text-gray-700 text-right" wire:click="setSort('potential_profit')">
                            <div class="flex items-center justify-end gap-1">
                                Margin / Profit
                                @if ($sortBy === 'potential_profit')
                                    <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-3.5 w-3.5 text-brand-600"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-5 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($units as $unit)
                        @php
                            $qty = (int) $unit->current_quantity;
                            $cost = (float) $unit->purchase_price;
                            $retail = (float) $unit->normal_sale_price;
                            $costVal = $qty * $cost;
                            $retailVal = $qty * $retail;
                            $profit = $retailVal - $costVal;
                            $margin = $retailVal > 0 ? round(($profit / $retailVal) * 100, 1) : 0;
                        @endphp
                        <tr wire:key="inv-val-unit-{{ $unit->id }}" class="hover:bg-gray-50/70">
                            {{-- Product / Unit Name --}}
                            <td class="px-5 py-3.5">
                                <span class="font-medium text-gray-900">{{ $unit->product?->name }}</span>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span>{{ $unit->name }}</span>
                                    <span>·</span>
                                    <span class="font-mono">{{ $unit->code ?: ($unit->original_code ?: 'No Code') }}</span>
                                    <span>·</span>
                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[11px] text-gray-600">{{ $unit->product?->category?->name ?? 'Uncategorized' }}</span>
                                </div>
                            </td>

                            {{-- Current Stock --}}
                            <td class="px-5 py-3.5 text-center">
                                <span class="font-semibold {{ $qty <= 0 ? 'text-red-600' : ($qty <= (int) $unit->reorder_quantity ? 'text-amber-600' : 'text-gray-900') }}">
                                    {{ $qty }}
                                </span>
                                <span class="block text-[11px] text-gray-400">Reorder: {{ $unit->reorder_quantity }}</span>
                            </td>

                            {{-- Cost Price --}}
                            <td class="px-5 py-3.5 text-right text-gray-600">
                                {{ number_format($cost, 2) }}
                            </td>

                            {{-- Cost Valuation --}}
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900">
                                {{ number_format($costVal, 2) }}
                            </td>

                            {{-- Retail Price --}}
                            <td class="px-5 py-3.5 text-right text-gray-600">
                                {{ number_format($retail, 2) }}
                            </td>

                            {{-- Retail Valuation --}}
                            <td class="px-5 py-3.5 text-right font-semibold text-emerald-600">
                                {{ number_format($retailVal, 2) }}
                            </td>

                            {{-- Margin / Profit --}}
                            <td class="px-5 py-3.5 text-right">
                                <span class="font-semibold {{ $profit >= 0 ? 'text-blue-600' : 'text-red-500' }}">
                                    {{ number_format($profit, 2) }}
                                </span>
                                <span class="block text-[11px] text-gray-400">{{ $margin }}% margin</span>
                            </td>

                            {{-- Health Status Badge --}}
                            <td class="px-5 py-3.5 text-center">
                                @if ($qty <= 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                        Out of stock
                                    </span>
                                @elseif ($qty <= (int) $unit->reorder_quantity)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        Low stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Healthy
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-gray-500">
                                <i data-lucide="package-x" class="mx-auto mb-2 h-8 w-8 text-gray-300"></i>
                                No product variants found matching the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($units->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $units->links() }}
            </div>
        @endif
    </section>
</div>
