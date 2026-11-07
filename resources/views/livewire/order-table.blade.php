<div x-data x-init="window.lucide?.createIcons()">
    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-end gap-3 border-b border-gray-100 p-4">
            <input wire:model.live.debounce.400ms="search" placeholder="Search order or customer" class="w-72 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
            <input type="date" wire:model.live="from" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
            <input type="date" wire:model.live="to" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
            <select wire:model.live="status" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">All statuses</option>
                @foreach(['pending','confirmed','changed','delivered','cancelled'] as $statusOption)
                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                @endforeach
            </select>
            @if($search || $from || $to || $status)
                <button type="button" wire:click="resetFilters" class="pos-button-secondary">Clear</button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Estimate</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="px-5 py-4 font-medium text-gray-900">{{ $order->order_number }}</td>
                            <td class="px-5 py-4">{{ $order->customer?->name ?? 'Walk-in' }}</td>
                            <td class="px-5 py-4 text-gray-500">{{ $order->order_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ match($order->status) { 'delivered' => 'bg-emerald-50 text-emerald-700', 'cancelled' => 'bg-red-50 text-red-700', 'confirmed' => 'bg-brand-50 text-brand-700', 'changed' => 'bg-amber-50 text-amber-700', default => 'bg-gray-100 text-gray-600' } }}">{{ ucfirst($order->status) }}</span></td>
                            <td class="px-5 py-4 font-medium">{{ number_format((float) $order->estimated_price, 2) }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('management.orders.show', $order) }}" class="inline-flex rounded-lg bg-brand-50 p-2 text-brand-600 hover:bg-brand-100" title="View order"><i data-lucide="eye" class="h-4 w-4"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $orders->links() }}</div>
    </section>
</div>
