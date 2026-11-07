<div class="space-y-5">
    @if($branches->isEmpty())
        <section class="pos-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-50 text-gray-400"><i data-lucide="store" class="h-7 w-7"></i></span>
            <h2 class="text-base font-semibold text-gray-900">No active shops yet</h2>
            <p class="mt-1 max-w-sm text-sm text-gray-500">Create an active shop before setting branch-specific prices.</p>
            @if(auth('web')->user()?->hasPermissionTo('branches', 'create'))<a href="{{ route('management.branches.index') }}" class="pos-button mt-5"><i data-lucide="plus" class="mr-2 h-4 w-4"></i>Add a shop</a>@endif
        </section>
    @else
        <section class="pos-panel p-4 sm:p-5">
            <div class="grid gap-4 lg:grid-cols-[minmax(12rem,0.8fr)_minmax(16rem,1.2fr)_auto] lg:items-end">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Shop</span>
                    <select wire:model.live="branchId" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        @foreach($branches as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search products</span>
                    <span class="relative block">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                        <input wire:model.live.debounce.300ms="search" placeholder="Product, SKU, or barcode" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    </span>
                </label>
                <p class="flex items-center gap-2 text-xs leading-5 text-gray-500 lg:max-w-56"><i data-lucide="info" class="h-4 w-4 shrink-0 text-gray-400"></i>Blank prices use the product unit defaults.</p>
            </div>
        </section>

        <section class="pos-panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-gray-900">Product prices</h2>
                    <p class="mt-1 text-xs text-gray-500">Prices for {{ $branch?->name ?: 'the selected shop' }}</p>
                </div>
                <span wire:loading role="status" class="inline-flex items-center gap-2 text-xs font-medium text-brand-600"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-500"></span>Updating prices…</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead><tr><th>Product / unit</th><th>Retail price</th><th>Wholesale price</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                        @forelse($units as $unit)
                            @php($override = $overrides->get($unit->id))
                            <tr wire:key="branch-price-{{ $unit->id }}">
                                <td>
                                    <div class="flex min-w-56 items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-500"><i data-lucide="package" class="h-5 w-5"></i></span>
                                        <span class="min-w-0"><span class="block truncate font-semibold text-gray-900">{{ $unit->product->name }}</span><span class="mt-0.5 block truncate text-xs text-gray-500">{{ $unit->name }} · {{ $unit->code ?: $unit->original_code ?: 'No SKU' }}</span></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="block font-semibold tabular-nums text-gray-900">{{ number_format((float) ($override?->normal_sale_price ?? $unit->normal_sale_price), 2) }} <span class="text-xs font-medium text-gray-500">MMK</span></span>
                                    <span class="mt-1 inline-flex items-center gap-1 text-[11px] {{ $override?->normal_sale_price === null ? 'text-gray-400' : 'text-brand-600' }}"><span class="h-1.5 w-1.5 rounded-full {{ $override?->normal_sale_price === null ? 'bg-gray-300' : 'bg-brand-500' }}"></span>{{ $override?->normal_sale_price === null ? 'Default price' : 'Shop price' }}</span>
                                </td>
                                <td>
                                    <span class="block font-semibold tabular-nums text-gray-900">{{ number_format((float) ($override?->whole_sale_price ?? $unit->whole_sale_price), 2) }} <span class="text-xs font-medium text-gray-500">MMK</span></span>
                                    <span class="mt-1 inline-flex items-center gap-1 text-[11px] {{ $override?->whole_sale_price === null ? 'text-gray-400' : 'text-brand-600' }}"><span class="h-1.5 w-1.5 rounded-full {{ $override?->whole_sale_price === null ? 'bg-gray-300' : 'bg-brand-500' }}"></span>{{ $override?->whole_sale_price === null ? 'Default price' : 'Shop price' }}</span>
                                </td>
                                <td class="text-right">@if(auth('web')->user()?->hasPermissionTo('branches', 'edit'))<button type="button" wire:click="edit({{ $unit->id }})" class="pos-button-secondary px-3 py-2"><i data-lucide="pencil" class="mr-1.5 h-3.5 w-3.5"></i>Set prices</button>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="!py-14 text-center"><i data-lucide="package-search" class="mx-auto mb-3 h-7 w-7 text-gray-300"></i><p class="font-semibold text-gray-800">No products found</p><p class="mt-1 text-xs text-gray-500">Try a different name, SKU, or barcode.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($units->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $units->links() }}</div>@endif
        </section>
    @endif

    @if($open && $editingUnit && auth('web')->user()?->hasPermissionTo('branches', 'edit'))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm" wire:keydown.escape="$set('open', false)">
            <section role="dialog" aria-modal="true" aria-labelledby="branch-price-title" class="w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i data-lucide="tags" class="h-5 w-5"></i></span>
                        <div><h2 id="branch-price-title" class="text-lg font-semibold text-gray-900">Set shop prices</h2><p class="mt-1 text-sm text-gray-500">{{ $editingUnit->product->name }} · {{ $editingUnit->name }}</p></div>
                    </div>
                    <button type="button" wire:click="$set('open', false)" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog"><i data-lucide="x" class="h-4 w-4"></i></button>
                </div>
                <form wire:submit="save" class="space-y-5 px-6 py-5">
                    <div class="grid grid-cols-2 gap-3 rounded-xl border border-gray-100 bg-gray-50 p-4 text-sm">
                        <div><span class="block text-xs text-gray-500">Default retail</span><strong class="mt-1 block tabular-nums text-gray-800">{{ number_format((float) $editingUnit->normal_sale_price, 2) }} MMK</strong></div>
                        <div><span class="block text-xs text-gray-500">Default wholesale</span><strong class="mt-1 block tabular-nums text-gray-800">{{ number_format((float) $editingUnit->whole_sale_price, 2) }} MMK</strong></div>
                    </div>
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Retail price <span class="font-normal text-gray-400">· MMK</span></span><input type="number" min="0" step="0.01" wire:model="normalPrice" placeholder="Use default" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                    @error('normalPrice')<p class="-mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Wholesale price <span class="font-normal text-gray-400">· MMK</span></span><input type="number" min="0" step="0.01" wire:model="wholeSalePrice" placeholder="Use default" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                    @error('wholeSalePrice')<p class="-mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                    <p class="flex items-start gap-2 rounded-lg bg-sky-50 px-3.5 py-3 text-xs leading-5 text-sky-800"><i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0"></i>Leave a field blank to inherit its default. Leave both blank to remove all price overrides for this product.</p>
                    <div class="flex justify-end gap-2 border-t border-gray-100 pt-4"><button type="button" wire:click="$set('open', false)" class="pos-button-secondary">Cancel</button><button wire:loading.attr="disabled" wire:target="save" class="pos-button disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="save">Save prices</span><span wire:loading wire:target="save">Saving…</span></button></div>
                </form>
            </section>
        </div>
    @endif
</div>
