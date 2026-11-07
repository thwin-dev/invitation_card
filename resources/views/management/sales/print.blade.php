<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $sale->voucher_code }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page { margin: 12mm; }
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            .receipt { max-width: none !important; padding: 0 !important; box-shadow: none !important; border: 0 !important; }
        }
    </style>
</head>
<body class="bg-gray-50 p-4 text-gray-900 sm:p-8">
    @php($currency = $sale->currency_code_snapshot ?: 'MMK')
    <main class="receipt pos-panel mx-auto max-w-xl overflow-hidden shadow-sm">
        <div class="no-print flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <a href="{{ route('management.sales.show', $sale) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to sale</a>
            <button type="button" onclick="window.print()" class="pos-button"><i data-lucide="printer" class="mr-2 h-4 w-4"></i>Print receipt</button>
        </div>

        <div class="p-5 sm:p-8">
            <header class="border-b border-dashed border-gray-300 pb-5 text-center">
                <span class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600"><i data-lucide="store" class="h-5 w-5"></i></span>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">{{ $sale->branch_name_snapshot ?: $sale->branch?->name ?: 'Sale POS' }}</h1>
                @if($sale->branch_address_snapshot)<p class="mt-1 whitespace-pre-line text-sm text-gray-600">{{ $sale->branch_address_snapshot }}</p>@endif
                @if($sale->branch_phone_snapshot)<p class="mt-1 text-sm text-gray-600">{{ $sale->branch_phone_snapshot }}</p>@endif
                @if($sale->receipt_header_snapshot)<p class="mt-4 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $sale->receipt_header_snapshot }}</p>@endif
            </header>

            <section class="flex justify-between gap-4 border-b border-dashed border-gray-300 py-5 text-sm">
                <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Receipt</p><p class="mt-1 break-all font-mono font-semibold text-gray-900">{{ $sale->voucher_code }}</p><p class="mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Customer</p><p class="mt-1 font-medium text-gray-900">{{ $sale->customer?->name ?: 'Walk-in customer' }}</p></div>
                <div class="shrink-0 text-right"><p class="font-medium text-gray-900">{{ $sale->sale_date?->format('d M Y') }}</p><p class="mt-1 text-gray-500">{{ $sale->seller?->name ?: 'POS staff' }}</p><p class="mt-4 text-xs text-gray-500">Sold by</p></div>
            </section>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[400px] text-left text-sm">
                    <thead class="border-b border-gray-200 text-[11px] font-semibold uppercase tracking-wide text-gray-500"><tr><th class="py-3 pr-3">Item</th><th class="py-3 text-right">Qty</th><th class="py-3 text-right">Price</th><th class="py-3 pl-3 text-right">Amount</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sale->items as $item)
                            <tr><td class="py-3 pr-3"><span class="block font-medium text-gray-900">{{ $item->unit?->product?->name ?: 'Unknown product' }}</span><span class="mt-0.5 block text-xs text-gray-500">{{ $item->unit?->name ?: 'Unknown unit' }}</span>@if($item->discount_name)<span class="mt-1 block text-xs text-emerald-700">{{ $item->discount_name }} ({{ $item->discount_value }} {{ $item->discount_type === 'percentage' ? '%' : $currency.' / unit' }}) · −{{ number_format((float) $item->discount_amount, 2) }} {{ $currency }}</span>@endif</td><td class="py-3 text-right tabular-nums">{{ $item->quantity }}</td><td class="py-3 text-right tabular-nums">{{ number_format((float) $item->price, 2) }}</td><td class="py-3 pl-3 text-right font-medium tabular-nums">{{ number_format($item->net_total, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <dl class="ml-auto mt-5 max-w-xs space-y-2 rounded-xl bg-gray-50 p-4 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Quantity</dt><dd class="font-medium tabular-nums">{{ $sale->total_quantity }}</dd></div>
                <div class="flex justify-between gap-4"><dt>Subtotal</dt><dd>{{ number_format((float) $sale->subtotal, 2) }} {{ $currency }}</dd></div>
                <div class="flex justify-between gap-4 text-emerald-700"><dt>Item discounts</dt><dd>−{{ number_format((float) $sale->discount_amount, 2) }} {{ $currency }}</dd></div>
                <div class="flex justify-between gap-4 border-t border-gray-200 pt-3 text-base font-bold"><dt>Total</dt><dd class="tabular-nums">{{ number_format((float) $sale->total_price, 2) }} {{ $currency }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Paid</dt><dd class="font-medium tabular-nums text-emerald-700">{{ number_format((float) $sale->paid_amount, 2) }} {{ $currency }}</dd></div>
                @if((float) $sale->credit_amount > 0)<div class="flex justify-between gap-4"><dt class="text-gray-500">Credit due</dt><dd class="font-medium tabular-nums text-amber-700">{{ number_format((float) $sale->credit_amount, 2) }} {{ $currency }}</dd></div>@endif
            </dl>

            @if($sale->receipt_footer_snapshot)<footer class="mt-6 border-t border-dashed border-gray-300 pt-5 text-center text-sm leading-6 text-gray-600"><p class="whitespace-pre-line">{{ $sale->receipt_footer_snapshot }}</p></footer>@endif
        </div>
    </main>
</body>
</html>
