<div x-data x-init="window.lucide?.createIcons()">
    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-end gap-3 border-b border-gray-100 p-4">
            <input wire:model.live.debounce.400ms="search" placeholder="Search name, phone, or address" class="w-80 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Phone</th>
                        <th class="px-5 py-3">Level</th>
                        <th class="px-5 py-3 text-right">Credit Limit</th>
                        <th class="px-5 py-3 text-right">Outstanding Debt</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        @php
                            $debt = (float) ($customer->outstanding_debt ?? 0);
                        @endphp
                        <tr wire:key="customer-{{ $customer->id }}">
                            <td class="px-5 py-4">
                                <span class="font-medium text-gray-900">{{ $customer->name }}</span>
                                <span class="block max-w-sm truncate text-xs text-gray-500">{{ $customer->address ?: 'No address' }}</span>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $customer->phone ?: '-' }}</td>
                            <td class="px-5 py-4">
                                @if($customer->customer_level == 3)
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">Level 3 (VIP)</span>
                                @elseif($customer->customer_level == 2)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200">Level 2 (Silver)</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700">Level 1</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($customer->allow_credit)
                                    <span class="font-mono text-sm font-semibold text-gray-900">{{ number_format((float) $customer->credit_limit, 2) }}</span>
                                    @if((float) $customer->credit_limit <= 0)
                                        <span class="block text-[11px] text-gray-400 font-normal">Unlimited</span>
                                    @endif
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500">Disabled</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($debt > 0)
                                    <span class="font-mono text-sm font-bold text-red-600">{{ number_format($debt, 2) }}</span>
                                @else
                                    <span class="font-mono text-xs font-medium text-emerald-600">0.00</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('management.customers.show', $customer) }}" class="inline-flex rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100" title="Customer details"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                @if(auth('web')->user()?->hasPermissionTo('customers', 'edit'))<button type="button" wire:click="editCustomer({{ $customer->id }})" class="ml-2 rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100" title="Edit customer"><i data-lucide="pencil" class="h-4 w-4"></i></button>@endif
                                @if(auth('web')->user()?->hasPermissionTo('customers', 'delete'))<button type="button" wire:click="confirmDelete({{ $customer->id }})" class="ml-2 rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100" title="Delete customer"><i data-lucide="trash-2" class="h-4 w-4"></i></button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr wire:key="customers-empty"><td colspan="6" class="px-5 py-10 text-center text-gray-500">No customers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $customers->links() }}</div>
    </section>

    @if($createOpen && auth('web')->user()?->hasPermissionTo('customers', 'create'))
        <div wire:key="customer-create-modal" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between"><div><h2 class="text-lg font-semibold text-gray-900">Add customer</h2><p class="mt-1 text-sm text-gray-500">Create a customer record for sales and credit tracking.</p></div><button type="button" wire:click="closeCreate" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                <form wire:submit.prevent="createCustomer" class="mt-5 space-y-4">@include('livewire.partials.customer-form-fields', ['mode' => 'create'])<div class="flex justify-end gap-3 border-t border-gray-100 pt-5"><button type="button" wire:click="closeCreate" class="pos-button-secondary">Cancel</button><button type="submit" wire:loading.attr="disabled" wire:target="createCustomer" class="pos-button">Create customer</button></div></form>
            </section>
        </div>
    @endif

    @if($editingId && auth('web')->user()?->hasPermissionTo('customers', 'edit'))
        <div wire:key="customer-edit-modal-{{ $editingId }}" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between"><div><h2 class="text-lg font-semibold text-gray-900">Edit customer</h2><p class="mt-1 text-sm text-gray-500">Update contact details and credit settings.</p></div><button type="button" wire:click="closeEdit" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                <form wire:submit.prevent="updateCustomer" class="mt-5 space-y-4">@include('livewire.partials.customer-form-fields', ['mode' => 'edit'])<div class="flex justify-end gap-3 border-t border-gray-100 pt-5"><button type="button" wire:click="closeEdit" class="pos-button-secondary">Cancel</button><button type="submit" wire:loading.attr="disabled" wire:target="updateCustomer" class="pos-button">Save changes</button></div></form>
            </section>
        </div>
    @endif

    @if($deletingId && auth('web')->user()?->hasPermissionTo('customers', 'delete'))
        <div wire:key="customer-delete-modal-{{ $deletingId }}" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section style="width:340px;max-width:calc(100vw - 2rem);" class="rounded-xl bg-white p-4 shadow-xl">
                <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600"><i data-lucide="trash-2" class="h-4 w-4"></i></span><div class="min-w-0 flex-1"><h2 class="text-base font-semibold text-gray-900">Delete customer?</h2><p class="mt-1 text-xs text-gray-500">This action cannot be undone.</p></div></div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" wire:click="closeDelete" class="rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancel</button><button type="button" wire:click="deleteCustomer" wire:loading.attr="disabled" wire:target="deleteCustomer" style="background-color:#dc2626;color:#ffffff;" class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium shadow-sm transition hover:opacity-90 disabled:opacity-50"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i><span>Delete</span></button></div>
            </section>
        </div>
    @endif
</div>
