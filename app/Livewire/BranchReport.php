<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Sale\Models\Sale;
use App\Support\AuthorizesPosPermissions;
use Livewire\Component;

class BranchReport extends Component
{
    use AuthorizesPosPermissions;

    public string $branchId = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->from = (string) request('from', now()->startOfMonth()->toDateString());
        $this->to = (string) request('to', now()->toDateString());
        $this->branchId = (string) request('branch_id', '');
    }

    public function updated(string $property): void
    {
        if ($property === 'branchId' && $this->branchId !== '') {
            InventoryBranch::query()->where('type', 'shop')->findOrFail($this->branchId);
        }
    }

    public function resetFilters(): void
    {
        $this->branchId = '';
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function render()
    {
        $this->authorizePosPermission('reports', 'view');
        $branches = InventoryBranch::query()->where('type', 'shop')->orderBy('name')->get();
        $aggregates = Sale::query()
            ->where('status', 'completed')
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
            ->when($this->from, fn ($query) => $query->whereDate('sale_date', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->whereDate('sale_date', '<=', $this->to))
            ->selectRaw('branch_id, COUNT(*) as sale_count, SUM(total_quantity) as quantity, SUM(total_price) as total_sales, SUM(paid_amount) as paid_total, SUM(credit_amount) as credit_total')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $rows = $branches
            ->when($this->branchId, fn ($items) => $items->where('id', (int) $this->branchId))
            ->map(function (InventoryBranch $branch) use ($aggregates): object {
                $totals = $aggregates->get($branch->id);
                return (object) [
                    'branch' => $branch,
                    'sale_count' => (int) ($totals->sale_count ?? 0),
                    'quantity' => (int) ($totals->quantity ?? 0),
                    'total_sales' => (float) ($totals->total_sales ?? 0),
                    'paid_total' => (float) ($totals->paid_total ?? 0),
                    'credit_total' => (float) ($totals->credit_total ?? 0),
                ];
            });

        return view('livewire.branch-report', [
            'branches' => $branches,
            'rows' => $rows,
            'saleCount' => $rows->sum('sale_count'),
            'quantity' => $rows->sum('quantity'),
            'totalSales' => $rows->sum('total_sales'),
            'paidTotal' => $rows->sum('paid_total'),
            'creditTotal' => $rows->sum('credit_total'),
        ]);
    }
}
