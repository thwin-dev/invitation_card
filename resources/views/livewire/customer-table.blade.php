<div x-data x-init="window.lucide?.createIcons()">
    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-end gap-3 border-b border-gray-100 p-4">
            <input wire:model.live.debounce.400ms="search" placeholder="Search name, phone, or address" class="w-80 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 w-16 text-center">Logo</th>
                        <th class="px-5 py-3">Exhibitor</th>
                        <th class="px-5 py-3">Phone</th>
                        <th class="px-5 py-3">Level</th>
                        <th class="px-5 py-3 text-center">QR Attachment</th>
                        <th class="px-5 py-3">Links</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        <tr wire:key="exhibitor-{{ $customer->id }}" class="hover:bg-gray-50/60 transition">
                            {{-- Exhibitor Logo Column --}}
                            <td class="px-5 py-3.5 text-center">
                                @if($customer->logo_url)
                                    <button type="button" wire:click="openPreview('{{ $customer->logo_url }}', '{{ addslashes($customer->name) }} - Logo')" class="group relative inline-block focus:outline-none" title="Click to view logo">
                                        <img src="{{ $customer->logo_url }}" alt="{{ $customer->name }}" class="h-11 w-11 rounded-xl object-cover border border-gray-200 shadow-xs group-hover:ring-2 group-hover:ring-brand-400 transition">
                                        <span class="absolute inset-0 flex items-center justify-center rounded-xl bg-gray-900/30 opacity-0 group-hover:opacity-100 transition text-white">
                                            <i data-lucide="zoom-in" class="h-4 w-4"></i>
                                        </span>
                                    </button>
                                @else
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-sm font-bold text-gray-500 border border-gray-200/60">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Exhibitor Info --}}
                            <td class="px-5 py-3.5">
                                <a href="{{ route('management.exhibitors.show', $customer) }}" class="font-semibold text-gray-900 hover:text-brand-600 transition">{{ $customer->name }}</a>
                                <span class="block max-w-sm truncate text-xs text-gray-500 mt-0.5">{{ $customer->address ?: 'No address' }}</span>
                            </td>

                            {{-- Phone --}}
                            <td class="px-5 py-3.5 text-gray-600 font-mono text-xs">{{ $customer->phone ?: '-' }}</td>

                            {{-- Level --}}
                            <td class="px-5 py-3.5">
                                @if($customer->customer_level == 3)
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">Level 3 (VIP)</span>
                                @elseif($customer->customer_level == 2)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200">Level 2 (Silver)</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700">Level 1</span>
                                @endif
                            </td>

                            {{-- QR Attachment Column --}}
                            <td class="px-5 py-3.5 text-center">
                                @if($customer->qr_code_url)
                                    <button type="button" wire:click="openPreview('{{ $customer->qr_code_url }}', '{{ addslashes($customer->name) }} - QR Attachment')" class="group relative inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-2xs hover:border-brand-300 hover:bg-brand-50/50 hover:text-brand-700 transition" title="Click to view QR code">
                                        <img src="{{ $customer->qr_code_url }}" alt="QR" class="h-6 w-6 rounded object-cover">
                                        <span>View QR</span>
                                        <i data-lucide="maximize-2" class="h-3 w-3 text-gray-400 group-hover:text-brand-600"></i>
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                        <i data-lucide="qr-code" class="h-3.5 w-3.5 opacity-40"></i>
                                        <span>None</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Links Column with Link and Copy Button --}}
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div x-data="{ copied: false }" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50/90 px-3 py-1.5 text-xs">
                                    <a href="{{ $customer->invitation_url }}" target="_blank" class="font-mono text-xs font-semibold text-[#465fff] hover:text-[#3649db] hover:underline inline-flex items-center gap-1" title="Open invitation card in new tab">
                                        <span>/invitation/{{ $customer->id }}/{{ $customer->slug }}</span>
                                        <svg class="h-3 w-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                                    </a>
                                    <button type="button" 
                                            @click="window.copyToClipboard('{{ $customer->invitation_url }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Invitation link copied for {{ addslashes($customer->name) }}' } })); }).catch(() => { prompt('Copy invitation link:', '{{ $customer->invitation_url }}'); })"
                                            class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 font-medium transition cursor-pointer"
                                            :class="copied ? 'bg-emerald-100 text-emerald-700 font-semibold' : 'bg-white text-gray-700 hover:bg-gray-100 hover:text-gray-900 border border-gray-200 shadow-2xs'"
                                            title="Copy invitation link">
                                        <template x-if="!copied">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                <span class="text-[11px]">Copy</span>
                                            </span>
                                        </template>
                                        <template x-if="copied">
                                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                                <span class="text-[11px]">Copied!</span>
                                            </span>
                                        </template>
                                    </button>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('management.exhibitors.show', $customer) }}" class="inline-flex rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100 transition" title="Exhibitor details"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                @if(auth('web')->user()?->hasPermissionTo('customers', 'edit') || auth('web')->user()?->hasPermissionTo('exhibitors', 'edit'))<button type="button" wire:click="editCustomer({{ $customer->id }})" class="ml-2 rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100" title="Edit exhibitor"><i data-lucide="pencil" class="h-4 w-4"></i></button>@endif
                                @if(auth('web')->user()?->hasPermissionTo('customers', 'delete') || auth('web')->user()?->hasPermissionTo('exhibitors', 'delete'))<button type="button" wire:click="confirmDelete({{ $customer->id }})" class="ml-2 rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100" title="Delete exhibitor"><i data-lucide="trash-2" class="h-4 w-4"></i></button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr wire:key="exhibitors-empty"><td colspan="7" class="px-5 py-10 text-center text-gray-500">No exhibitors found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $customers->links() }}</div>
    </section>

    {{-- Attachment Preview Lightbox Modal --}}
    @if($previewUrl)
        <div wire:key="attachment-preview-modal" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-xs">
            <section class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-semibold text-gray-900 truncate">{{ $previewTitle ?: 'Attachment Preview' }}</h2>
                    <button type="button" wire:click="closePreview" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <div class="mt-4 flex flex-col items-center justify-center p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <img src="{{ $previewUrl }}" alt="Attachment" class="max-h-80 max-w-full rounded-lg object-contain shadow-xs">
                </div>
                <div class="mt-4 flex items-center justify-between gap-3">
                    <a href="{{ $previewUrl }}" download target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        <i data-lucide="download" class="h-4 w-4"></i>Download
                    </a>
                    <button type="button" wire:click="closePreview" class="pos-button-secondary text-xs">Close</button>
                </div>
            </section>
        </div>
    @endif

    {{-- Create Modal --}}
    @if($createOpen && (auth('web')->user()?->hasPermissionTo('customers', 'create') || auth('web')->user()?->hasPermissionTo('exhibitors', 'create')))
        <div wire:key="exhibitor-create-modal" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4 overflow-y-auto">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl my-8">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Add exhibitor</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Create a new exhibitor with contact details, logo and QR code.</p>
                    </div>
                    <button type="button" wire:click="closeCreate" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>
                <form wire:submit="createCustomer" novalidate class="mt-4 space-y-4">
                    @if($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                            <p class="font-semibold flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="h-4 w-4 text-red-600"></i>
                                Please check the following:
                            </p>
                            <ul class="mt-1 list-disc pl-4 space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @include('livewire.partials.customer-form-fields', ['mode' => 'create'])
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" wire:click="closeCreate" class="pos-button-secondary">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="createCustomer" class="pos-button">
                            <span wire:loading.remove wire:target="createCustomer">Create exhibitor</span>
                            <span wire:loading wire:target="createCustomer" class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($editingId && (auth('web')->user()?->hasPermissionTo('customers', 'edit') || auth('web')->user()?->hasPermissionTo('exhibitors', 'edit')))
        <div wire:key="exhibitor-edit-modal-{{ $editingId }}" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4 overflow-y-auto">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl my-8">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Edit exhibitor</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Update exhibitor details, logo and QR code attachment.</p>
                    </div>
                    <button type="button" wire:click="closeEdit" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>
                <form wire:submit="updateCustomer" novalidate class="mt-4 space-y-4">
                    @if($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                            <p class="font-semibold flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="h-4 w-4 text-red-600"></i>
                                Please check the following:
                            </p>
                            <ul class="mt-1 list-disc pl-4 space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @include('livewire.partials.customer-form-fields', ['mode' => 'edit'])
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" wire:click="closeEdit" class="pos-button-secondary">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateCustomer" class="pos-button">
                            <span wire:loading.remove wire:target="updateCustomer">Save changes</span>
                            <span wire:loading wire:target="updateCustomer" class="inline-flex items-center gap-1.5">
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    {{-- Import Modal --}}
    @if($importOpen && (auth('web')->user()?->hasPermissionTo('customers', 'import') || auth('web')->user()?->hasPermissionTo('exhibitors', 'import')))
        <div wire:key="exhibitor-import-modal" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <i data-lucide="file-spreadsheet" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Import Exhibitors</h2>
                            <p class="text-xs text-gray-500">Upload an Excel (.xlsx, .xls) or CSV file with exhibitor details</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeImport" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <div class="mt-4 rounded-lg bg-blue-50/70 p-3.5 border border-blue-100 text-xs text-blue-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">Need the format?</p>
                            <p class="mt-0.5 text-blue-700">Download our pre-styled template with column headers and sample data.</p>
                        </div>
                        <a href="{{ route('management.exhibitors.sample-excel') }}" class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
                            <i data-lucide="download" class="h-3.5 w-3.5"></i>Sample Excel
                        </a>
                    </div>
                </div>

                <form wire:submit="importCustomers" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Select Excel / CSV File</label>
                        <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-600 hover:file:bg-brand-100 cursor-pointer border border-gray-300 rounded-lg p-1.5">
                        <div wire:loading wire:target="importFile" class="mt-2 text-xs text-brand-600 font-medium flex items-center gap-2">
                            <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin"></i> Uploading file...
                        </div>
                        @error('importFile') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    @if(!empty($importErrors))
                        <div class="max-h-36 overflow-y-auto rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 space-y-1">
                            <p class="font-semibold">Warnings / Skipped entries:</p>
                            <ul class="list-disc pl-4 space-y-0.5">
                                @foreach($importErrors as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" wire:click="closeImport" class="pos-button-secondary">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="importCustomers" class="pos-button">
                            <span wire:loading.remove wire:target="importCustomers">Import exhibitors</span>
                            <span wire:loading wire:target="importCustomers" class="inline-flex items-center gap-2">
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if($deletingId && (auth('web')->user()?->hasPermissionTo('customers', 'delete') || auth('web')->user()?->hasPermissionTo('exhibitors', 'delete')))
        <div wire:key="exhibitor-delete-modal-{{ $deletingId }}" x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section style="width:340px;max-width:calc(100vw - 2rem);" class="rounded-xl bg-white p-4 shadow-xl">
                <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600"><i data-lucide="trash-2" class="h-4 w-4"></i></span><div class="min-w-0 flex-1"><h2 class="text-base font-semibold text-gray-900">Delete exhibitor?</h2><p class="mt-1 text-xs text-gray-500">This action cannot be undone.</p></div></div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" wire:click="closeDelete" class="rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancel</button><button type="button" wire:click="deleteCustomer" wire:loading.attr="disabled" wire:target="deleteCustomer" style="background-color:#dc2626;color:#ffffff;" class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium shadow-sm transition hover:opacity-90 disabled:opacity-50"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i><span>Delete</span></button></div>
            </section>
        </div>
    @endif
</div>
