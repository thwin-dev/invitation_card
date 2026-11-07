<div x-data x-init="window.lucide?.createIcons()" x-on:open-user-create.window="$wire.openCreate()">
    <section class="pos-panel overflow-hidden">
        {{-- Search and Role Filter --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4">
            <div class="flex flex-wrap items-center gap-3">
                <input
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search name, code, or email"
                    class="w-72 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                >
                <select
                    wire:model.live="role"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                >
                    <option value="">All roles</option>
                    @foreach($roles as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div wire:loading class="text-xs font-medium text-brand-600">
                 
                Updating employees...
            </div>
        </div>

        {{-- Employees Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Last login</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="px-5 py-4">
                                <span class="font-medium text-gray-900">{{ $user->name }}</span>
                                <span class="block text-xs text-gray-500">{{ $user->user_code }}</span>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $user->email }}</td>
                            <td class="px-5 py-4">
                                @if($user->role)
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                        {{ $user->role }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-500">
                                {{ $user->last_login_at?->format('d M Y H:i') ?: 'Not recorded' }}
                            </td>
                            <td class="px-5 py-4">
                                @if($canEdit)
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $user->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleStatus({{ $user->id }})"
                                    class="inline-flex cursor-pointer items-center disabled:opacity-50"
                                    title="{{ $user->prohibition_flag ? 'Click to activate' : 'Click to prohibit' }}"
                                    aria-label="Toggle employee status"
                                >
                                    <span class="relative block h-6 w-11 rounded-full p-1 transition-colors {{ ! $user->prohibition_flag ? 'bg-[#465fff]' : 'bg-gray-300' }}">
                                        <span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform {{ ! $user->prohibition_flag ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </span>
                                </button>
                                @else
                                    <span class="text-xs text-gray-500">{{ $user->prohibition_flag ? 'Inactive' : 'Active' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a
                                    href="{{ route('management.users.show', $user) }}"
                                    class="inline-flex rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100"
                                    title="Employee details"
                                >
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </a>
                                @if($canEdit)
                                <button
                                    type="button"
                                    wire:click="editUser({{ $user->id }})"
                                    class="ml-2 inline-flex rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100"
                                    title="Edit employee"
                                >
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                                No employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">
            {{ $users->links() }}
        </div>
    </section>

    {{-- Create Employee Modal --}}
    @if($canCreate && $createOpen)
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Add employee</h2>
                        <p class="mt-1 text-sm text-gray-500">Create an account for management or POS access.</p>
                    </div>
                    <button type="button" wire:click="closeCreate" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form wire:submit.prevent="createUser" class="mt-5 space-y-4">
                    <div>
                        <label for="createCode" class="block text-xs font-medium text-gray-700">User code</label>
                        <input
                            type="text"
                            id="createCode"
                            wire:model="createCode"
                            placeholder="e.g. EMP-001"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        >
                        @error('createCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="createName" class="block text-xs font-medium text-gray-700">Name</label>
                        <input
                            type="text"
                            id="createName"
                            wire:model="createName"
                            placeholder="Full name"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        >
                        @error('createName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="createEmail" class="block text-xs font-medium text-gray-700">Email</label>
                        <input
                            type="email"
                            id="createEmail"
                            wire:model="createEmail"
                            placeholder="name@example.com"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        >
                        @error('createEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if($canAssignRole)
                    <div>
                        <label for="createRole" class="block text-xs font-medium text-gray-700">Role</label>
                        <select id="createRole" wire:model="createRoleId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                            <option value="">Choose a role</option>
                            @foreach($roleOptions as $roleOption)
                                <option value="{{ $roleOption->id }}">{{ $roleOption->name }}</option>
                            @endforeach
                        </select>
                        @error('createRoleId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    @else
                        <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">New employees receive the Cashier role. Role assignment requires permission to manage roles.</p>
                    @endif

                    <div>
                        <label for="createPassword" class="block text-xs font-medium text-gray-700">Password</label>
                        <input
                            type="password"
                            id="createPassword"
                            wire:model="createPassword"
                            placeholder="Minimum 8 characters"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        >
                        @error('createPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" wire:click="closeCreate" class="pos-button-secondary">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="createUser" class="pos-button">
                            Create employee
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    {{-- Edit Employee Modal --}}
    @if($canEdit && $editingId)
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Edit employee</h2>
                        <p class="mt-1 text-sm text-gray-500">Update account credentials and role.</p>
                    </div>
                    <button type="button" wire:click="closeEdit" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form wire:submit.prevent="updateUser" class="mt-5 space-y-4">
                    <div>
                        <label for="editCode" class="block text-xs font-medium text-gray-700">User code</label>
                        <input
                            type="text"
                            id="editCode"
                            wire:model="editCode"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        >
                        @error('editCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="editName" class="block text-xs font-medium text-gray-700">Name</label>
                        <input
                            type="text"
                            id="editName"
                            wire:model="editName"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        >
                        @error('editName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="editEmail" class="block text-xs font-medium text-gray-700">Email</label>
                        <input
                            type="email"
                            id="editEmail"
                            wire:model="editEmail"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        >
                        @error('editEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if($canAssignRole)
                    <div>
                        <label for="editRole" class="block text-xs font-medium text-gray-700">Role</label>
                        <select id="editRole" wire:model="editRoleId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                            <option value="">Choose a role</option>
                            @foreach($roleOptions as $roleOption)
                                <option value="{{ $roleOption->id }}">{{ $roleOption->name }}</option>
                            @endforeach
                        </select>
                        @error('editRoleId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    @endif

                    <div>
                        <label for="editPassword" class="block text-xs font-medium text-gray-700">New password <span class="font-normal text-gray-400">(leave blank to keep current)</span></label>
                        <input
                            type="password"
                            id="editPassword"
                            wire:model="editPassword"
                            placeholder="Minimum 8 characters"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        >
                        @error('editPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" wire:click="closeEdit" class="pos-button-secondary">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateUser" class="pos-button">
                            Save changes
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
