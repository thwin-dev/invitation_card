<?php

namespace App\Livewire;

use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Sale\Models\Sale;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReport extends Component
{
    public string $periodMode = 'daily'; // 'daily', 'weekly', 'monthly'
    public string $from = '';
    public string $to = '';
    public string $sellerId = '';
    public string $customerId = '';
    public string $activeTab = 'breakdown'; // 'breakdown', 'products'

    public function mount(?string $from = null, ?string $to = null, ?string $mode = null): void
    {
        $this->from = $from ?: (string) request('from', $this->from ?: now()->startOfMonth()->toDateString());
        $this->to = $to ?: (string) request('to', $this->to ?: now()->toDateString());
        $mode = $mode ?: (string) request('mode', $this->periodMode ?: 'daily');
        if (in_array($mode, ['daily', 'weekly', 'monthly'], true)) {
            $this->periodMode = $mode;
        } else {
            $this->periodMode = 'daily';
        }
    }

    public function setPeriodMode(string $mode): void
    {
        if (in_array($mode, ['daily', 'weekly', 'monthly'], true)) {
            $this->periodMode = $mode;
        }
    }

    public function setActiveTab(string $tab): void
    {
        if (in_array($tab, ['breakdown', 'products'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function setToday(): void
    {
        $this->from = now()->toDateString();
        $this->to = now()->toDateString();
        $this->dispatch('date-range-updated', from: $this->from, to: $this->to);
    }

    public function setThisWeek(): void
    {
        $this->from = now()->startOfWeek()->toDateString();
        $this->to = now()->endOfWeek()->toDateString();
        $this->dispatch('date-range-updated', from: $this->from, to: $this->to);
    }

    public function setThisMonth(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
        $this->dispatch('date-range-updated', from: $this->from, to: $this->to);
    }

    public function setLastMonth(): void
    {
        $this->from = now()->subMonth()->startOfMonth()->toDateString();
        $this->to = now()->subMonth()->endOfMonth()->toDateString();
        $this->dispatch('date-range-updated', from: $this->from, to: $this->to);
    }

    public function setLast90Days(): void
    {
        $this->from = now()->subDays(90)->toDateString();
        $this->to = now()->toDateString();
        $this->dispatch('date-range-updated', from: $this->from, to: $this->to);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->calculateReportData();
        $rows = $data['breakdown'];
        $periodMode = $this->periodMode;
        $from = $data['fromDate']->toDateString();
        $to = $data['toDate']->toDateString();

        return response()->streamDownload(function () use ($rows, $data, $periodMode): void {
            $handle = fopen('php://output', 'w');
            // Write BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Summary Header
            fputcsv($handle, ['Sales Report', ucfirst($periodMode) . ' Breakdown']);
            fputcsv($handle, ['Period', $data['fromDate']->format('Y-m-d') . ' to ' . $data['toDate']->format('Y-m-d')]);
            fputcsv($handle, ['Total Sales (MMK)', number_format($data['salesTotal'], 2)]);
            fputcsv($handle, ['Paid Amount (MMK)', number_format($data['paidTotal'], 2)]);
            fputcsv($handle, ['Credit Amount (MMK)', number_format($data['creditTotal'], 2)]);
            fputcsv($handle, ['Total Invoices', $data['invoiceCount']]);
            fputcsv($handle, ['Total Units Sold', $data['quantity']]);
            fputcsv($handle, []);

            // Data Columns
            fputcsv($handle, [
                'Period / Date',
                'Invoices Count',
                'Units Sold',
                'Total Sales (MMK)',
                'Paid (MMK)',
                'Credit (MMK)',
                'Avg Order Value (MMK)',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['label'],
                    $row['count'],
                    $row['qty'],
                    number_format($row['total'], 2, '.', ''),
                    number_format($row['paid'], 2, '.', ''),
                    number_format($row['credit'], 2, '.', ''),
                    number_format($row['avg_order'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, "sales-report-{$periodMode}-{$from}-to-{$to}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $sellers = User::query()->orderBy('name')->get(['id', 'name']);
        $customers = PosCustomer::query()->orderBy('name')->get(['id', 'name']);
        $reportData = $this->calculateReportData();

        return view('livewire.sales-report', array_merge($reportData, [
            'sellers' => $sellers,
            'customers' => $customers,
        ]));
    }

    private function calculateReportData(): array
    {
        try {
            $fromDate = Carbon::parse($this->from ?: now()->startOfMonth())->startOfDay();
        } catch (\Throwable) {
            $fromDate = now()->startOfMonth()->startOfDay();
        }

        try {
            $toDate = Carbon::parse($this->to ?: now())->endOfDay();
        } catch (\Throwable) {
            $toDate = now()->endOfDay();
        }

        if ($fromDate->gt($toDate)) {
            $temp = $fromDate;
            $fromDate = $toDate->copy()->startOfDay();
            $toDate = $temp->copy()->endOfDay();
        }

        $query = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->with(['items.unit.product', 'customer', 'seller']);

        if ($this->sellerId !== '') {
            $query->where('sale_by', $this->sellerId);
        }

        if ($this->customerId !== '') {
            $query->where('customer_id', $this->customerId);
        }

        $sales = $query->latest('sale_date')->get();

        $salesTotal = (float) $sales->sum('total_price');
        $paidTotal = (float) $sales->sum('paid_amount');
        $creditTotal = (float) $sales->sum('credit_amount');
        $quantity = (int) $sales->sum('total_quantity');
        $invoiceCount = $sales->count();
        $averageOrderValue = $invoiceCount > 0 ? $salesTotal / $invoiceCount : 0.0;

        // Group breakdown based on periodMode
        $breakdown = match ($this->periodMode) {
            'monthly' => $this->groupMonthly($sales),
            'weekly' => $this->groupWeekly($sales),
            default => $this->groupDaily($sales),
        };

        // Top selling products in this filtered range
        $topProducts = $sales->flatMap(fn ($sale) => $sale->items)
            ->groupBy('product_unit_id')
            ->map(function ($items) {
                $first = $items->first();
                $unitName = $first?->unit?->name ?? 'Unit';
                $productName = $first?->unit?->product?->name ?? 'Unknown Product';
                $productCode = $first?->unit?->product?->code ?? ($first?->unit?->code ?? '-');
                $qty = (int) $items->sum('quantity');
                $revenue = (float) $items->sum(fn ($i) => (float)$i->quantity * (float)$i->price);
                return [
                    'product_name' => $productName,
                    'unit_name' => $unitName,
                    'code' => $productCode,
                    'quantity' => $qty,
                    'revenue' => $revenue,
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        return [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'sales' => $sales,
            'salesTotal' => $salesTotal,
            'paidTotal' => $paidTotal,
            'creditTotal' => $creditTotal,
            'quantity' => $quantity,
            'invoiceCount' => $invoiceCount,
            'averageOrderValue' => $averageOrderValue,
            'breakdown' => $breakdown,
            'topProducts' => $topProducts,
        ];
    }

    private function groupDaily($sales)
    {
        return $sales->groupBy(fn ($sale) => Carbon::parse($sale->sale_date)->toDateString())
            ->map(function ($group, $key) {
                $count = $group->count();
                $qty = (int) $group->sum('total_quantity');
                $total = (float) $group->sum('total_price');
                $paid = (float) $group->sum('paid_amount');
                $credit = (float) $group->sum('credit_amount');
                return [
                    'period_key' => $key,
                    'label' => Carbon::parse($key)->format('d M Y (D)'),
                    'sort_key' => $key,
                    'count' => $count,
                    'qty' => $qty,
                    'total' => $total,
                    'paid' => $paid,
                    'credit' => $credit,
                    'avg_order' => $count > 0 ? $total / $count : 0.0,
                ];
            })
            ->sortByDesc('sort_key')
            ->values();
    }

    private function groupWeekly($sales)
    {
        return $sales->groupBy(function ($sale) {
            $c = Carbon::parse($sale->sale_date);
            return $c->format('o-\WW');
        })->map(function ($group, $key) {
            $count = $group->count();
            $qty = (int) $group->sum('total_quantity');
            $total = (float) $group->sum('total_price');
            $paid = (float) $group->sum('paid_amount');
            $credit = (float) $group->sum('credit_amount');

            $firstDate = Carbon::parse($group->min('sale_date'))->startOfWeek();
            $lastDate = Carbon::parse($group->max('sale_date'))->endOfWeek();

            return [
                'period_key' => $key,
                'label' => 'Week ' . substr($key, 6) . ' (' . $firstDate->format('M d') . ' - ' . $lastDate->format('M d, Y') . ')',
                'sort_key' => $key,
                'count' => $count,
                'qty' => $qty,
                'total' => $total,
                'paid' => $paid,
                'credit' => $credit,
                'avg_order' => $count > 0 ? $total / $count : 0.0,
            ];
        })
        ->sortByDesc('sort_key')
        ->values();
    }

    private function groupMonthly($sales)
    {
        return $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->sale_date)->format('Y-m');
        })->map(function ($group, $key) {
            $count = $group->count();
            $qty = (int) $group->sum('total_quantity');
            $total = (float) $group->sum('total_price');
            $paid = (float) $group->sum('paid_amount');
            $credit = (float) $group->sum('credit_amount');

            return [
                'period_key' => $key,
                'label' => Carbon::createFromFormat('Y-m', $key)->format('F Y'),
                'sort_key' => $key,
                'count' => $count,
                'qty' => $qty,
                'total' => $total,
                'paid' => $paid,
                'credit' => $credit,
                'avg_order' => $count > 0 ? $total / $count : 0.0,
            ];
        })
        ->sortByDesc('sort_key')
        ->values();
    }
}
