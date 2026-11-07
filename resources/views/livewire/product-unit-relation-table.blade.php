<div x-data x-init="window.lucide?.createIcons()">
    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Unit relations</h2>
                <p class="mt-1 text-sm text-gray-500">Select a product, then define relations and convert stock.</p>
            </div>
            <select wire:model.live="productId" class="min-w-72 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">Choose product</option>
                @foreach($products as $item)
                    <option value="{{ $item->id }}">{{ $item->code }} · {{ $item->name }}</option>
                @endforeach
            </select>
        </div>

        @if($product)
            <div class="grid gap-4 border-b border-gray-100 p-4 md:grid-cols-3">
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Product</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $product->code }} · {{ $product->name }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Counting units</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $units->count() }}</p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    @if(auth('web')->user()?->hasPermissionTo('unit-relations', 'edit'))<button type="button" wire:click="openConversion" class="pos-button-secondary"><i data-lucide="repeat-2" class="mr-2 h-4 w-4"></i>Convert stock</button>@endif
                    @if(auth('web')->user()?->hasPermissionTo('unit-relations', 'create'))<button type="button" wire:click="openRelation" class="pos-button"><i data-lucide="plus" class="mr-2 h-4 w-4"></i>Add relation</button>@endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">From</th>
                            <th class="px-5 py-3">To</th>
                            <th class="px-5 py-3">Rate</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($relations as $relation)
                            <tr wire:key="relation-{{ $relation->id }}">
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $relation->fromUnit->name }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $relation->toUnit->name }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-600">1 {{ $relation->fromUnit->name }} = {{ $relation->quantity }} {{ $relation->toUnit->name }}</span></td>
                                <td class="px-5 py-4 text-right">
                                    @if(auth('web')->user()?->hasPermissionTo('unit-relations', 'delete'))<button type="button" wire:click="confirmDelete({{ $relation->id }})" class="rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100" title="Delete relation"><i data-lucide="trash-2" class="h-4 w-4"></i></button>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">No unit relations yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-5 py-12 text-center text-sm text-gray-500">Choose a product to manage its unit relations.</div>
        @endif
    </section>

    @if($product)
        <section class="pos-panel mt-6 overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Recent conversions</h2>
                <p class="mt-1 text-sm text-gray-500">Latest stock movements created from unit conversion.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">From</th><th class="px-5 py-3">To</th><th class="px-5 py-3">Balances</th><th class="px-5 py-3">Date</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($conversions as $conversion)
                            <tr wire:key="conversion-{{ $conversion->id }}"><td class="px-5 py-4 font-medium">{{ $conversion->fromUnit->name }}</td><td class="px-5 py-4">{{ $conversion->toUnit->name }}</td><td class="px-5 py-4 text-gray-500">{{ $conversion->from_unit_quantity }} / {{ $conversion->to_unit_quantity }}</td><td class="px-5 py-4 text-gray-500">{{ $conversion->created_at?->format('d M Y H:i') }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">No conversions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if($relationOpen && auth('web')->user()?->hasPermissionTo('unit-relations', 'create'))
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between"><div><h2 class="text-lg font-semibold text-gray-900">Add relation</h2><p class="mt-1 text-sm text-gray-500">Define how two units for this product relate.</p></div><button type="button" wire:click="closeRelation" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                <form wire:submit.prevent="createRelation" class="mt-5 space-y-4">
                    <label class="block text-sm font-medium text-gray-700">From unit<select wire:model="relationFromUnitId" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"><option value="">Choose from unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->current_quantity }})</option>@endforeach</select>@error('relationFromUnitId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</label>
                    <label class="block text-sm font-medium text-gray-700">To unit<select wire:model="relationToUnitId" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"><option value="">Choose to unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select>@error('relationToUnitId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</label>
                    <label class="block text-sm font-medium text-gray-700">Quantity<input type="number" min="1" wire:model="relationQuantity" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">@error('relationQuantity') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</label>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5"><button type="button" wire:click="closeRelation" class="pos-button-secondary">Cancel</button><button type="submit" wire:loading.attr="disabled" wire:target="createRelation" class="pos-button">Create relation</button></div>
                </form>
            </section>
        </div>
    @endif

    @if($conversionOpen && auth('web')->user()?->hasPermissionTo('unit-relations', 'edit'))
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between"><div><h2 class="text-lg font-semibold text-gray-900">Convert stock</h2><p class="mt-1 text-sm text-gray-500">Move stock from one unit to another using an existing relation.</p></div><button type="button" wire:click="closeConversion" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                <form wire:submit.prevent="convertStock" class="mt-5 space-y-4">
                    <label class="block text-sm font-medium text-gray-700">From unit<select wire:model="convertFromUnitId" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"><option value="">Choose from unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->current_quantity }})</option>@endforeach</select>@error('convertFromUnitId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</label>
                    <label class="block text-sm font-medium text-gray-700">To unit<select wire:model="convertToUnitId" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"><option value="">Choose to unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select>@error('convertToUnitId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</label>
                    <label class="block text-sm font-medium text-gray-700">Quantity to convert<input type="number" min="1" wire:model="convertQuantity" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">@error('convertQuantity') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</label>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5"><button type="button" wire:click="closeConversion" class="pos-button-secondary">Cancel</button><button type="submit" wire:loading.attr="disabled" wire:target="convertStock" class="pos-button">Convert stock</button></div>
                </form>
            </section>
        </div>
    @endif

    @if($deletingId && auth('web')->user()?->hasPermissionTo('unit-relations', 'delete'))
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section style="width:340px;max-width:calc(100vw - 2rem);" class="rounded-xl bg-white p-4 shadow-xl">
                <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600"><i data-lucide="trash-2" class="h-4 w-4"></i></span><div class="min-w-0 flex-1"><h2 class="text-base font-semibold text-gray-900">Delete relation?</h2><p class="mt-1 text-xs text-gray-500">This action cannot be undone.</p></div></div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" wire:click="closeDelete" class="rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancel</button><button type="button" wire:click="deleteRelation" wire:loading.attr="disabled" wire:target="deleteRelation" style="background-color:#dc2626;color:#ffffff;" class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium shadow-sm transition hover:opacity-90 disabled:opacity-50"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i><span>Delete</span></button></div>
            </section>
        </div>
    @endif
</div>
