<div x-data x-init="window.lucide?.createIcons()">
    <section class="pos-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-end gap-3 border-b border-gray-100 p-4">
            <select wire:model.live="direction" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">All directions</option>
                <option value="IN">In</option>
                <option value="OUT">Out</option>
            </select>
            <select wire:model.live="referenceType" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">All references</option>
                @foreach($referenceTypes as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            <input wire:model.live.debounce.400ms="search" placeholder="Search product, unit, or note" class="w-72 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Direction</th>
                        <th class="px-5 py-3">Product / unit</th>
                        <th class="px-5 py-3">Change</th>
                        <th class="px-5 py-3">Before / after</th>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($movements as $movement)
                        <tr wire:key="movement-{{ $movement->id }}">
                            <td class="px-5 py-4 text-gray-500">{{ $movement->created_at->format('d M Y H:i') }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $movement->direction === 'IN' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $movement->direction }}</span>
                            </td>
                            <td class="px-5 py-4"><span class="font-medium text-gray-900">{{ $movement->unit?->product?->name ?: 'Unknown product' }}</span><span class="block text-xs text-gray-500">{{ $movement->unit?->name ?: 'Unknown unit' }}</span><span class="block text-xs text-gray-500">{{ $movement->branch?->name ?: 'Main' }} · {{ $movement->lot?->batch_number }} {{ $movement->lot?->serial_number }}</span></td>
                            <td class="px-5 py-4 font-medium">{{ $movement->quantity }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $movement->quantity_before }} → {{ $movement->quantity_after }}</td>
                            <td class="px-5 py-4"><span class="font-medium text-gray-800">{{ $movement->reference_type ?: '-' }} @if($movement->reference_id)#{{ $movement->reference_id }}@endif</span><span class="block max-w-xs truncate text-xs text-gray-500">{{ $movement->note ?: '-' }}</span></td>
                            <td class="px-5 py-4 text-gray-600">{{ $movement->user?->name ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">No stock movements recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $movements->links() }}</div>
    </section>
</div>
