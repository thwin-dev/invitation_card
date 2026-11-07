<div x-data x-init="window.lucide?.createIcons()">
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('management.users.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition hover:text-brand-700">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to employees
            </a>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900">{{ $name }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $user_code }} · {{ $email }} @if($phone) · {{ $phone }} @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($canEditUsers)
            <button
                type="button"
                wire:click="toggleStatus"
                wire:loading.attr="disabled"
                wire:target="toggleStatus"
                class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition disabled:opacity-50 {{ $prohibition_flag ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100' }}"
                title="{{ $prohibition_flag ? 'Click to activate account' : 'Click to prohibit account' }}"
            >
                <i data-lucide="{{ $prohibition_flag ? 'check-circle' : 'ban' }}" class="h-4 w-4"></i>
                <span wire:loading.remove wire:target="toggleStatus">
                    {{ $prohibition_flag ? 'Activate account' : 'Prohibit account' }}
                </span>
                <span wire:loading wire:target="toggleStatus">
                    Updating...
                </span>
            </button>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Edit Employee Form (2 columns wide on large screens) --}}
        <div class="lg:col-span-2">
            @if($canEditUsers)
            <section class="pos-panel p-6">
                <div class="mb-5 border-b border-gray-100 pb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Edit employee details</h2>
                    <p class="mt-1 text-sm text-gray-500">Update credentials, contact information, and role without page reload.</p>
                </div>

                <form wire:submit.prevent="updateUser" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="user_code" class="block text-xs font-medium text-gray-700">User code</label>
                            <input
                                type="text"
                                id="user_code"
                                wire:model="user_code"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            @error('user_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="name" class="block text-xs font-medium text-gray-700">Name</label>
                            <input
                                type="text"
                                id="name"
                                wire:model="name"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-medium text-gray-700">Email</label>
                            <input
                                type="email"
                                id="email"
                                wire:model="email"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-medium text-gray-700">Phone</label>
                            <input
                                type="text"
                                id="phone"
                                wire:model="phone"
                                placeholder="e.g. 0912345678"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if($canAssignRole)
                        <div class="sm:col-span-2">
                            <label for="role" class="block text-xs font-medium text-gray-700">Role</label>
                            <select id="role" wire:model="roleId" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                                <option value="">Choose a role</option>
                                @foreach($roleOptions as $roleOption)
                                    <option value="{{ $roleOption->id }}">{{ $roleOption->name }}</option>
                                @endforeach
                            </select>
                            @error('roleId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @endif

                        <div class="sm:col-span-2">
                            <label for="password" class="block text-xs font-medium text-gray-700">
                                New password <span class="font-normal text-gray-400">(leave blank to keep current password)</span>
                            </label>
                            <input
                                type="password"
                                id="password"
                                wire:model="password"
                                placeholder="Minimum 8 characters"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="updateUser"
                            class="pos-button"
                        >
                            <span wire:loading.remove wire:target="updateUser">Save changes</span>
                            <span wire:loading wire:target="updateUser">Saving...</span>
                        </button>
                    </div>
                </form>
            </section>
            @else
                <section class="pos-panel p-6">
                    <h2 class="font-semibold text-gray-900">Employee details</h2>
                    <p class="mt-4 text-sm text-gray-600">{{ $user->name }} · {{ $user->email }}</p>
                    <p class="mt-1 text-sm text-gray-600">Role: {{ $user->role ?: 'Unassigned' }}</p>
                </section>
            @endif
        </div>

        {{-- Employee Profile Info Card --}}
        <div>
            <section class="pos-panel p-6">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-xl font-bold text-brand-600">
                        {{ strtoupper(substr($name ?: 'E', 0, 1)) }}
                    </span>
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $name }}</h3>
                        <span class="mt-0.5 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                            {{ $user->role ?: 'Standard user' }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 space-y-3 border-t border-gray-100 pt-4 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Account created</span>
                        <span class="font-medium text-gray-700">{{ $user->created_at?->format('d M Y') ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Status</span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ ! $prohibition_flag ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                            <span class="mr-1.5 h-1.5 w-1.5 rounded-full {{ ! $prohibition_flag ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                            {{ ! $prohibition_flag ? 'Active' : 'Prohibited' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">User code</span>
                        <span class="font-medium text-gray-700">{{ $user_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Email address</span>
                        <span class="font-medium text-gray-700">{{ $email }}</span>
                    </div>
                    @if($phone)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Phone</span>
                            <span class="font-medium text-gray-700">{{ $phone }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total sales</span>
                        <span class="font-medium text-gray-900">
                            {{ number_format($totalSalesAmount, 2) }}
                            <span class="text-gray-400">({{ $salesCount }} {{ Str::plural('voucher', $salesCount) }})</span>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Last login</span>
                        <span class="font-medium text-gray-700">{{ $user->last_login_at?->format('d M Y H:i') ?: 'Not recorded' }}</span>
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Recent Sales Activity --}}
    @if($canViewSales)
    <section class="pos-panel mt-6 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Recent sales processed</h2>
                <p class="mt-0.5 text-xs text-gray-500">Recent sales transactions handled by this employee.</p>
            </div>
            @if($salesCount > 0 && auth('web')->user()?->hasPermissionTo('sales', 'view'))
                <a href="{{ route('management.sales.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                    View all sales →
                </a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Voucher</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentSales as $sale)
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $sale->voucher_code }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $sale->sale_date?->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                {{ $sale->customer?->name ?: 'Walk-in customer' }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                {{ number_format((float) $sale->total_price, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $sale->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a
                                    href="{{ route('management.sales.show', $sale) }}"
                                    class="inline-flex rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100"
                                    title="View sale"
                                >
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                No sales recorded for this employee yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>
