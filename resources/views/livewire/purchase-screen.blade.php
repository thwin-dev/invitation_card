<div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-sm" x-data x-init="window.lucide?.createIcons()">
    <div class="grid h-[calc(100vh-9rem)] min-h-[620px] overflow-hidden lg:grid-cols-[390px_minmax(0,1fr)] xl:grid-cols-[430px_minmax(0,1fr)]">
        {{-- Left Column: Purchase Order Cart & Form --}}
        <form wire:submit.prevent="savePurchase" class="flex min-h-0 flex-col overflow-hidden border-r border-gray-200 bg-white">
            {{-- Top Header: Tag and Purchase Date --}}
            <div class="shrink-0 border-b border-gray-200 px-3 py-2.5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg bg-brand-500 px-2.5 py-1.5 text-xs font-bold text-white">PURCHASE</span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Receive stock</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-gray-400">Date:</span>
                        <input type="date" wire:model.live="purchaseDate" class="h-8 rounded-lg border border-gray-200 px-2.5 text-xs text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                    </div>
                </div>
            </div>

            {{-- Supplier Selector Card --}}
            <div class="shrink-0 border-b border-gray-200 bg-gray-50/80 px-3 py-2.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-gray-600">Supplier:</span>
                    <button type="button" wire:click="toggleCustomSupplier" class="text-[11px] font-medium text-brand-600 hover:underline">
                        {{ $isCustomSupplier ? 'Select registered supplier' : '+ Custom name' }}
                    </button>
                </div>

                @if(! $isCustomSupplier)
                    <div class="mt-1.5">
                        <select wire:model.live="supplierId" class="h-8 w-full rounded-lg border border-gray-300 bg-white px-2.5 text-xs font-medium text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                            <option value="">Choose registered supplier</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">
                                    {{ $sup->name }}{{ $sup->company_name ? ' ('.$sup->company_name.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($this->selectedSupplierModel)
                        @php
                            $sup = $this->selectedSupplierModel;
                            $payable = (float) $sup->outstandingPayable();
                        @endphp
                        <div class="mt-1.5 flex items-center justify-between gap-2 rounded-md bg-white px-2.5 py-1 text-[11px] border border-gray-200 shadow-2xs">
                            <div class="truncate text-gray-600">
                                <span class="font-medium text-gray-800">{{ $sup->company_name ?: $sup->name }}</span>
                                @if($sup->phone)
                                    <span class="text-gray-400">· {{ $sup->phone }}</span>
                                @endif
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="text-gray-500">Payable:</span>
                                <strong class="font-mono {{ $payable > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ number_format($payable, 2) }}
                                </strong>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="mt-1.5">
                        <input
                            type="text"
                            wire:model.live="supplierName"
                            placeholder="Enter supplier / brand name"
                            class="h-8 w-full rounded-lg border border-gray-300 bg-white px-2.5 text-xs text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                        >
                    </div>
                @endif
            </div>

            {{-- Cart Items List (Scrollable) --}}
            <div class="min-h-0 flex-1 overflow-y-auto bg-gray-50 p-3 pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @forelse($cart as $unitId => $line)
                    <div class="mb-2 rounded-lg border border-gray-200 bg-white px-2.5 py-2 shadow-sm">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $line['product'] }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ $line['unit'] }} · {{ $line['code'] ?: 'No SKU' }} ·
                                <span class="font-medium text-gray-600">Stock: {{ $line['stock'] }}</span>
                            </p>
                        </div>

                        <details class="mt-2 text-xs"><summary class="cursor-pointer text-brand-600">Batch, serial and expiry</summary><div class="mt-2 grid gap-2">
                            <label>Batch number<input wire:model="cart.{{ $unitId }}.batch_number" class="mt-1 w-full rounded border border-gray-300 p-2"></label>
                            <label>Serial number (quantity must be 1)<input wire:model="cart.{{ $unitId }}.serial_number" class="mt-1 w-full rounded border border-gray-300 p-2"></label>
                            <label>Expiry date<input type="date" wire:model="cart.{{ $unitId }}.expires_at" class="mt-1 w-full rounded border border-gray-300 p-2"></label>
                        </div></details>
                        <div class="mt-2 border-t border-gray-100 pt-2">
                            <div class="grid grid-cols-[108px_minmax(0,1fr)_32px] items-center gap-2">
                                {{-- Quantity Adjuster --}}
                                <div class="flex h-9 items-center rounded-lg bg-gray-100 p-1">
                                    <button
                                        type="button"
                                        wire:click="$set('cart.{{ $unitId }}.qty', {{ max(1, (int) $line['qty'] - 1) }})"
                                        class="flex h-7 w-7 items-center justify-center rounded-md bg-white text-gray-600 hover:text-brand-600 font-bold"
                                    >-</button>
                                    <input
                                        type="number"
                                        min="1"
                                        wire:model.live="cart.{{ $unitId }}.qty"
                                        class="h-7 w-10 border-0 bg-transparent p-0 text-center text-sm font-semibold focus:ring-0"
                                    >
                                    <button
                                        type="button"
                                        wire:click="$set('cart.{{ $unitId }}.qty', {{ (int) $line['qty'] + 1 }})"
                                        class="flex h-7 w-7 items-center justify-center rounded-md bg-white text-gray-600 hover:text-brand-600 font-bold"
                                    >+</button>
                                </div>

                                {{-- Purchase Cost Price (Editable per unit) --}}
                                <div class="text-right">
                                    <span class="block text-[11px] font-medium text-gray-400">Unit cost price</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.live="cart.{{ $unitId }}.price"
                                        class="h-7 w-28 rounded-md border border-gray-200 bg-gray-50/50 px-2 text-right font-mono text-xs font-semibold text-gray-900 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100"
                                    >
                                </div>

                                {{-- Delete item button --}}
                                <button
                                    type="button"
                                    wire:click="removeUnit({{ $unitId }})"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition"
                                    title="Remove item"
                                    aria-label="Remove item"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-1 text-right font-mono text-xs font-semibold text-gray-500">
                                {{ number_format((int) $line['qty'] * (float) $line['price'], 2) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex min-h-72 items-center justify-center rounded-lg border border-dashed border-gray-200 bg-white p-6 text-center text-sm text-gray-500">
                        Search or scan an item, then click a product card to add it to purchase.
                    </div>
                @endforelse
            </div>

            {{-- Bottom Section: Paid, Totals, Save button & Payfull / Reset button placement like in sale page --}}
            <div class="shrink-0 border-t border-gray-200 bg-white p-2.5 shadow-[0_-10px_24px_rgba(16,24,40,0.08)]">
                <div class="grid gap-1.5 text-sm">
                    {{-- Paid Input --}}
                    <label class="rounded-lg bg-gray-50 px-3 py-2">
                        <span class="block text-xs text-gray-500">Paid amount</span>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model.live="paidAmount"
                            class="mt-1 h-11 w-full rounded-lg border border-gray-200 px-3 text-right font-mono text-base font-semibold focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                    </label>

                    {{-- Due Date if Payable > 0 --}}
                    @if($this->payableAmount > 0)
                        <label class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2">
                            <span class="text-xs text-gray-500">Payment due date</span>
                            <input
                                type="date"
                                wire:model.live="paymentDueDate"
                                class="h-7 w-32 rounded-md border border-gray-200 px-2 text-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                            >
                        </label>
                    @endif

                    {{-- Total Summary Row --}}
                    <div class="flex items-end justify-between border-t border-gray-200 pt-2">
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-wide text-gray-900">Total purchase</span>
                            @if($this->payableAmount > 0)
                                <span class="text-xs text-gray-500">Payable <strong class="font-mono text-red-700">{{ number_format($this->payableAmount, 2) }}</strong></span>
                            @elseif($this->subtotal > 0)
                                <span class="text-xs text-gray-500">Status <strong class="font-mono text-emerald-700">Paid in full</strong></span>
                            @endif
                        </div>
                        <strong class="font-mono text-xl font-bold text-brand-600">
                            {{ number_format($this->subtotal, 2) }}
                        </strong>
                    </div>
                </div>

                {{-- Primary Save Button --}}
                <button
                    type="submit"
                    class="mt-2 flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-sm hover:bg-brand-600 transition"
                    wire:loading.attr="disabled"
                    wire:target="savePurchase"
                >
                    <span class="inline-flex items-center gap-2">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Save & receive stock
                    </span>
                </button>

                {{-- Quick Action Buttons placed exactly like in sale page --}}
                <div class="mt-2 grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        wire:click="payFullAmount"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Pay full
                    </button>
                    <a
                        href="{{ route('management.purchases.index') }}"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>
                    <button
                        type="button"
                        wire:click="resetCart"
                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </form>

        {{-- Right Column: Catalog, Search & Quick Add --}}
        <section class="flex min-h-0 min-w-0 flex-col overflow-hidden bg-gray-100">
            {{-- Top Search & Category Bar --}}
            <div class="shrink-0 border-b border-gray-200 bg-white p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        class="relative min-w-72 flex-1"
                        x-data
                        x-on:purchase-search-focus.window="$nextTick(() => $refs.purchaseSearch.focus())"
                    >
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"></i>
                        <input
                            x-ref="purchaseSearch"
                            wire:model.live.debounce.150ms="search"
                            autofocus
                            placeholder="Search catalog or scan barcode"
                            class="h-12 w-full rounded-xl border border-gray-200 bg-white pl-11 pr-4 text-sm font-medium text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                    </div>
                    <button
                        type="button"
                        wire:click="focusScanner"
                        class="inline-flex h-12 items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        <i data-lucide="scan-barcode" class="h-4 w-4"></i>
                        Scan
                    </button>
                </div>

                {{-- Category Tabs --}}
                <div class="mt-3 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <button
                        type="button"
                        wire:click="selectCategory('')"
                        class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold transition {{ $categoryId === '' ? 'bg-brand-500 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
                    >
                        All Items
                    </button>
                    @foreach($categories as $category)
                        <button
                            type="button"
                            wire:click="selectCategory('{{ $category->id }}')"
                            class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold transition {{ (string) $categoryId === (string) $category->id ? 'bg-brand-500 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Products Grid --}}
            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @forelse($products as $unit)
                        @php
                            $inCart = isset($cart[$unit->id]);
                        @endphp
                        <button
                            type="button"
                            wire:click="addUnit({{ $unit->id }})"
                            class="group overflow-hidden rounded-xl border bg-white p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $inCart ? 'border-brand-500 bg-brand-50/20 ring-2 ring-brand-500/20' : 'border-gray-200 hover:border-brand-200' }}"
                        >
                            <div class="relative flex aspect-[1.35] items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                                @if($unit->product?->photo_path)
                                    <img
                                        src="{{ str_starts_with($unit->product->photo_path, 'images/') ? asset($unit->product->photo_path) : \Illuminate\Support\Facades\Storage::disk('public')->url($unit->product->photo_path) }}"
                                        alt="{{ $unit->product->name }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/70 text-gray-400">
                                        <i data-lucide="package" class="h-7 w-7"></i>
                                    </span>
                                @endif

                                <span class="absolute right-2 top-2 rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">
                                    {{ $unit->current_quantity }} in stock
                                </span>

                                @if($inCart)
                                    <span class="absolute left-2 top-2 rounded-full bg-brand-500 px-2 py-0.5 text-xs font-bold text-white shadow-sm">
                                        {{ $cart[$unit->id]['qty'] }} in cart
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3">
                                <h3 class="line-clamp-2 min-h-10 text-sm font-bold text-gray-900 group-hover:text-brand-600">
                                    {{ $unit->product?->name ?: 'Unknown product' }}
                                </h3>
                                <p class="mt-1 truncate text-xs text-gray-500">
                                    {{ $unit->name }} · {{ $unit->code ?: $unit->original_code ?: 'No code' }}
                                </p>
                                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                                    <div>
                                        <span class="block text-[10px] uppercase text-gray-400">Cost price</span>
                                        <strong class="font-mono text-base text-gray-900">
                                            {{ number_format((float) ($unit->purchase_price ?: 0), 2) }}
                                        </strong>
                                    </div>
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $inCart ? 'bg-brand-500 text-white' : 'bg-brand-50 text-brand-600 group-hover:bg-brand-500 group-hover:text-white' }} transition">
                                        <i data-lucide="plus" class="h-4 w-4"></i>
                                    </span>
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full flex min-h-72 flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">
                            <i data-lucide="package-search" class="h-10 w-10 text-gray-300"></i>
                            <p class="mt-2 font-medium text-gray-700">No matching product units found</p>
                            <p class="mt-1 text-xs text-gray-400">Try changing your search term or category filter.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Bottom Footer --}}
            <div class="shrink-0 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3">
                <div class="text-xs text-gray-500">
                    Showing catalog units · Click to add to incoming stock order
                </div>
                <a href="{{ route('management.purchases.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:underline">
                    <i data-lucide="list" class="h-4 w-4"></i>
                    View purchase history
                </a>
            </div>
        </section>
    </div>
</div>
