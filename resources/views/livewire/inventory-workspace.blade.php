<div wire:poll.15s class="space-y-5">
    <section class="pos-panel p-4 sm:p-5">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="font-semibold text-gray-900">Stock overview</h2><p class="mt-1 text-xs text-gray-500">Choose a location and narrow the inventory list.</p></div>
            @if(auth('web')->user()?->hasPermissionTo('inventory', 'edit'))<button type="button" wire:click="start" class="pos-button whitespace-nowrap"><i data-lucide="plus" class="mr-2 h-4 w-4"></i>Record operation</button>@endif
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Branch</span>
                <select wire:model.live="branchId" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }} · {{ $branch->type === 'warehouse' ? 'Warehouse' : 'Shop' }}</option>@endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search inventory</span>
                <span class="relative block">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input wire:model.live.debounce.300ms="search" placeholder="Product, SKU, batch, or serial" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </span>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Stock alerts</span>
                <select wire:model.live="status" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <option value="">All stock</option><option value="low">Low stock</option><option value="out">Out of stock</option><option value="expired">Expired batches</option><option value="expiring">Expires within 30 days</option>
                </select>
            </label>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-3">
            <p class="max-w-4xl text-xs leading-5 text-gray-500">Sales use the selected active shop. Purchases currently enter Main shop stock. Transfer stock between shops and warehouses here.</p>
            <span class="inline-flex items-center gap-2 text-[11px] font-medium text-gray-400"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>Refreshes every 15 seconds</span>
        </div>
        <div wire:loading wire:target="search,branchId,status" role="status" class="mt-3 inline-flex items-center gap-2 text-xs font-medium text-brand-600"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-500"></span>Updating inventory…</div>
    </section>

    <section class="pos-panel overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-semibold text-gray-900">Branch balances</h2><p class="mt-1 text-xs text-gray-500">Stock available at the selected location.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead><tr><th>Product / variant</th><th class="text-right">Quantity</th><th class="text-right">Alert level</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                <tbody>
                    @forelse($units as $unit)
                        @php($isOut = (float) $unit->branch_quantity <= 0)
                        @php($isLow = ! $isOut && (float) $unit->branch_quantity <= (float) $unit->reorder_quantity)
                        <tr wire:key="balance-{{ $unit->id }}">
                            <td><div class="flex min-w-56 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-500"><i data-lucide="package" class="h-5 w-5"></i></span><span><span class="block font-semibold text-gray-900">{{ $unit->product->name }} / {{ $unit->name }}</span><span class="mt-0.5 block text-xs text-gray-500">{{ $unit->code ?: $unit->original_code ?: 'No SKU' }}@if($unit->size || $unit->color) · {{ collect([$unit->size, $unit->color])->filter()->implode(' · ') }}@endif</span></span></div></td>
                            <td class="text-right font-semibold tabular-nums text-gray-900">{{ number_format((float) $unit->branch_quantity) }}</td>
                            <td class="text-right tabular-nums">{{ number_format((float) $unit->reorder_quantity) }}</td>
                            <td><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $isOut ? 'bg-red-50 text-red-700' : ($isLow ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}"><span class="h-1.5 w-1.5 rounded-full {{ $isOut ? 'bg-red-500' : ($isLow ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>{{ $isOut ? 'Out of stock' : ($isLow ? 'Low stock' : 'In stock') }}</span></td>
                            <td class="text-right">@if(auth('web')->user()?->hasPermissionTo('inventory', 'edit'))<button type="button" wire:click="start({{ $unit->id }})" class="pos-button-secondary whitespace-nowrap px-3 py-2"><i data-lucide="sliders-horizontal" class="mr-1.5 h-4 w-4"></i>Stock action</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="!py-14 text-center"><span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-400"><i data-lucide="package-search" class="h-6 w-6"></i></span><p class="font-semibold text-gray-800">No matching stock</p><p class="mt-1 text-xs text-gray-500">Try another search or alert filter.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($units->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $units->links() }}</div>@endif
    </section>

    <details class="pos-panel group overflow-hidden">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500">
            <div><h2 class="font-semibold text-gray-900">Batches and serial numbers</h2><p class="mt-1 text-xs text-gray-500">Trace stock lots and check their expiry dates.</p></div>
            <span class="flex shrink-0 items-center gap-3"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">{{ $lots->total() }}</span><i data-lucide="chevron-down" class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180"></i></span>
        </summary>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead><tr><th>Product / unit</th><th>Batch</th><th>Serial</th><th>Expiry</th><th class="text-right">Quantity</th></tr></thead>
                <tbody>
                    @forelse($lots as $lot)
                        @php($isExpired = $lot->expires_at?->lt(today()))
                        <tr wire:key="lot-{{ $lot->id }}">
                            <td><span class="block min-w-48 font-semibold text-gray-900">{{ $lot->unit->product->name }}</span><span class="mt-0.5 block text-xs text-gray-500">{{ $lot->unit->name }}</span></td>
                            <td class="font-mono text-xs">{{ $lot->batch_number ?: '—' }}</td>
                            <td class="font-mono text-xs">{{ $lot->serial_number ?: '—' }}</td>
                            <td>@if($lot->expires_at)<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $isExpired ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600' }}">{{ $lot->expires_at->format('d M Y') }}</span>@else<span class="text-gray-400">—</span>@endif</td>
                            <td class="text-right font-semibold tabular-nums text-gray-900">{{ number_format((float) $lot->quantity) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="!py-12 text-center"><p class="font-semibold text-gray-800">No matching batches or serial numbers</p><p class="mt-1 text-xs text-gray-500">Incoming stock with lot details will appear here.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lots->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $lots->links() }}</div>@endif
    </details>

    @if($open && auth('web')->user()?->hasPermissionTo('inventory', 'edit'))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm" wire:keydown.escape="$set('open', false)" wire:click.self="$set('open', false)">
            <section role="dialog" aria-modal="true" aria-labelledby="operation-title" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl">
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-100 bg-white/95 px-6 py-5 backdrop-blur">
                    <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i data-lucide="boxes" class="h-5 w-5"></i></span><div><h2 id="operation-title" class="text-lg font-semibold text-gray-900">Record stock operation</h2><p class="mt-1 text-sm text-gray-500">Update a location balance or transfer stock between locations.</p></div></div>
                    <button type="button" wire:click="$set('open', false)" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog"><i data-lucide="x" class="h-4 w-4"></i></button>
                </div>
                <form wire:submit="save" class="grid gap-x-4 gap-y-5 px-6 py-5 sm:grid-cols-2">
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Operation</span><select autofocus wire:model.live="form.kind" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">@foreach(['in' => 'Stock in', 'out' => 'Stock out', 'adjust' => 'Adjust balance', 'count' => 'Physical stock count', 'transfer' => 'Branch transfer', 'damaged' => 'Damaged stock', 'expired' => 'Expired stock', 'lost' => 'Lost stock', 'assemble' => 'Assemble bundle'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Product / variant</span><select wire:model.live="form.unit_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"><option value="">Choose a product</option>@foreach($unitOptions as $option)<option value="{{ $option->id }}">{{ $option->product->name }} / {{ $option->name }}{{ $option->size || $option->color ? ' · '.collect([$option->size, $option->color])->filter()->implode(' · ') : '' }}</option>@endforeach</select></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Source branch</span><select wire:model.live="form.branch_id" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
                    @if($form['kind'] === 'transfer')
                        <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Destination branch</span><select wire:model="form.destination_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"><option value="">Choose destination</option>@foreach($branches as $branch)@if($branch->id != $form['branch_id'])<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endif @endforeach</select></label>
                    @endif
                    <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">{{ in_array($form['kind'], ['count', 'adjust']) ? 'New total quantity' : 'Quantity' }}</span><input type="number" min="{{ in_array($form['kind'], ['count', 'adjust']) ? 0 : 1 }}" wire:model="form.quantity" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"><span class="mt-1.5 block text-xs text-gray-500">Current balance: <strong class="font-semibold text-gray-700">{{ number_format($expected) }}</strong></span></label>
                    @if(!in_array($form['kind'], ['in', 'assemble']))
                        <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Batch / serial to remove</span><select wire:model="form.lot_id" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"><option value="">Automatic (earliest expiry first)</option>@foreach($lotOptions as $lot)<option value="{{ $lot->id }}">#{{ $lot->id }} · {{ $lot->batch_number ?: 'No batch' }} · {{ $lot->serial_number ?: 'No serial' }} · {{ $lot->quantity }} available · {{ $lot->expires_at?->format('d M Y') ?: 'No expiry' }}</option>@endforeach</select></label>
                    @endif
                    @if(in_array($form['kind'], ['in', 'assemble', 'count', 'adjust']))
                        <div class="sm:col-span-2"><p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Incoming stock details <span class="font-normal normal-case tracking-normal">· optional</span></p><div class="grid gap-4 sm:grid-cols-2">
                            <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Batch number</span><input wire:model="form.batch_number" placeholder="Enter batch number" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                            <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Serial number</span><input wire:model="form.serial_number" placeholder="Receive one at a time" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                            <label class="block sm:col-span-2"><span class="mb-1.5 block text-sm font-medium text-gray-700">Expiry date</span><input type="date" wire:model="form.expires_at" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100 sm:max-w-xs"></label>
                        </div></div>
                    @endif
                    <label class="block sm:col-span-2"><span class="mb-1.5 block text-sm font-medium text-gray-700">Reason / count reference</span><textarea wire:model="form.note" required rows="3" placeholder="Add a short note for the stock history" class="w-full resize-y rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></textarea></label>
                    @if($errors->any())<ul role="alert" class="space-y-1 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700 sm:col-span-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
                    <div class="sticky bottom-0 z-10 -mx-6 flex justify-end gap-2 border-t border-gray-100 bg-white/95 px-6 pb-1 pt-4 backdrop-blur sm:col-span-2"><button type="button" wire:click="$set('open', false)" class="pos-button-secondary">Cancel</button><button wire:loading.attr="disabled" wire:target="save" class="pos-button disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="save">Record operation</span><span wire:loading wire:target="save">Recording…</span></button></div>
                </form>
            </section>
        </div>
    @endif
</div>
