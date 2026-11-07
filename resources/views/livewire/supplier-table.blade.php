<div x-data x-init="window.lucide?.createIcons()">
    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-end gap-3 border-b border-gray-100 p-4">
            <input wire:model.live.debounce.400ms="search" placeholder="Search supplier, company, phone..." class="w-80 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Phone</th>
                        <th class="px-5 py-3 text-center">Orders</th>
                        <th class="px-5 py-3 text-right">Total Purchased</th>
                        <th class="px-5 py-3 text-right">Payable Balance</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($suppliers as $supplier)
                        @php
                            $payable = (float) ($supplier->outstanding_payable ?? 0);
                        @endphp
                        <tr wire:key="supplier-{{ $supplier->id }}">
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ $supplier->name }}</div>
                                @if($supplier->company_name)
                                    <div class="text-xs text-brand-600 font-medium">{{ $supplier->company_name }}</div>
                                @endif
                                <div class="max-w-xs truncate text-xs text-gray-400">{{ $supplier->address ?: 'No address' }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $supplier->phone ?: '-' }}</td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700">
                                    {{ $supplier->purchases_count }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-mono font-medium text-gray-800">
                                {{ number_format((float) ($supplier->total_purchased ?? 0), 2) }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($payable > 0)
                                    <span class="font-mono text-sm font-bold text-red-600">{{ number_format($payable, 2) }}</span>
                                @else
                                    <span class="font-mono text-xs font-medium text-emerald-600">0.00</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('management.suppliers.show', $supplier) }}" class="inline-flex rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100" title="Supplier profile & history"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                @if(auth('web')->user()?->hasPermissionTo('suppliers', 'edit'))<button type="button" wire:click="editSupplier({{ $supplier->id }})" class="ml-2 rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100" title="Edit supplier"><i data-lucide="pencil" class="h-4 w-4"></i></button>@endif
                                @if(auth('web')->user()?->hasPermissionTo('suppliers', 'delete'))<button type="button" wire:click="confirmDelete({{ $supplier->id }})" class="ml-2 rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100" title="Delete supplier"><i data-lucide="trash-2" class="h-4 w-4"></i></button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr wire:key="suppliers-empty"><td colspan="6" class="px-5 py-10 text-center text-gray-500">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $suppliers->links() }}</div>
    </section>

    {{-- Create Modal --}}
    @if($createOpen && auth('web')->user()?->hasPermissionTo('suppliers', 'create'))
        <div wire:key="supplier-create-modal" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Add supplier</h2>
                        <p class="mt-1 text-sm text-gray-500">Create a new supplier for purchase orders and payables.</p>
                    </div>
                    <button type="button" wire:click="closeCreate" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form wire:submit.prevent="createSupplier" class="mt-5 space-y-4">
                    <label class="block text-sm font-medium text-gray-700">Contact / Supplier name
                        <input wire:model="createName" required placeholder="e.g. U Hla Win" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                        @error('createName') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-gray-700">Company / Brand name (optional)
                        <input wire:model="createCompany" placeholder="e.g. ABC Distributor" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                        @error('createCompany') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-gray-700">Phone
                        <input wire:model="createPhone" placeholder="e.g. 0912345678" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                        @error('createPhone') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-gray-700">Address
                        <textarea wire:model="createAddress" rows="3" placeholder="Office / Warehouse address" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"></textarea>
                        @error('createAddress') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" wire:click="closeCreate" class="pos-button-secondary">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="createSupplier" class="pos-button">Create supplier</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($editingId && auth('web')->user()?->hasPermissionTo('suppliers', 'edit'))
        <div wire:key="supplier-edit-modal-{{ $editingId }}" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Edit supplier</h2>
                        <p class="mt-1 text-sm text-gray-500">Update contact and business details.</p>
                    </div>
                    <button type="button" wire:click="closeEdit" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form wire:submit.prevent="updateSupplier" class="mt-5 space-y-4">
                    <label class="block text-sm font-medium text-gray-700">Contact / Supplier name
                        <input wire:model="editName" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                        @error('editName') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-gray-700">Company / Brand name (optional)
                        <input wire:model="editCompany" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                        @error('editCompany') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-gray-700">Phone
                        <input wire:model="editPhone" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                        @error('editPhone') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-gray-700">Address
                        <textarea wire:model="editAddress" rows="3" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"></textarea>
                        @error('editAddress') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" wire:click="closeEdit" class="pos-button-secondary">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateSupplier" class="pos-button">Save changes</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if($deletingId && auth('web')->user()?->hasPermissionTo('suppliers', 'delete'))
        <div wire:key="supplier-delete-modal-{{ $deletingId }}" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section style="width:340px;max-width:calc(100vw - 2rem);" class="rounded-xl bg-white p-4 shadow-xl">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-semibold text-gray-900">Delete supplier?</h2>
                        <p class="mt-1 text-xs text-gray-500">This action cannot be undone.</p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="closeDelete" class="rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancel</button>
                    <button type="button" wire:click="deleteSupplier" wire:loading.attr="disabled" wire:target="deleteSupplier" style="background-color:#dc2626;color:#ffffff;" class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium shadow-sm transition hover:opacity-90 disabled:opacity-50">
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                        <span>Delete</span>
                    </button>
                </div>
            </section>
        </div>
    @endif
</div>
