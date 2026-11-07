<div>
    <div class="pos-panel overflow-hidden">
        <div class="flex flex-wrap gap-3 border-b border-gray-100 p-4">
            <label class="flex-1"><span class="sr-only">Search discount rules</span><input wire:model.live.debounce.300ms="search" placeholder="Search discount rules" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"></label>
            <label><span class="sr-only">Branch</span><select wire:model.live="branchFilter" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
            <label><span class="sr-only">Status</span><select wire:model.live="statusFilter" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            @if(auth('web')->user()?->hasPermissionTo('discounts', 'create'))<button wire:click="create" wire:loading.attr="disabled" class="pos-button">Create discount rule</button>@endif
        </div>
        <p wire:loading.delay role="status" class="px-4 py-2 text-sm text-gray-500">Updating discount rules…</p>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr><th>Rule</th><th>Branch</th><th>Applies to</th><th>Discount</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            @forelse($rules as $rule)
                <tr wire:key="discount-rule-{{ $rule->id }}"><td class="font-medium">{{ $rule->name }}</td><td>{{ $rule->branches->pluck('name')->join(', ') ?: $rule->branch?->name }}</td><td>{{ $rule->unit ? ($rule->unit->product?->name.' · '.$rule->unit->name) : 'All items' }}</td><td>{{ number_format((float) $rule->value, 2) }} {{ $rule->type === 'percentage' ? '%' : '/ unit' }}</td><td>
                    @if(auth('web')->user()?->hasPermissionTo('discounts', 'edit'))
                        <button type="button" role="switch" aria-checked="{{ $rule->is_active ? 'true' : 'false' }}" aria-label="Toggle {{ $rule->name }}" wire:click="toggle({{ $rule->id }})" wire:loading.attr="disabled" wire:target="toggle({{ $rule->id }})" class="rounded-full px-3 py-1 text-xs font-semibold disabled:opacity-50 {{ $rule->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">{{ $rule->is_active ? 'Active' : 'Inactive' }}</button>
                    @else {{ $rule->is_active ? 'Active' : 'Inactive' }} @endif
                </td><td>@if(auth('web')->user()?->hasPermissionTo('discounts', 'edit'))<button wire:click="edit({{ $rule->id }})" wire:loading.attr="disabled" class="pos-button-secondary">Edit</button>@endif</td></tr>
            @empty<tr><td colspan="6" class="p-8 text-center text-gray-500">No discount rules found. Create a rule for a shop to start applying discounts.</td></tr>@endforelse
        </tbody></table></div>
        <div class="p-4">{{ $rules->links() }}</div>
    </div>
    @if($open)
        <div x-data x-init="$nextTick(() => $refs.name.focus())" @keydown.escape.window="$wire.close()" class="fixed inset-0 z-[80] flex items-center justify-center overflow-y-auto bg-gray-900/50 p-4">
            <section x-trap.inert.noscroll="true" role="dialog" aria-modal="true" aria-labelledby="discount-rule-title" class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 id="discount-rule-title" class="mb-5 text-lg font-semibold">{{ $editingId ? 'Edit' : 'Create' }} discount rule</h2>
                <form wire:submit="save" class="space-y-4">
                    <label class="block"><span class="mb-1.5 block text-sm font-medium">Rule name</span><input x-ref="name" wire:model="name" required maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2.5">@error('name')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
                    <fieldset><legend class="mb-1.5 block text-sm font-medium">Branches</legend><div class="max-h-40 space-y-2 overflow-y-auto rounded-lg border border-gray-300 p-3">@foreach($branches as $branch)<label wire:key="discount-rule-branch-{{ $branch->id }}" class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="branchIds" value="{{ $branch->id }}" class="rounded border-gray-300"><span>{{ $branch->name }}</span></label>@endforeach</div><span class="mt-1 block text-xs text-gray-500">Select one or more branches. This rule uses the same discount value in each selected branch.</span>@error('branchIds')<span class="text-sm text-red-600">{{ $message }}</span>@enderror @error('branchIds.*')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</fieldset>
                    <label class="block"><span class="mb-1.5 block text-sm font-medium">Product / unit</span><select wire:model="unitId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5"><option value="">All items at this branch</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->product?->name }} · {{ $unit->name }}</option>@endforeach</select>@error('unitId')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="block"><span class="mb-1.5 block text-sm font-medium">Discount type</span><select wire:model.live="type" class="w-full rounded-lg border border-gray-300 px-3 py-2.5"><option value="percentage">Percentage</option><option value="fixed">Fixed amount per unit</option></select>@error('type')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
                        <label class="block"><span class="mb-1.5 block text-sm font-medium">Value</span><input wire:model="value" type="number" step="0.01" min="0.01" max="{{ $type === 'percentage' ? '100' : '9999999999.99' }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5">@error('value')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isActive" class="rounded border-gray-300">Active</label>
                    <p class="text-xs text-gray-500">One rule per sale item. Fixed amounts are deducted from each unit. Completed sales retain their original discounts when a rule changes.</p>
                    <div class="flex justify-end gap-2"><button type="button" wire:click="close" class="pos-button-secondary">Cancel</button><button class="pos-button" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Save rule</span><span wire:loading wire:target="save">Saving…</span></button></div>
                </form>
            </section>
        </div>
    @endif
</div>
