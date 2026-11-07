<div x-on:open-category-create.window="$wire.openCreate()">
    @if($toast)
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3500)" x-show="show" x-transition style="position:fixed;top:1rem;right:1rem;width:380px;max-width:calc(100vw - 2rem);z-index:70" class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-xl sm:right-8 sm:top-8">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><i data-lucide="check" class="h-5 w-5"></i></span>
            <div class="min-w-0 flex-1"><p class="text-sm font-semibold text-gray-900">Success</p><p class="mt-0.5 text-sm text-gray-500">{{ $toast }}</p></div>
            <button type="button" @click="show = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100" aria-label="Dismiss notification"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
    @endif
    <section class="pos-panel overflow-hidden">
        <div class="flex justify-end border-b border-gray-100 p-4">
            <input wire:model.live.debounce.400ms="search" placeholder="Search code or name" class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Code</th><th class="px-5 py-3">Name</th><th class="px-5 py-3">Subcategories</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($categories as $category)
                        <tr><td class="px-5 py-4 text-gray-500">{{ $category->code ?: '-' }}</td><td class="px-5 py-4 font-medium">{{ $category->name }}</td><td class="px-5 py-4">{{ $category->subcategories->pluck('name')->join(', ') ?: '-' }}</td><td class="px-5 py-4 text-right">@if(auth('web')->user()?->hasPermissionTo('categories', 'edit'))<button type="button" wire:click="editCategory({{ $category->id }})" class="rounded-lg bg-brand-50 p-2 text-brand-600" title="Edit category"><i data-lucide="pencil" class="inline h-4 w-4"></i></button><button type="button" wire:click="toggleCategory({{ $category->id }})" wire:loading.attr="disabled" wire:target="toggleCategory({{ $category->id }})" class="ml-3 inline-flex cursor-pointer items-center" title="Toggle category" aria-label="Toggle category"><span class="relative block h-6 w-11 rounded-full p-1 transition-colors {{ $category->is_active ? 'bg-[#465fff]' : 'bg-gray-300' }}"><span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform {{ $category->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span></span></button>@endif</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 p-4">{{ $categories->links() }}</div>
    </section>
    @if($editingId && auth('web')->user()?->hasPermissionTo('categories', 'edit'))
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4"><section class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl"><div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Edit category</h2><button type="button" wire:click="closeEdit" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button></div><form wire:submit.prevent="updateCategory" class="mt-6 space-y-5"><label class="block text-sm font-medium">Code<input wire:model="editCode" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5"></label><label class="block text-sm font-medium">Name<input wire:model="editName" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5"></label><div class="flex justify-end gap-3"><button type="button" wire:click="closeEdit" class="pos-button-secondary">Cancel</button><button type="submit" class="pos-button">Save changes</button></div></form></section></div>
    @endif
    @if($createOpen && auth('web')->user()?->hasPermissionTo('categories', 'create'))
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4"><section class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl"><div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Add category</h2><button type="button" wire:click="closeCreate" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button></div><form wire:submit.prevent="createCategory" class="mt-6 space-y-5"><label class="block text-sm font-medium">Code<input wire:model="createCode" placeholder="e.g. CAT-001" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5"></label><label class="block text-sm font-medium">Name<input wire:model="createName" placeholder="Category name" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5"></label><div class="flex justify-end gap-3"><button type="button" wire:click="closeCreate" class="pos-button-secondary">Cancel</button><button type="submit" class="pos-button">Create category</button></div></form></section></div>
    @endif
</div>
