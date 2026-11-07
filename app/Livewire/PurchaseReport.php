<?php

namespace App\Livewire;

use App\Models\User;
use App\Modules\Purchase\Models\PosSupplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\SupplierPayment;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseReport extends Component
{
    public string $periodMode = 'daily'; // 'daily', 'weekly', 'monthly'
    public string $from = '';
    public string $to = '';
    public string $supplierId = '';
    public string $purchaserId = '';
    public string $paymentStatus = ''; // '', 'paid', 'partial', 'unpaid'
    public string $activeTab = 'breakdown'; // 'breakdown', 'suppliers', 'products', 'invoices'

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
        if (in_array($tab, ['breakdown', 'suppliers', 'products', 'invoices'], true)) {
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
        $activeTab = $this->activeTab;
        $periodMode = $this->periodMode;
        $from = $data['fromDate']->toDateString();
        $to = $data['toDate']->toDateString();

        return response()->streamDownload(function () use ($data, $activeTab, $periodMode): void {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Summary Header
            fputcsv($handle, ['Purchase & Supplier Report', ucfirst($activeTab) . ' View (' . ucfirst($periodMode) . ')']);
            fputcsv($handle, ['Period', $data['fromDate']->format('Y-m-d') . ' to ' . $data['toDate']->format('Y-m-d')]);
            fputcsv($handle, ['Total Purchases (MMK)', number_format($data['totalPurchases'], 2)]);
            fputcsv($handle, ['Paid Amount (MMK)', number_format($data['totalPaid'], 2)]);
            fputcsv($handle, ['Outstanding Payable (MMK)', number_format($data['totalPayable'], 2)]);
            fputcsv($handle, ['Payments Disbursed in Period (MMK)', number_format($data['paymentsDisbursed'], 2)]);
            fputcsv($handle, ['Total Purchase Bills', $data['billCount']]);
            fputcsv($handle, ['Total Units Purchased', $data['totalUnits']]);
            fputcsv($handle, []);

            if ($activeTab === 'breakdown') {
                fputcsv($handle, [
                    'Period / Date',
                    'Purchase Bills',
                    'Units Purchased',
                    'Total Purchases (MMK)',
                    'Paid (MMK)',
                    'Payable (MMK)',
                    'Avg Bill Value (MMK)',
                ]);
                foreach ($data['breakdown'] as $row) {
                    fputcsv($handle, [
                        $row['label'],
                        $row['count'],
                        $row['qty'],
                        number_format($row['total'], 2, '.', ''),
                        number_format($row['paid'], 2, '.', ''),
                        number_format($row['payable'], 2, '.', ''),
                        number_format($row['avg_bill'], 2, '.', ''),
                    ]);
                }
            } elseif ($activeTab === 'suppliers') {
                fputcsv($handle, [
                    'Supplier Name',
                    'Company Name',
                    'Phone',
                    'Purchase Bills',
                    'Units Purchased',
                    'Total Purchased (MMK)',
                    'Paid (MMK)',
                    'Payable (MMK)',
                    'Overall Balance (MMK)',
                    'Payment Status',
                ]);
                foreach ($data['supplierBreakdown'] as $row) {
                    fputcsv($handle, [
                        $row['name'],
                        $row['company'] ?: '-',
                        $row['phone'] ?: '-',
                        $row['bills_count'],
                        $row['units_count'],
                        number_format($row['total_purchased'], 2, '.', ''),
                        number_format($row['total_paid'], 2, '.', ''),
                        number_format($row['total_payable'], 2, '.', ''),
                        number_format($row['overall_payable'], 2, '.', ''),
                        ucfirst($row['status']),
                    ]);
                }
            } elseif ($activeTab === 'products') {
                fputcsv($handle, [
                    'Rank',
                    'Product Name',
                    'Code / SKU',
                    'Unit',
                    'Quantity Purchased',
                    'Total Cost / Spend (MMK)',
                    'Avg Unit Cost (MMK)',
                ]);
                foreach ($data['topProducts'] as $index => $row) {
                    fputcsv($handle, [
                        $index + 1,
                        $row['product_name'],
                        $row['code'],
                        $row['unit_name'],
                        $row['quantity'],
                        number_format($row['total_cost'], 2, '.', ''),
                        number_format($row['avg_cost'], 2, '.', ''),
                    ]);
                }
            } else {
                fputcsv($handle, [
                    'Bill ID',
                    'Purchase Date',
                    'Supplier',
                    'Purchaser / Staff',
                    'Total Quantity',
                    'Total Price (MMK)',
                    'Paid (MMK)',
                    'Payable (MMK)',
                    'Payment Status',
                    'Payment Due Date',
                ]);
                foreach ($data['purchases'] as $p) {
                    fputcsv($handle, [
                        '#PO-' . str_pad((string) $p->id, 5, '0', STR_PAD_LEFT),
                        $p->purchase_date ? Carbon::parse($p->purchase_date)->format('Y-m-d') : '-',
                        $p->supplier?->name ?? $p->supplier_name,
                        $p->buyer?->name ?? '-',
                        $p->total_quantity,
                        number_format((float) $p->total_price, 2, '.', ''),
                        number_format((float) $p->paid_amount, 2, '.', ''),
                        number_format((float) $p->payable_amount, 2, '.', ''),
                        ucfirst($p->payment_status ?: 'paid'),
                        $p->payment_due_date ? Carbon::parse($p->payment_due_date)->format('Y-m-d') : '-',
                    ]);
                }
            }

            fclose($handle);
        }, "purchase-report-{$activeTab}-{$periodMode}-{$from}-to-{$to}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $suppliers = PosSupplier::query()->orderBy('name')->get(['id', 'name', 'company_name']);
        $purchasers = User::query()->orderBy('name')->get(['id', 'name']);
        $reportData = $this->calculateReportData();

        return view('livewire.purchase-report', array_merge($reportData, [
            'suppliers' => $suppliers,
            'purchasers' => $purchasers,
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

        $query = Purchase::query()
            ->whereBetween('purchase_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->with(['supplier', 'buyer', 'items.unit.product']);

        if ($this->supplierId !== '') {
            $query->where('supplier_id', $this->supplierId);
        }

        if ($this->purchaserId !== '') {
            $query->where('purchase_by', $this->purchaserId);
        }

        if ($this->paymentStatus !== '') {
            $query->where('payment_status', $this->paymentStatus);
        }

        $purchases = $query->latest('purchase_date')->latest('id')->get();

        $totalPurchases = (float) $purchases->sum('total_price');
        $totalPaid = (float) $purchases->sum('paid_amount');
        $totalPayable = (float) $purchases->sum('payable_amount');
        $totalUnits = (int) $purchases->sum('total_quantity');
        $billCount = $purchases->count();
        $averageBillValue = $billCount > 0 ? $totalPurchases / $billCount : 0.0;

        // Disbursed payments to suppliers in period
        $paymentsQuery = SupplierPayment::query()
            ->whereBetween('payment_date', [$fromDate->toDateString(), $toDate->toDateString()]);

        if ($this->supplierId !== '') {
            $paymentsQuery->where('supplier_id', $this->supplierId);
        }

        $paymentsDisbursed = (float) $paymentsQuery->sum('amount');

        // Period Breakdown
        $breakdown = match ($this->periodMode) {
            'monthly' => $this->groupMonthly($purchases),
            'weekly' => $this->groupWeekly($purchases),
            default => $this->groupDaily($purchases),
        };

        // Supplier Breakdown
        $supplierBreakdown = $this->groupSuppliers($purchases);

        // Top Purchased Products
        $topProducts = $this->topPurchasedProducts($purchases);

        return [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'purchases' => $purchases,
            'totalPurchases' => $totalPurchases,
            'totalPaid' => $totalPaid,
            'totalPayable' => $totalPayable,
            'totalUnits' => $totalUnits,
            'billCount' => $billCount,
            'averageBillValue' => $averageBillValue,
            'paymentsDisbursed' => $paymentsDisbursed,
            'breakdown' => $breakdown,
            'supplierBreakdown' => $supplierBreakdown,
            'topProducts' => $topProducts,
        ];
    }

    private function groupDaily($purchases)
    {
        return $purchases->groupBy(fn ($purchase) => Carbon::parse($purchase->purchase_date)->toDateString())
            ->map(function ($group, $key) {
                $count = $group->count();
                $qty = (int) $group->sum('total_quantity');
                $total = (float) $group->sum('total_price');
                $paid = (float) $group->sum('paid_amount');
                $payable = (float) $group->sum('payable_amount');
                return [
                    'period_key' => $key,
                    'label' => Carbon::parse($key)->format('d M Y (D)'),
                    'sort_key' => $key,
                    'count' => $count,
                    'qty' => $qty,
                    'total' => $total,
                    'paid' => $paid,
                    'payable' => $payable,
                    'avg_bill' => $count > 0 ? $total / $count : 0.0,
                ];
            })
            ->sortByDesc('sort_key')
            ->values();
    }

    private function groupWeekly($purchases)
    {
        return $purchases->groupBy(function ($purchase) {
            $c = Carbon::parse($purchase->purchase_date);
            return $c->format('o-\WW');
        })->map(function ($group, $key) {
            $count = $group->count();
            $qty = (int) $group->sum('total_quantity');
            $total = (float) $group->sum('total_price');
            $paid = (float) $group->sum('paid_amount');
            $payable = (float) $group->sum('payable_amount');

            $firstDate = Carbon::parse($group->min('purchase_date'))->startOfWeek();
            $lastDate = Carbon::parse($group->max('purchase_date'))->endOfWeek();

            return [
                'period_key' => $key,
                'label' => 'Week ' . substr($key, 6) . ' (' . $firstDate->format('M d') . ' - ' . $lastDate->format('M d, Y') . ')',
                'sort_key' => $key,
                'count' => $count,
                'qty' => $qty,
                'total' => $total,
                'paid' => $paid,
                'payable' => $payable,
                'avg_bill' => $count > 0 ? $total / $count : 0.0,
            ];
        })
        ->sortByDesc('sort_key')
        ->values();
    }

    private function groupMonthly($purchases)
    {
        return $purchases->groupBy(function ($purchase) {
            return Carbon::parse($purchase->purchase_date)->format('Y-m');
        })->map(function ($group, $key) {
            $count = $group->count();
            $qty = (int) $group->sum('total_quantity');
            $total = (float) $group->sum('total_price');
            $paid = (float) $group->sum('paid_amount');
            $payable = (float) $group->sum('payable_amount');

            return [
                'period_key' => $key,
                'label' => Carbon::createFromFormat('Y-m', $key)->format('F Y'),
                'sort_key' => $key,
                'count' => $count,
                'qty' => $qty,
                'total' => $total,
                'paid' => $paid,
                'payable' => $payable,
                'avg_bill' => $count > 0 ? $total / $count : 0.0,
            ];
        })
        ->sortByDesc('sort_key')
        ->values();
    }

    private function groupSuppliers($purchases)
    {
        // Group by supplier id or supplier name
        return $purchases->groupBy(function ($purchase) {
            return $purchase->supplier_id ?: ('name:' . $purchase->supplier_name);
        })->map(function ($group) {
            $first = $group->first();
            $supplier = $first->supplier;

            $name = $supplier?->name ?? $first->supplier_name;
            $company = $supplier?->company_name ?? '';
            $phone = $supplier?->phone ?? '';
            $supplierId = $supplier?->id ?? null;

            $billsCount = $group->count();
            $unitsCount = (int) $group->sum('total_quantity');
            $totalPurchased = (float) $group->sum('total_price');
            $totalPaid = (float) $group->sum('paid_amount');
            $totalPayable = (float) $group->sum('payable_amount');
            $overallPayable = $supplier ? (float) $supplier->outstandingPayable() : $totalPayable;

            $status = $totalPayable <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

            return [
                'supplier_id' => $supplierId,
                'name' => $name,
                'company' => $company,
                'phone' => $phone,
                'bills_count' => $billsCount,
                'units_count' => $unitsCount,
                'total_purchased' => $totalPurchased,
                'total_paid' => $totalPaid,
                'total_payable' => $totalPayable,
                'overall_payable' => $overallPayable,
                'status' => $status,
            ];
        })
        ->sortByDesc('total_purchased')
        ->values();
    }

    private function topPurchasedProducts($purchases)
    {
        return $purchases->flatMap(fn ($purchase) => $purchase->items)
            ->groupBy('product_unit_id')
            ->map(function ($items) {
                $first = $items->first();
                $unitName = $first?->unit?->name ?? 'Unit';
                $productName = $first?->unit?->product?->name ?? 'Unknown Product';
                $productCode = $first?->unit?->product?->code ?? ($first?->unit?->code ?? '-');
                $qty = (int) $items->sum('quantity');
                $totalCost = (float) $items->sum(fn ($i) => (float) $i->quantity * (float) $i->price);
                $avgCost = $qty > 0 ? $totalCost / $qty : 0.0;

                return [
                    'product_name' => $productName,
                    'unit_name' => $unitName,
                    'code' => $productCode,
                    'quantity' => $qty,
                    'total_cost' => $totalCost,
                    'avg_cost' => $avgCost,
                ];
            })
            ->sortByDesc('total_cost')
            ->take(15)
            ->values();
    }
}
