<div
    class="overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-sm"
    x-data="posSaleScreen(@js(now()->toDateString()), @js($branch->is_main), @js(['branch_id' => $branch->id, ...app(\App\Modules\Sale\Services\BranchSalePricing::class)->receiptSnapshot($branch)]))"
>
    <div class="grid h-[calc(100vh-9rem)] min-h-[620px] overflow-hidden lg:grid-cols-[390px_minmax(0,1fr)] xl:grid-cols-[430px_minmax(0,1fr)]">
        {{-- Left Form: Cart & Payment --}}
        <form
            @submit.prevent="if (!isOnline) { handleOfflineComplete(); } else { $wire.completeSale(); }"
            class="flex min-h-0 flex-col overflow-hidden border-r border-gray-200 bg-white"
        >
            {{-- Header with Online/Offline Network Pills --}}
            <div class="shrink-0 border-b border-gray-200 px-3 py-2.5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-lg bg-brand-500 px-2.5 py-1.5 text-xs font-bold text-white">SALE</span>

                        {{-- Online / Offline Badges --}}
                        <template x-if="isOnline">
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Online
                            </span>
                        </template>
                        <template x-if="!isOnline">
                            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 border border-red-200 animate-pulse">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                Offline Mode
                            </span>
                        </template>

                        {{-- Refresh Catalog Button --}}
                        <template x-if="isOnline">
                            <button
                                type="button"
                                @click="refreshCatalogNow()"
                                class="inline-flex items-center gap-1 rounded-full bg-gray-50 px-2 py-0.5 text-[10px] font-semibold text-gray-700 border border-gray-200 hover:bg-gray-100 transition cursor-pointer"
                                title="Update offline catalog & prices"
                            >
                                <i data-lucide="refresh-cw" class="h-2.5 w-2.5 text-gray-500"></i>
                                Refresh
                            </button>
                        </template>

                        {{-- Pending Offline Sync Button --}}
                        <template x-if="pendingCount > 0">
                            <button
                                type="button"
                                @click="openOfflineModal()"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-800 border border-amber-300 hover:bg-amber-100 transition cursor-pointer"
                                title="Click to view and sync offline sales"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Sync (<span x-text="pendingCount"></span>)
                            </button>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-end gap-2">
                    <label x-show="isOnline" class="block text-[10px] font-semibold text-gray-500">Shop
                        <select wire:model.live="branchId" aria-label="Sales shop" class="mt-0.5 h-8 max-w-40 rounded-lg border border-gray-200 bg-white px-2.5 text-xs font-medium text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                            @foreach($branches as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach
                        </select>
                    </label>
                    <span x-show="!isOnline" class="mb-0.5 inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-2.5 py-1.5 text-[10px] font-semibold text-gray-600"><i data-lucide="store" class="h-3.5 w-3.5"></i>{{ $branch->name }}</span>
                    <label x-show="isOnline" class="block text-[10px] font-semibold text-gray-500">Sale date
                    <input
                        type="date"
                        wire:model.live="saleDate"
                        class="mt-0.5 h-8 rounded-lg border border-gray-200 bg-white px-2.5 text-xs font-medium text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                    >
                    </label>
                    <label x-show="!isOnline" class="block text-[10px] font-semibold text-gray-500">Sale date
                    <input
                        type="date"
                        x-model="offlineSaleDate"
                        class="mt-0.5 h-8 rounded-lg border border-gray-200 bg-white px-2.5 text-xs font-medium text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                    >
                    </label>
                    </div>
                </div>
            </div>
            <div x-show="!isOnline && !isMainBranch" class="shrink-0 border-b border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800" role="status">
                Offline sales are currently available from the Main shop only. Reconnect before selling from {{ $branch->name }}.
            </div>

            {{-- Customer Selector --}}
            <div class="shrink-0 border-b border-gray-200 bg-gray-50/80 px-3 py-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">Customer:</span>
                    <select
                        name="pos_customer_select"
                        x-show="isOnline"
                        wire:model.live="customerId"
                        class="h-8 flex-1 rounded-lg border border-gray-300 bg-white px-2.5 text-xs font-medium text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                    >
                        <option value="">Walk-in Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Level {{ $c->customer_level }})</option>
                        @endforeach
                    </select>
                    <select
                        x-show="!isOnline"
                        x-model="offlineCustomerId"
                        class="h-8 flex-1 rounded-lg border border-gray-300 bg-white px-2.5 text-xs font-medium text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                    >
                        <option value="">Walk-in Customer</option>
                        <template x-for="cust in offlineCustomers" :key="cust.id">
                            <option :value="cust.id" x-text="cust.name + ' (Level ' + cust.customer_level + ')'"></option>
                        </template>
                    </select>
                </div>
                @if($this->selectedCustomerModel)
                    @php
                        $cust = $this->selectedCustomerModel;
                        $custDebt = $cust->outstandingDebt();
                    @endphp
                    <div x-show="isOnline" class="mt-1.5 flex items-center justify-between gap-2 text-[11px]">
                        <span class="font-medium">
                            @if($cust->customer_level == 3)
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 font-bold text-amber-700 border border-amber-200">Level 3 (VIP)</span>
                            @elseif($cust->customer_level == 2)
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 font-bold text-blue-700 border border-blue-200">Level 2 (Silver)</span>
                            @else
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 font-bold text-gray-700">Level 1</span>
                            @endif
                        </span>
                        <div class="text-right">
                            @if($cust->allow_credit)
                                <span class="text-gray-500">Debt: <strong class="font-mono {{ $custDebt > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($custDebt, 2) }}</strong></span>
                                <span class="text-gray-300">|</span>
                                <span class="text-gray-500">Limit: <strong class="font-mono text-gray-800">{{ (float) $cust->credit_limit > 0 ? number_format((float) $cust->credit_limit, 2) : '∞' }}</strong></span>
                            @else
                                <span class="rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-semibold text-red-600">Credit disabled</span>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Offline Customer Debt and Credit Info --}}
                <template x-if="!isOnline && getSelectedOfflineCustomer()">
                    <div class="mt-1.5 flex items-center justify-between gap-2 text-[11px]">
                        <span class="font-medium">
                            <template x-if="getSelectedOfflineCustomer().customer_level === 3">
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 font-bold text-amber-700 border border-amber-200">Level 3 (VIP)</span>
                            </template>
                            <template x-if="getSelectedOfflineCustomer().customer_level === 2">
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 font-bold text-blue-700 border border-blue-200">Level 2 (Silver)</span>
                            </template>
                            <template x-if="getSelectedOfflineCustomer().customer_level !== 2 && getSelectedOfflineCustomer().customer_level !== 3">
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 font-bold text-gray-700" x-text="'Level ' + (getSelectedOfflineCustomer().customer_level || 1)"></span>
                            </template>
                        </span>
                        <div class="text-right">
                            <template x-if="getSelectedOfflineCustomer().allow_credit">
                                <span>
                                    <span class="text-gray-500">Debt: <strong class="font-mono" :class="(getSelectedOfflineCustomer().debt || 0) > 0 ? 'text-red-600' : 'text-emerald-600'" x-text="Number(getSelectedOfflineCustomer().debt || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong></span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-gray-500">Limit: <strong class="font-mono text-gray-800" x-text="(getSelectedOfflineCustomer().credit_limit > 0) ? Number(getSelectedOfflineCustomer().credit_limit).toLocaleString(undefined, {minimumFractionDigits: 2}) : '∞'"></strong></span>
                                </span>
                            </template>
                            <template x-if="!getSelectedOfflineCustomer().allow_credit">
                                <span class="rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-semibold text-red-600">Credit disabled</span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Cart Items List --}}
            <div class="min-h-0 flex-1 overflow-y-auto bg-gray-50 p-3 pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                {{-- Online Livewire Cart --}}
                <div x-show="isOnline">
                    @forelse($cart as $unitId => $line)
                        <div class="mb-2 rounded-lg border border-gray-200 bg-white px-2.5 py-2 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $line['product'] }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ $line['unit'] }} · {{ $line['code'] ?: 'No code' }} · <span class="font-medium text-emerald-600">{{ $line['stock'] }} in stock</span></p>
                                </div>
                                <strong class="font-mono text-sm text-gray-900">{{ number_format((float) $line['price'], 2) }}</strong>
                            </div>

                            <div class="mt-2 border-t border-gray-100 pt-2">
                                <div class="grid grid-cols-[108px_minmax(0,1fr)_32px] items-center gap-2">
                                    <div class="flex h-9 items-center rounded-lg bg-gray-100 p-1">
                                        <button type="button" wire:click="$set('cart.{{ $unitId }}.qty', {{ max(1, (int) $line['qty'] - 1) }})" class="flex h-7 w-7 items-center justify-center rounded-md bg-white text-gray-600 hover:text-brand-600 font-bold">-</button>
                                        <input type="number" min="1" max="{{ $line['stock'] }}" wire:model.live="cart.{{ $unitId }}.qty" class="h-7 w-10 border-0 bg-transparent p-0 text-center text-sm font-semibold focus:ring-0">
                                        <button type="button" wire:click="$set('cart.{{ $unitId }}.qty', {{ min((int) $line['stock'], (int) $line['qty'] + 1) }})" class="flex h-7 w-7 items-center justify-center rounded-md bg-white text-gray-600 hover:text-brand-600 font-bold">+</button>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-[11px] font-medium text-gray-400">Selling price</span>
                                        <strong class="font-mono text-sm font-semibold text-gray-900">{{ number_format((float) $line['price'], 2) }}</strong>
                                    </div>
                                    <button type="button" wire:click="removeUnit({{ $unitId }})" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600" title="Remove item">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                @php($lineDiscount = $this->discountCalculation['items'][$unitId]['discount_amount'] ?? 0)
                                @php($appliedDiscountName = $this->discountCalculation['items'][$unitId]['discount_name'] ?? null)
                                <div class="mt-2 text-xs text-gray-600">Item discount: <span class="font-medium">{{ $appliedDiscountName ?: 'No eligible discount' }}</span></div>
                                <div class="mt-1 flex justify-between text-xs"><span class="text-emerald-700">Discount: {{ number_format($lineDiscount, 2) }}</span><strong class="font-mono">{{ number_format((int) $line['qty'] * (float) $line['price'] - $lineDiscount, 2) }}</strong></div>
                            </div>
                        </div>
                    @empty
                        <div class="flex min-h-72 items-center justify-center rounded-lg border border-dashed border-gray-200 bg-white p-6 text-center text-sm text-gray-500">
                            Search or scan an item, then click a product card to add it here.
                        </div>
                    @endforelse
                </div>

                {{-- Offline Client Cart --}}
                <div x-show="!isOnline">
                    <template x-for="(line, unitId) in offlineCart" :key="unitId">
                        <div class="mb-2 rounded-lg border border-gray-200 bg-white px-2.5 py-2 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900" x-text="line.product"></p>
                                    <p class="mt-0.5 text-xs text-gray-500" x-text="line.unit + ' · ' + (line.code || 'No code') + ' · ' + getOfflineStock(line.unit_id) + ' in stock'"></p>
                                </div>
                                <strong class="font-mono text-sm text-gray-900" x-text="Number(line.price).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong>
                            </div>

                            <div class="mt-2 border-t border-gray-100 pt-2">
                                <div class="grid grid-cols-[108px_minmax(0,1fr)_32px] items-center gap-2">
                                    <div class="flex h-9 items-center rounded-lg bg-gray-100 p-1">
                                        <button type="button" @click="if (line.qty > 1) line.qty--" class="flex h-7 w-7 items-center justify-center rounded-md bg-white text-gray-600 hover:text-brand-600 font-bold">-</button>
                                        <input type="number" min="1" :max="getOfflineStock(line.unit_id)" x-model.number="line.qty" @input="if (line.qty > getOfflineStock(line.unit_id)) { line.qty = getOfflineStock(line.unit_id); showOfflineToast('Quantity capped at available stock (' + getOfflineStock(line.unit_id) + ')'); } if (line.qty < 1) line.qty = 1;" class="h-7 w-10 border-0 bg-transparent p-0 text-center text-sm font-semibold focus:ring-0">
                                        <button type="button" @click="if (line.qty < getOfflineStock(line.unit_id)) { line.qty++; } else { showOfflineToast('Cannot exceed available stock (' + getOfflineStock(line.unit_id) + ')'); }" class="flex h-7 w-7 items-center justify-center rounded-md bg-white text-gray-600 hover:text-brand-600 font-bold">+</button>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-[11px] font-medium text-gray-400">Selling price</span>
                                        <strong class="font-mono text-sm font-semibold text-gray-900" x-text="Number(line.price).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong>
                                    </div>
                                    <button type="button" @click="delete offlineCart[unitId]" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600" title="Remove item">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-2 text-xs text-gray-600">Item discount: <span class="font-medium" x-text="getOfflineAutoDiscountRule(line)?.name || 'No eligible discount'"></span></div>
                                <div class="mt-1 flex justify-between text-xs"><span class="text-emerald-700" x-text="'Discount: ' + getOfflineLineDiscount(line).toFixed(2)"></span><strong class="font-mono" x-text="(line.qty * line.price - getOfflineLineDiscount(line)).toFixed(2)"></strong></div>
                            </div>
                        </div>
                    </template>
                    <template x-if="Object.keys(offlineCart).length === 0">
                        <div class="flex min-h-72 items-center justify-center rounded-lg border border-dashed border-gray-200 bg-white p-6 text-center text-sm text-gray-500">
                            Search or click a product card to add to offline cart.
                        </div>
                    </template>
                </div>
            </div>

            {{-- Totals & Checkout Panel --}}
            <div class="shrink-0 border-t border-gray-200 bg-white p-2.5 shadow-[0_-10px_24px_rgba(16,24,40,0.08)]">
                <div class="grid gap-1.5 text-sm">
                    {{-- Paid Amount Input --}}
                    <label class="rounded-lg bg-gray-50 px-3 py-2">
                        <span class="block text-xs text-gray-500">Paid</span>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            x-show="isOnline"
                            wire:model.live="paidAmount"
                            class="mt-1 h-11 w-full rounded-lg border border-gray-200 px-3 text-right font-mono text-base font-semibold focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            x-show="!isOnline"
                            x-model="offlinePaidAmount"
                            class="mt-1 h-11 w-full rounded-lg border border-gray-200 px-3 text-right font-mono text-base font-semibold focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                    </label>

                    {{-- Online Credit Due Date --}}
                    @if($this->creditAmount > 0 && $customerId)
                        <label x-show="isOnline" class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2">
                            <span class="text-xs text-gray-500">Repayment</span>
                            <input type="date" wire:model.live="repaymentDate" class="h-7 w-32 rounded-md border border-gray-200 px-2 text-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                    @endif

                    {{-- Offline Credit Due Date --}}
                    <label x-show="!isOnline && (getOfflineCredit() > 0 && offlineCustomerId)" class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2">
                        <span class="text-xs text-gray-500">Repayment</span>
                        <input type="date" x-model="offlineRepaymentDate" class="h-7 w-32 rounded-md border border-gray-200 px-2 text-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                    </label>

                    <div x-show="isOnline" class="space-y-1 text-xs">
                        <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($this->subtotal, 2) }}</span></div>
                        <div class="flex justify-between text-emerald-700"><span>Item discounts</span><span>−{{ number_format($this->discountCalculation['discount_amount'], 2) }}</span></div>
                        @if(isset($this->discountCalculation['error']))<p role="alert" class="text-red-600">{{ $this->discountCalculation['error'] }}</p>@endif
                    </div>
                    <div x-show="!isOnline" class="space-y-1 text-xs">
                        <div class="flex justify-between"><span>Subtotal</span><span x-text="getOfflineGross().toFixed(2)"></span></div>
                        <div class="flex justify-between text-emerald-700"><span>Item discounts</span><span x-text="'−' + getOfflineDiscount().toFixed(2)"></span></div>
                    </div>
                    {{-- Totals Row --}}
                    <div class="flex items-end justify-between border-t border-gray-200 pt-2">
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-wide text-gray-900">Total due</span>
                            <template x-if="isOnline">
                                <div>
                                    @if($this->changeAmount > 0)
                                        <span class="text-xs text-gray-500">Change <strong class="font-mono text-emerald-700">{{ number_format($this->changeAmount, 2) }}</strong></span>
                                    @elseif($this->creditAmount > 0)
                                        <span class="text-xs text-gray-500">Credit <strong class="font-mono text-red-700">{{ number_format($this->creditAmount, 2) }}</strong></span>
                                    @endif
                                </div>
                            </template>
                            <template x-if="!isOnline">
                                <div>
                                    <template x-if="getOfflineChange() > 0">
                                        <span class="text-xs text-gray-500">Change <strong class="font-mono text-emerald-700" x-text="Number(getOfflineChange()).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong></span>
                                    </template>
                                    <template x-if="getOfflineCredit() > 0 && getOfflineChange() <= 0">
                                        <span class="text-xs text-gray-500">Credit <strong class="font-mono text-red-700" x-text="Number(getOfflineCredit()).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <template x-if="isOnline">
                            <strong class="font-mono text-xl font-bold text-brand-600">{{ number_format($this->total, 2) }}</strong>
                        </template>
                        <template x-if="!isOnline">
                            <strong class="font-mono text-xl font-bold text-brand-600" x-text="Number(getOfflineSubtotal()).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong>
                        </template>
                    </div>
                </div>

                <button
                    type="submit"
                    class="mt-2 flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-sm hover:bg-brand-600 transition"
                    wire:loading.attr="disabled"
                    wire:target="completeSale"
                >
                    <span class="inline-flex items-center gap-2">
                        <i data-lucide="credit-card" class="h-4 w-4"></i>
                        <span x-text="isOnline ? 'Pay / complete sale' : 'Complete Sale (Offline)'"></span>
                    </span>
                </button>

                <div class="mt-2 grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        @click="if (!isOnline) { payFullOffline(); } else { $wire.payFullAmount(); }"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Pay full
                    </button>
                    <button
                        type="button"
                        @click="if (isOnline) { $wire.holdSale(); } else { holdSaleOffline(); }"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer"
                    >
                        Hold
                    </button>
                    <button
                        type="button"
                        @click="if (!isOnline) { resetOfflineCart(); } else { $wire.resetCart(); }"
                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </form>

        {{-- Right Panel: Catalog, Search & Grid --}}
        <section class="flex min-h-0 min-w-0 flex-col overflow-hidden bg-gray-100">
            <div class="shrink-0 border-b border-gray-200 bg-white p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        class="relative min-w-72 flex-1"
                        x-data
                        x-on:sale-search-focus.window="$nextTick(() => $refs.saleSearch.focus())"
                    >
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"></i>
                        <input
                            x-ref="saleSearch"
                            x-show="isOnline"
                            wire:model.live.debounce.150ms="search"
                            autofocus
                            placeholder="Search catalog or scan barcode"
                            class="h-12 w-full rounded-xl border border-gray-200 bg-white pl-11 pr-4 text-sm font-medium text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                        <input
                            x-ref="offlineSearchInput"
                            x-show="!isOnline"
                            x-model="offlineSearch"
                            @keydown.enter.prevent="handleOfflineSearchEnter()"
                            placeholder="Search catalog or scan barcode (offline)"
                            class="h-12 w-full rounded-xl border border-gray-200 bg-white pl-11 pr-4 text-sm font-medium text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                    </div>
                    <button type="button" @click="if (isOnline) { $wire.focusScanner(); } else { $nextTick(() => $refs.offlineSearchInput?.focus()); }" class="inline-flex h-12 items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer">
                        <i data-lucide="scan-barcode" class="h-4 w-4"></i>
                        Scan
                    </button>
                </div>

                <div class="mt-3 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <button
                        type="button"
                        @click="if (isOnline) { $wire.selectCategory(''); } else { offlineCategory = ''; }"
                        class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold transition"
                        :class="(isOnline ? '{{ $categoryId === '' }}' : offlineCategory === '') ? 'bg-brand-500 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                    >
                        All Items
                    </button>
                    @foreach($categories as $category)
                        <button
                            type="button"
                            @click="if (isOnline) { $wire.selectCategory('{{ $category->id }}'); } else { offlineCategory = '{{ $category->id }}'; }"
                            class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold transition"
                            :class="(isOnline ? '{{ (string) $categoryId === (string) $category->id }}' : offlineCategory === '{{ $category->id }}') ? 'bg-brand-500 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @forelse($products as $unit)
                        <button
                            type="button"
                            x-show="isOnline || ((!offlineCategory || offlineCategory == '{{ $unit->product?->category_id }}') && (!offlineSearch || '{{ strtolower($unit->product?->name . ' ' . $unit->name . ' ' . $unit->code . ' ' . $unit->original_code) }}'.includes(offlineSearch.toLowerCase())))"
                            @click.prevent="addToCart({{ json_encode([
                                'unit_id' => $unit->id,
                                'product' => $unit->product?->name ?: 'Unknown product',
                                'unit' => $unit->name,
                                'code' => $unit->code ?: $unit->original_code ?: '',
                                'stock' => (int) $unit->branch_stock,
                                'price' => (float) $unit->branch_price
                            ]) }})"
                            class="group overflow-hidden rounded-xl border border-gray-200 bg-white p-3 text-left shadow-sm hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md transition"
                            :class="(!isOnline && getOfflineStock({{ $unit->id }}) <= 0) ? 'opacity-60 cursor-not-allowed border-dashed' : ''"
                        >
                            <div class="relative flex aspect-[1.35] items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                                @if($unit->product?->photo_path)
                                    <img src="{{ str_starts_with($unit->product->photo_path, 'images/') ? asset($unit->product->photo_path) : \Illuminate\Support\Facades\Storage::disk('public')->url($unit->product->photo_path) }}" alt="{{ $unit->product->name }}" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/70 text-gray-400"><i data-lucide="package" class="h-7 w-7"></i></span>
                                @endif
                                <span x-show="isOnline" class="absolute right-2 top-2 rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">{{ $unit->branch_stock }} left</span>
                                <template x-if="!isOnline">
                                    <span
                                        class="absolute right-2 top-2 rounded-full px-2 py-1 text-xs font-bold"
                                        :class="getOfflineStock({{ $unit->id }}) <= 0 ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700'"
                                        x-text="getOfflineStock({{ $unit->id }}) <= 0 ? 'Out of Stock (Offline)' : getOfflineStock({{ $unit->id }}) + ' left'"
                                    ></span>
                                </template>
                            </div>
                            <div class="mt-3">
                                <h3 class="line-clamp-2 min-h-10 text-sm font-bold text-gray-900">{{ $unit->product?->name ?: 'Unknown product' }}</h3>
                                <p class="mt-1 truncate text-xs text-gray-500">{{ $unit->name }} · {{ $unit->code ?: $unit->original_code ?: 'No code' }}</p>
                                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                                    <strong class="font-mono text-base text-gray-900">{{ number_format((float) $unit->branch_price, 2) }}</strong>
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg transition"
                                        :class="(!isOnline && getOfflineStock({{ $unit->id }}) <= 0) ? 'bg-gray-100 text-gray-400' : 'bg-brand-50 text-brand-600 group-hover:bg-brand-500 group-hover:text-white'"
                                    >
                                        <i data-lucide="plus" class="h-4 w-4"></i>
                                    </span>
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full flex min-h-72 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white text-sm text-gray-500">
                            No matching products found.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="shrink-0 flex items-center justify-between gap-3 border-t border-gray-200 bg-white px-4 py-3">
                <div class="min-w-0 flex-1">
                    @if($heldSales->isNotEmpty())
                        <div x-show="isOnline" class="flex items-center gap-2">
                            <span class="shrink-0 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Held orders</span>
                            <div class="flex min-w-0 items-center gap-3 overflow-x-auto py-2 px-1.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                @foreach($heldSales as $heldSale)
                                    <div class="inline-flex items-center shrink-0 rounded-lg border text-xs font-semibold overflow-hidden transition shadow-sm {{ $heldSaleId === $heldSale->id ? 'border-brand-500 bg-brand-50 text-brand-700 ring-1 ring-brand-500/20' : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300' }}">
                                        <button type="button" wire:click="resumeHeldSale({{ $heldSale->id }})" class="flex items-center gap-2 py-2 pl-3 pr-2.5 text-left transition hover:bg-black/5" title="Show order">
                                            <span class="truncate font-semibold {{ $heldSaleId === $heldSale->id ? 'text-brand-700' : 'text-gray-800' }}">{{ $heldSale->customer?->name ?: 'Walk-in' }}</span>
                                            <span class="font-mono text-[11px] font-bold {{ $heldSaleId === $heldSale->id ? 'text-brand-600' : 'text-gray-500' }}">{{ number_format((float) $heldSale->total_price, 2) }}</span>
                                        </button>
                                        <span class="h-4 w-px shrink-0 {{ $heldSaleId === $heldSale->id ? 'bg-brand-200' : 'bg-gray-200' }}"></span>
                                        <button type="button" wire:click="deleteHeldSale({{ $heldSale->id }})" class="flex items-center justify-center py-2 px-2.5 text-red-500 hover:bg-red-50 hover:text-red-700 transition cursor-pointer" title="Delete order" aria-label="Delete order">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Offline Held Orders --}}
                    <div x-show="!isOnline && offlineHeldSales.length > 0" class="flex items-center gap-2">
                        <span class="shrink-0 text-[11px] font-semibold uppercase tracking-wide text-amber-800">Held (Offline)</span>
                        <div class="flex min-w-0 items-center gap-3 overflow-x-auto py-2 px-1.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            <template x-for="held in offlineHeldSales" :key="held.id">
                                <div
                                    class="inline-flex items-center shrink-0 rounded-lg border text-xs font-semibold overflow-hidden transition shadow-sm"
                                    :class="offlineHeldSaleId === held.id ? 'border-amber-500 bg-amber-50 text-amber-900 ring-1 ring-amber-500/30' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'"
                                >
                                    <button
                                        type="button"
                                        @click="resumeOfflineHeldSale(held)"
                                        class="flex items-center gap-2 py-2 pl-3 pr-2.5 text-left transition hover:bg-black/5"
                                        title="Show held order"
                                    >
                                        <span class="truncate font-semibold" x-text="held.customer_name || 'Walk-in'"></span>
                                        <span class="font-mono text-[11px] font-bold text-amber-800" x-text="Number(held.total_price || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </button>
                                    <span class="h-4 w-px shrink-0 bg-gray-200"></span>
                                    <button
                                        type="button"
                                        @click="deleteOfflineHeldSale(held.id)"
                                        class="flex items-center justify-center py-2 px-2.5 text-red-500 hover:bg-red-50 hover:text-red-700 transition cursor-pointer"
                                        title="Delete order"
                                    >
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-gray-800">
                    <i data-lucide="archive" class="h-4 w-4"></i>
                    Open drawer
                </button>
            </div>
        </section>
    </div>

    {{-- Offline Pending Sales Modal --}}
    <div
        x-cloak
        x-show="showOfflineModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-xs"
        @keydown.escape.window="showOfflineModal = false"
    >
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl" @click.outside="showOfflineModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Offline Sales Queue</h3>
                    <p class="text-xs text-gray-500">Transactions stored locally in browser waiting for network sync</p>
                </div>
                <button type="button" @click="showOfflineModal = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4 max-h-80 overflow-y-auto divide-y divide-gray-100">
                <template x-if="pendingSales.length === 0">
                    <div class="py-10 text-center text-sm text-gray-500">
                        No pending offline sales. All transactions are up to date.
                    </div>
                </template>

                <template x-for="sale in pendingSales" :key="sale.client_transaction_id">
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-bold text-gray-900" x-text="sale.voucher_code"></span>
                                <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">Offline</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                <span x-text="sale.customer_name"></span> ·
                                <span x-text="new Date(sale.offline_created_at).toLocaleTimeString()"></span> ·
                                <span x-text="sale.items.length + ' items'"></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <strong class="font-mono text-sm text-gray-900 mr-2" x-text="Number(sale.total_price).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong>
                            <button
                                type="button"
                                @click="printOffline(sale)"
                                class="rounded-lg bg-gray-100 p-2 text-gray-600 hover:bg-gray-200 transition cursor-pointer"
                                title="Print Receipt"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="if (confirm('Discard this offline sale from queue?')) deleteOfflineSale(sale.client_transaction_id)"
                                class="rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100 transition cursor-pointer"
                                title="Discard / Delete from Queue"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-5 flex items-center justify-between border-t border-gray-100 pt-4">
                <span class="text-xs text-gray-500" x-text="pendingSales.length + ' transactions pending'"></span>
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="showOfflineModal = false"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Close
                    </button>
                    <button
                        type="button"
                        @click="syncNow()"
                        :disabled="!isOnline || isSyncing || pendingSales.length === 0"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-xs font-bold text-white hover:bg-brand-600 disabled:opacity-50"
                    >
                        <span x-text="isSyncing ? 'Syncing...' : 'Sync All Now'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
