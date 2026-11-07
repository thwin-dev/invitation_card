<div x-data x-init="window.lucide?.createIcons()" x-on:open-expense-create.window="$wire.openCreate()">
    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4">
            <div class="text-sm">
                <span class="text-gray-500">Filtered total</span>
                <strong class="ml-2 text-gray-900">{{ number_format((float) $filteredTotal, 2) }}</strong>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <div wire:ignore>
                    <input
                        type="date"
                        wire:model.live="from"
                        placeholder="From date"
                        onclick="this.showPicker?.()"
                        x-init="const ph = $el.getAttribute('placeholder') || 'From date';
                        if (window.flatpickr && !$el._flatpickr) {
                            flatpickr($el, {
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd/m/Y',
                                altInputPlaceholder: ph,
                                altInputClass: 'rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15',
                                allowInput: true,
                                defaultDate: $wire.from || null,
                                onReady: (_, __, instance) => {
                                    if (instance.altInput) instance.altInput.placeholder = ph;
                                },
                                onClose: (_, __, instance) => {
                                    if (!instance.input.value && instance.altInput) instance.altInput.placeholder = ph;
                                },
                                onChange: (dates, dateStr) => {
                                    $wire.set('from', dateStr);
                                }
                            });
                        }"
                        class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                    >
                </div>
                <div wire:ignore>
                    <input
                        type="date"
                        wire:model.live="to"
                        placeholder="To date"
                        onclick="this.showPicker?.()"
                        x-init="const ph = $el.getAttribute('placeholder') || 'To date';
                        if (window.flatpickr && !$el._flatpickr) {
                            flatpickr($el, {
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd/m/Y',
                                altInputPlaceholder: ph,
                                altInputClass: 'rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15',
                                allowInput: true,
                                defaultDate: $wire.to || null,
                                onReady: (_, __, instance) => {
                                    if (instance.altInput) instance.altInput.placeholder = ph;
                                },
                                onClose: (_, __, instance) => {
                                    if (!instance.input.value && instance.altInput) instance.altInput.placeholder = ph;
                                },
                                onChange: (dates, dateStr) => {
                                    $wire.set('to', dateStr);
                                }
                            });
                        }"
                        class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                    >
                </div>
                <select wire:model.live="kind" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                    <option value="">All types</option>
                    <option value="expense">Expenses</option>
                    <option value="profit">Profit</option>
                </select>
                <input
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search title or type"
                    class="w-72 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                >
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Title</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($expenses as $expense)
                        <tr wire:key="expense-{{ $expense->id }}" class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4 text-gray-600">{{ $expense->expense_date?->format('d M Y') ?: '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $expense->is_profit ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                    {{ $expense->is_profit ? 'Profit' : $expense->type }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-medium text-gray-900">{{ $expense->title }}</td>
                            <td class="max-w-sm truncate px-5 py-4 text-gray-500">{{ $expense->description ?: '-' }}</td>
                            <td class="px-5 py-4 text-right font-medium text-gray-900">{{ number_format((float) $expense->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">No expenses found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">
            {{ $expenses->links() }}
        </div>
    </section>

    @if($createOpen && auth('web')->user()?->hasPermissionTo('expenses', 'create'))
        <div x-data x-cloak x-show="true" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Add expense</h2>
                        <p class="mt-1 text-sm text-gray-500">Record an operating expense or income entry.</p>
                    </div>
                    <button type="button" wire:click="closeCreate" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form wire:submit.prevent="createExpense" class="mt-5 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Type
                            <input
                                wire:model="createType"
                                required
                                placeholder="e.g. Utilities, Rent"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            @error('createType') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block text-sm font-medium text-gray-700">
                            Period
                            <input
                                wire:model="createPeriod"
                                placeholder="e.g. September 2026"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            @error('createPeriod') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Expense date
                            <input
                                type="date"
                                wire:model="createExpenseDate"
                                required
                                placeholder="dd/mm/yyyy"
                                onclick="this.showPicker?.()"
                                x-init="const ph = $el.getAttribute('placeholder') || 'dd/mm/yyyy';
                                if (window.flatpickr && !$el._flatpickr) {
                                    flatpickr($el, {
                                        dateFormat: 'Y-m-d',
                                        altInput: true,
                                        altFormat: 'd/m/Y',
                                        altInputPlaceholder: ph,
                                        altInputClass: $el.className,
                                        allowInput: true,
                                        defaultDate: $wire.createExpenseDate || null,
                                        onReady: (_, __, instance) => {
                                            if (instance.altInput) instance.altInput.placeholder = ph;
                                        },
                                        onClose: (_, __, instance) => {
                                            if (!instance.input.value && instance.altInput) instance.altInput.placeholder = ph;
                                        },
                                        onChange: (dates, dateStr) => {
                                            $wire.set('createExpenseDate', dateStr);
                                        }
                                    });
                                }"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            @error('createExpenseDate') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block text-sm font-medium text-gray-700">
                            Amount
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model="createAmount"
                                required
                                placeholder="0.00"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            @error('createAmount') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="block text-sm font-medium text-gray-700">
                        Title
                        <input
                            wire:model="createTitle"
                            required
                            placeholder="Expense title"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        >
                        @error('createTitle') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block text-sm font-medium text-gray-700">
                        Description
                        <textarea
                            wire:model="createDescription"
                            rows="2"
                            placeholder="Optional details"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                        ></textarea>
                        @error('createDescription') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-700">
                        <span>Mark as income / profit</span>
                        <button
                            type="button"
                            wire:click="$toggle('createIsProfit')"
                            class="relative h-6 w-11 rounded-full p-1 transition-colors {{ $createIsProfit ? 'bg-[#465fff]' : 'bg-gray-300' }}"
                            aria-label="Toggle profit"
                        >
                            <span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform {{ $createIsProfit ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </label>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" wire:click="closeCreate" class="pos-button-secondary">
                            Cancel
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="createExpense" class="pos-button">
                            Record expense
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
