<div
    x-data
    x-init="window.lucide?.createIcons()"
>
    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-end gap-3 border-b border-gray-100 p-4">
            <select wire:model.live="productId" class="min-w-64 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">All products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->code }} · {{ $product->name }}</option>
                @endforeach
            </select>
            <input wire:model.live.debounce.400ms="search" placeholder="Search product, unit, or code" class="w-72 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Product / unit</th>
                        <th class="px-5 py-3">Codes</th>
                        <th class="px-5 py-3">Quantity</th>
                        <th class="px-5 py-3">Sale</th>
                        <th class="px-5 py-3">Purchase</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($units as $unit)
                        <tr wire:key="unit-{{ $unit->id }}">
                            <td class="px-5 py-4"><span class="font-medium text-gray-800">{{ $unit->product->name }}</span><span class="block text-xs text-gray-500">{{ $unit->name }} · {{ $unit->size }} {{ $unit->color }}</span></td>
                            <td class="px-5 py-4 text-gray-500">{{ $unit->code ?: '-' }}<span class="block text-xs">Original: {{ $unit->original_code ?: '-' }}</span></td>
                            <td class="px-5 py-4"><span class="font-medium">{{ $unit->current_quantity }}</span><span class="block text-xs text-gray-500">Reorder at {{ $unit->reorder_quantity }}</span></td>
                            <td class="px-5 py-4">{{ number_format((float) $unit->normal_sale_price, 2) }}<span class="block text-xs text-gray-500">Wholesale {{ number_format((float) $unit->whole_sale_price, 2) }}</span><span class="block text-xs text-gray-500">Order {{ number_format((float) $unit->order_price, 2) }}</span></td>
                            <td class="px-5 py-4">{{ number_format((float) $unit->purchase_price, 2) }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('management.product-units.barcode', $unit) }}" target="_blank" class="pos-button-secondary">Barcode</a>
                                @if(auth('web')->user()?->hasPermissionTo('units', 'edit'))<button type="button" wire:click="editUnit({{ $unit->id }})" class="rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100" title="Edit unit"><i data-lucide="pencil" class="h-4 w-4"></i></button>@endif
                                @if(auth('web')->user()?->hasPermissionTo('units', 'delete'))<button type="button" wire:click="confirmDelete({{ $unit->id }})" class="ml-2 rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100" title="Delete unit"><i data-lucide="trash-2" class="h-4 w-4"></i></button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No counting units found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $units->links() }}</div>
    </section>

    @if($createOpen && auth('web')->user()?->hasPermissionTo('units', 'create'))
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="max-h-[88vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between"><div><h2 class="text-lg font-semibold text-gray-900">Add counting unit</h2><p class="mt-1 text-sm text-gray-500">Create stock and price information for one product unit.</p></div><button type="button" wire:click="closeCreate" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                <form wire:submit.prevent="createUnit" class="mt-5 grid gap-4 md:grid-cols-2">
                    @include('livewire.partials.product-unit-form-fields', ['mode' => 'create'])
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5 md:col-span-2"><button type="button" wire:click="closeCreate" class="pos-button-secondary">Cancel</button><button type="submit" wire:loading.attr="disabled" wire:target="createUnit" class="pos-button">Create counting unit</button></div>
                </form>
            </section>
        </div>
    @endif

    @if($editingId && auth('web')->user()?->hasPermissionTo('units', 'edit'))
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="max-h-[88vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between"><div><h2 class="text-lg font-semibold text-gray-900">Edit counting unit</h2><p class="mt-1 text-sm text-gray-500">Update stock, codes, and prices without reloading the page.</p></div><button type="button" wire:click="closeEdit" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                <form wire:submit.prevent="updateUnit" class="mt-5 grid gap-4 md:grid-cols-2">
                    @include('livewire.partials.product-unit-form-fields', ['mode' => 'edit'])
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5 md:col-span-2"><button type="button" wire:click="closeEdit" class="pos-button-secondary">Cancel</button><button type="submit" wire:loading.attr="disabled" wire:target="updateUnit" class="pos-button">Save changes</button></div>
                </form>
            </section>
        </div>
    @endif

    @if($deletingId && auth('web')->user()?->hasPermissionTo('units', 'delete'))
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section style="width:340px;max-width:calc(100vw - 2rem);" class="rounded-xl bg-white p-4 shadow-xl">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600"><i data-lucide="trash-2" class="h-4 w-4"></i></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-semibold text-gray-900">Delete unit?</h2>
                        <p class="mt-1 text-xs text-gray-500">This action cannot be undone.</p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="closeDelete" class="rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancel</button>
                    <button type="button" wire:click="deleteUnit" wire:loading.attr="disabled" wire:target="deleteUnit" style="background-color:#dc2626;color:#ffffff;" class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium shadow-sm transition hover:opacity-90 disabled:opacity-50">
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                        <span>Delete</span>
                    </button>
                </div>
            </section>
        </div>
    @endif
</div>
