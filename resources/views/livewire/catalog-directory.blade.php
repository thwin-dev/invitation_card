<div class="space-y-5">
    <section class="pos-panel flex flex-col gap-4 p-4 sm:flex-row sm:items-end sm:justify-between">
        <label class="block w-full max-w-md">
            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search {{ $kind === 'brands' ? 'brands' : 'locations' }}</span>
            <span class="relative block">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                <input wire:model.live.debounce.300ms="search" placeholder="{{ $kind === 'branches' ? 'Search by location name' : 'Search by brand name' }}" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </span>
        </label>
        <div class="flex flex-wrap items-center gap-2">
            @if($kind === 'branches' && auth('web')->user()?->hasPermissionTo('branches', 'view'))
                <a href="{{ route('management.branches.prices') }}" class="pos-button-secondary"><i data-lucide="tags" class="mr-2 h-4 w-4"></i>Branch prices</a>
            @endif
            @if(auth('web')->user()?->hasPermissionTo($kind === 'branches' ? 'branches' : 'products', 'create'))<button type="button" wire:click="edit" class="pos-button"><i data-lucide="plus" class="mr-2 h-4 w-4"></i>Add {{ $kind === 'brands' ? 'brand' : 'location' }}</button>@endif
        </div>
    </section>

    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">{{ $kind === 'branches' ? 'All locations' : 'All brands' }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ $rows->total() }} {{ $kind === 'branches' ? 'locations' : 'brands' }}</p>
            </div>
            <div wire:loading role="status" class="inline-flex items-center gap-2 text-xs font-medium text-brand-600">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-500"></span>Updating list…
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead>
                    <tr>
                        <th>{{ $kind === 'branches' ? 'Location' : 'Brand' }}</th>
                        <th>Type</th>
                        @if($kind === 'branches')<th>Currency</th>@endif
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr wire:key="directory-{{ $row->id }}">
                            <td>
                                <div class="flex min-w-52 items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $kind === 'branches' && $row->type === 'warehouse' ? 'bg-amber-50 text-amber-600' : 'bg-brand-50 text-brand-600' }}">
                                        <i data-lucide="{{ $kind === 'branches' ? ($row->type === 'warehouse' ? 'warehouse' : 'store') : 'tag' }}" class="h-5 w-5"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-2 font-semibold text-gray-900">
                                            <span class="truncate">{{ $row->name }}</span>
                                            @if($kind === 'branches' && $row->is_main)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-700"><i data-lucide="lock-keyhole" class="h-3 w-3"></i>Main</span>
                                            @endif
                                        </span>
                                        @if($kind === 'branches')
                                            <span class="mt-0.5 block truncate text-xs text-gray-500">{{ $row->address ?: ($row->is_main ? 'Default sales and purchasing location' : 'No address added') }}</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if($kind === 'branches')
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->type === 'warehouse' ? 'bg-amber-50 text-amber-700' : 'bg-sky-50 text-sky-700' }}">{{ $row->type === 'warehouse' ? 'Warehouse' : 'Shop' }}</span>
                                @else
                                    <span class="text-gray-500">Brand</span>
                                @endif
                            </td>
                            @if($kind === 'branches')
                                <td><span class="font-medium text-gray-700">{{ $row->currency_code ?: 'MMK' }}</span></td>
                            @endif
                            <td>
                                @if($kind === 'brands' && auth('web')->user()?->hasPermissionTo('products', 'edit'))
                                    <button type="button" wire:click="toggle({{ $row->id }})" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}" aria-label="Toggle {{ $row->name }} status">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $row->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>{{ $row->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $row->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>{{ $row->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if(auth('web')->user()?->hasPermissionTo($kind === 'branches' ? 'branches' : 'products', 'edit'))<button type="button" wire:click="edit({{ $row->id }})" class="pos-button-secondary px-3 py-2"><i data-lucide="pencil" class="mr-1.5 h-3.5 w-3.5"></i>Edit</button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $kind === 'branches' ? 5 : 4 }}" class="!py-14 text-center">
                                <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-400"><i data-lucide="{{ $kind === 'branches' ? 'map-pin' : 'tag' }}" class="h-6 w-6"></i></span>
                                <p class="font-semibold text-gray-800">No {{ $kind === 'branches' ? 'locations' : 'brands' }} found</p>
                                <p class="mt-1 text-xs text-gray-500">Try another search or add a new {{ $kind === 'branches' ? 'location' : 'brand' }}.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </section>

    @if($open && ($editingId ? auth('web')->user()?->hasPermissionTo($kind === 'branches' ? 'branches' : 'products', 'edit') : auth('web')->user()?->hasPermissionTo($kind === 'branches' ? 'branches' : 'products', 'create')))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm" wire:keydown.escape="$set('open', false)">
            <section role="dialog" aria-modal="true" aria-labelledby="directory-title" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i data-lucide="{{ $kind === 'branches' ? 'store' : 'tag' }}" class="h-5 w-5"></i></span>
                        <div>
                            <h2 id="directory-title" class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit' : 'Add' }} {{ $kind === 'brands' ? 'brand' : 'location' }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $kind === 'branches' ? 'Set up the location and the details shown on its receipts.' : 'Add a brand to organize your products.' }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('open', false)" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog"><i data-lucide="x" class="h-4 w-4"></i></button>
                </div>

                <form wire:submit="save" class="space-y-5 px-6 py-5">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-gray-700">Name</span>
                        <input autofocus wire:model="name" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100" required>
                    </label>
                    @error('name')<p class="-mt-3 text-sm text-red-600">{{ $message }}</p>@enderror

                    @if($kind === 'branches')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-medium text-gray-700">Location type</span>
                                <select wire:model.live="type" @disabled($isMain) class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"><option value="shop">Shop</option><option value="warehouse">Warehouse</option></select>
                            </label>
                            <div class="flex items-end pb-2">
                                <label class="inline-flex items-center gap-2.5 text-sm font-medium text-gray-700"><input type="checkbox" wire:model="isActive" @disabled($isMain) class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 disabled:cursor-not-allowed">Active location</label>
                            </div>
                        </div>
                        @if($type === 'shop' && $isMain)
                            <div class="flex items-start gap-3 rounded-xl border border-brand-100 bg-brand-50/70 p-4 text-sm text-brand-800">
                                <i data-lucide="lock-keyhole" class="mt-0.5 h-4 w-4 shrink-0"></i>
                                <p><strong>Main is fixed.</strong> Its shop type and active status stay enabled so existing stock remains attached to the same location.</p>
                            </div>
                        @elseif($type === 'shop' && !$hasMain)
                            <label class="inline-flex items-center gap-2.5 rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-3 text-sm font-medium text-gray-700"><input type="checkbox" wire:model="isMain" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">Set as Main sales and purchasing location</label>
                        @endif
                        @error('isMain')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('type')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Phone <span class="font-normal text-gray-400">· optional</span></span><input wire:model="phone" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                            <label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Address <span class="font-normal text-gray-400">· optional</span></span><input wire:model="address" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                        </div>
                        @foreach(['phone', 'address'] as $field)
                            @error($field)<p class="-mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                        @endforeach

                        <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                            <div class="mb-3 flex items-center gap-2"><i data-lucide="receipt-text" class="h-4 w-4 text-gray-500"></i><h3 class="text-sm font-semibold text-gray-800">Receipt message</h3></div>
                            <div class="space-y-4">
                                <label class="block"><span class="mb-1.5 block text-xs font-medium text-gray-600">Header <span class="font-normal text-gray-400">· optional</span></span><textarea wire:model="receiptHeader" rows="2" placeholder="Message below the shop name" class="w-full resize-y rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></textarea></label>
                                <label class="block"><span class="mb-1.5 block text-xs font-medium text-gray-600">Footer <span class="font-normal text-gray-400">· optional</span></span><textarea wire:model="receiptFooter" rows="2" placeholder="Thank-you note or return policy" class="w-full resize-y rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></textarea></label>
                            </div>
                            <p class="mt-3 text-xs leading-5 text-gray-500">Each sale saves a snapshot, so existing receipts keep their original details.</p>
                        </div>
                        @foreach(['receiptHeader', 'receiptFooter'] as $field)
                            @error($field)<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @endforeach
                    @endif

                    <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <button type="button" wire:click="$set('open', false)" class="pos-button-secondary">Cancel</button>
                        <button wire:loading.attr="disabled" wire:target="save" class="pos-button disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : ($kind === 'branches' ? 'Create location' : 'Create brand') }}</span><span wire:loading wire:target="save">Saving…</span></button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
