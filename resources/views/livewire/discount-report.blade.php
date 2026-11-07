<div class="space-y-5">
    <div class="pos-panel flex flex-wrap gap-4 p-4">
        <label class="text-sm">From<input type="date" data-native-date wire:model.live="from" class="mt-1 block rounded-lg border border-gray-300 px-3 py-2">@error('from')<span class="text-red-600">{{ $message }}</span>@enderror</label>
        <label class="text-sm">To<input type="date" data-native-date wire:model.live="to" class="mt-1 block rounded-lg border border-gray-300 px-3 py-2">@error('to')<span class="text-red-600">{{ $message }}</span>@enderror</label>
        <label class="text-sm">Branch<select wire:model.live="branchId" class="mt-1 block rounded-lg border border-gray-300 px-3 py-2"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>@error('branchId')<span class="text-red-600">{{ $message }}</span>@enderror</label>
        <span wire:loading.delay role="status" class="self-end text-sm text-gray-500">Updating report…</span>
    </div>
    <section class="pos-panel overflow-hidden">
        <div class="p-4"><h2 class="font-semibold">Discounts by branch</h2><p class="mt-1 text-xs text-gray-500">Completed discounted sales only. Held and cancelled sales are excluded. Amounts are grouped by receipt currency.</p></div>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr><th>Branch</th><th>Currency</th><th>Discounted sales</th><th>Subtotal</th><th>Discounts</th><th>Net sales</th></tr></thead><tbody>
            @forelse($summaries as $summary)<tr><td>{{ $summary->branch?->name ?: 'Former branch' }}</td><td>{{ $summary->currency_code_snapshot }}</td><td>{{ $summary->sale_count }}</td><td>{{ number_format((float) $summary->gross, 2) }}</td><td class="font-semibold text-emerald-700">{{ number_format((float) $summary->discounts, 2) }}</td><td>{{ number_format((float) $summary->net, 2) }}</td></tr>
            @empty<tr><td colspan="6" class="p-8 text-center text-gray-500">No discounted sales in this period.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="pos-panel overflow-hidden">
        <h2 class="p-4 font-semibold">Discounted sale details</h2>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr><th>Voucher / date</th><th>Branch</th><th>Applied rules</th><th>Subtotal</th><th>Discount</th><th>Net total</th></tr></thead><tbody>
            @forelse($sales as $sale)<tr wire:key="discount-sale-{{ $sale->id }}"><td>@if(auth('web')->user()?->hasPermissionTo('sales', 'view'))<a href="{{ route('management.sales.show', $sale) }}" class="font-medium text-brand-600">{{ $sale->voucher_code }}</a>@else {{ $sale->voucher_code }} @endif<span class="block text-xs text-gray-500">{{ $sale->sale_date?->format('d M Y') }}</span></td><td>{{ $sale->branch_name_snapshot ?: $sale->branch?->name }}</td><td>{{ $sale->items->where('discount_amount', '>', 0)->pluck('discount_name')->unique()->implode(', ') }}</td><td>{{ number_format((float) $sale->subtotal, 2) }}</td><td>{{ number_format((float) $sale->discount_amount, 2) }}</td><td>{{ number_format((float) $sale->total_price, 2) }} {{ $sale->currency_code_snapshot }}</td></tr>
            @empty<tr><td colspan="6" class="p-8 text-center text-gray-500">No discounted sales found.</td></tr>@endforelse
        </tbody></table></div>
        <div class="p-4">{{ $sales->links() }}</div>
    </section>
</div>
