<div wire:poll.15s x-data x-init="window.lucide?.createIcons()">
    <a href="{{ route('management.inventory.operations') }}" class="pos-button mb-4">Stock in / out, transfers and counts</a>
    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="pos-panel p-5"><p class="text-sm text-gray-500">Units tracked</p><p class="mt-2 text-2xl font-semibold">{{ $unitsTracked }}</p></div>
        <div class="pos-panel p-5"><p class="text-sm text-gray-500">Needs reorder</p><p class="mt-2 text-2xl font-semibold text-amber-700">{{ $reorderCount }}</p></div>
        <div class="pos-panel p-5"><p class="text-sm text-gray-500">Stock purchase value</p><p class="mt-2 text-2xl font-semibold">{{ number_format((float) $stockValue, 2) }}</p></div>
    </div>

    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4">
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="setMode('overview')" class="{{ $mode === 'overview' ? 'bg-brand-500 text-white shadow-sm' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }} rounded-lg px-3.5 py-2 text-sm font-semibold">Stock count</button>
                <button type="button" wire:click="setMode('price')" class="{{ $mode === 'price' ? 'bg-brand-500 text-white shadow-sm' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }} rounded-lg px-3.5 py-2 text-sm font-semibold">Stock prices</button>
                <button type="button" wire:click="setMode('reorder')" class="{{ $mode === 'reorder' ? 'bg-amber-600 text-white shadow-sm' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }} rounded-lg px-3.5 py-2 text-sm font-semibold">Reorder list ({{ $reorderCount }})</button>
            </div>
            <input wire:model.live.debounce.400ms="search" placeholder="Search product, unit, or code" class="w-72 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Product / unit</th>
                        <th class="px-5 py-3">Quantity</th>
                        <th class="px-5 py-3">Reorder</th>
                        <th class="px-5 py-3">Prices</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($units as $unit)
                        <tr wire:key="stock-unit-{{ $unit->id }}">
                            <td class="px-5 py-4"><span class="font-medium text-gray-900">{{ $unit->product->name }}</span><span class="block text-xs text-gray-500">{{ $unit->name }} · {{ $unit->code ?: '-' }}</span></td>
                            <td class="px-5 py-4"><span class="font-semibold {{ $unit->current_quantity <= $unit->reorder_quantity ? 'text-amber-700' : 'text-gray-900' }}">{{ $unit->current_quantity }}</span></td>
                            <td class="px-5 py-4 text-gray-600">{{ $unit->reorder_quantity }}</td>
                            <td class="px-5 py-4"><span class="block">Sale {{ number_format((float) $unit->normal_sale_price, 2) }}</span><span class="block text-xs text-gray-500">Wholesale {{ number_format((float) $unit->whole_sale_price, 2) }}</span><span class="block text-xs text-gray-500">Order {{ number_format((float) $unit->order_price, 2) }} · Buy {{ number_format((float) $unit->purchase_price, 2) }}</span></td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('management.product-units.index', ['product_id' => $unit->product_id]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"><i data-lucide="boxes" class="h-4 w-4"></i>Units</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">No stock units found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $units->links() }}</div>
    </section>
</div>
