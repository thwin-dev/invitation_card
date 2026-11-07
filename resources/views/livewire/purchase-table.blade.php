<div class="pos-panel overflow-hidden">
    <div class="flex flex-wrap justify-end gap-2 border-b border-gray-100 p-4">
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
        <input
            wire:model.live.debounce.400ms="search"
            placeholder="Search supplier"
            class="w-72 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
        >
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Supplier</th>
                    <th class="px-5 py-3 text-center">Quantity</th>
                    <th class="px-5 py-3 text-right">Total</th>
                    <th class="px-5 py-3 text-right">Payable</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3">Recorded by</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($purchases as $purchase)
                    <tr class="transition hover:bg-gray-50/70">
                        <td class="px-5 py-4 text-gray-600">{{ $purchase->purchase_date?->format('d M Y') ?: '-' }}</td>
                        <td class="px-5 py-4">
                            @if($purchase->supplier)
                                <a href="{{ route('management.suppliers.show', $purchase->supplier) }}" class="font-medium text-brand-600 hover:underline">
                                    {{ $purchase->supplier_name }}
                                </a>
                            @else
                                <span class="font-medium text-gray-900">{{ $purchase->supplier_name }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">{{ $purchase->total_quantity }}</td>
                        <td class="px-5 py-4 text-right font-mono font-medium text-gray-900">{{ number_format((float) $purchase->total_price, 2) }}</td>
                        <td class="px-5 py-4 text-right font-mono {{ (float) $purchase->payable_amount > 0 ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                            {{ number_format((float) $purchase->payable_amount, 2) }}
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if((float) $purchase->payable_amount <= 0)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Paid</span>
                            @elseif((float) $purchase->paid_amount > 0)
                                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Partial</span>
                            @else
                                <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700">Unpaid</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $purchase->buyer?->name ?: '-' }}</td>
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center justify-end gap-2">
                                <a
                                    href="{{ route('management.purchases.show', $purchase) }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600 transition hover:bg-brand-100"
                                    title="View purchase"
                                >
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </a>
                                @if(auth('web')->user()?->hasPermissionTo('purchases', 'edit'))<a
                                    href="{{ route('management.purchases.edit', $purchase) }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100"
                                    title="Edit purchase"
                                >
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr wire:key="purchases-empty">
                        <td colspan="8" class="px-5 py-10 text-center text-gray-500">No purchases recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-gray-100 p-4">
        {{ $purchases->links() }}
    </div>
</div>
