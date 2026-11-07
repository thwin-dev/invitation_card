<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\ProductCategory\Models\ProductCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryValuation extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryId = '';
    public string $stockStatus = ''; // '', 'in_stock', 'low_stock', 'out_of_stock', 'excess'
    public string $sortBy = 'valuation_cost'; // 'valuation_cost', 'valuation_retail', 'potential_profit', 'current_quantity', 'name'
    public string $sortDirection = 'desc';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'categoryId', 'stockStatus', 'sortBy', 'sortDirection'], true)) {
            $this->resetPage();
        }
    }

    public function setSort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = in_array($column, ['name'], true) ? 'asc' : 'desc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryId', 'stockStatus', 'sortBy', 'sortDirection']);
        $this->sortBy = 'valuation_cost';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        $query = $this->buildQuery();
        $units = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inventory-valuation-' . now()->format('Y-m-d-His') . '.csv"',
        ];

        return response()->stream(function () use ($units) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Product Name',
                'Category',
                'Unit Name',
                'Barcode / Unit Code',
                'Current Stock',
                'Reorder Level',
                'Stock Status',
                'Purchase Cost (Unit)',
                'Total Valuation (Cost)',
                'Retail Price (Unit)',
                'Total Valuation (Retail)',
                'Wholesale Price (Unit)',
                'Total Valuation (Wholesale)',
                'Potential Margin / Profit',
                'Margin %',
            ]);

            foreach ($units as $unit) {
                $qty = (int) $unit->current_quantity;
                $cost = (float) $unit->purchase_price;
                $retail = (float) $unit->normal_sale_price;
                $wholesale = (float) $unit->whole_sale_price;

                $valCost = $qty * $cost;
                $valRetail = $qty * $retail;
                $valWholesale = $qty * $wholesale;
                $potentialProfit = $valRetail - $valCost;
                $marginPercent = $valRetail > 0 ? round(($potentialProfit / $valRetail) * 100, 2) : 0;

                $status = 'In Stock';
                if ($qty <= 0) {
                    $status = 'Out of Stock';
                } elseif ($qty <= (int) $unit->reorder_quantity) {
                    $status = 'Low Stock';
                }

                fputcsv($handle, [
                    $unit->product?->name ?? 'Unknown',
                    $unit->product?->category?->name ?? 'Uncategorized',
                    $unit->name,
                    $unit->code ?: ($unit->original_code ?: '-'),
                    $qty,
                    $unit->reorder_quantity,
                    $status,
                    number_format($cost, 2, '.', ''),
                    number_format($valCost, 2, '.', ''),
                    number_format($retail, 2, '.', ''),
                    number_format($valRetail, 2, '.', ''),
                    number_format($wholesale, 2, '.', ''),
                    number_format($valWholesale, 2, '.', ''),
                    number_format($potentialProfit, 2, '.', ''),
                    $marginPercent . '%',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    protected function buildQuery()
    {
        return ProductUnit::query()
            ->with(['product.category'])
            ->whereHas('product')
            ->when($this->categoryId, function ($q) {
                $q->whereHas('product', fn ($query) => $query->where('category_id', $this->categoryId));
            })
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('original_code', 'like', "%{$term}%")
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
                });
            })
            ->when($this->stockStatus, function ($q) {
                if ($this->stockStatus === 'in_stock') {
                    $q->where('current_quantity', '>', 0);
                } elseif ($this->stockStatus === 'out_of_stock') {
                    $q->where('current_quantity', '<=', 0);
                } elseif ($this->stockStatus === 'low_stock') {
                    $q->where('current_quantity', '>', 0)
                      ->whereColumn('current_quantity', '<=', 'reorder_quantity');
                }
            });
    }

    public function render()
    {
        $categories = ProductCategory::query()->orderBy('name')->get();

        // Calculate Global KPI Summary Metrics across ALL items matching base filter
        $baseQuery = $this->buildQuery();

        // Overall stats
        $allUnits = (clone $baseQuery)->get();

        $totalUnitsTracked = $allUnits->count();
        $totalQuantityInStock = (int) $allUnits->where('current_quantity', '>', 0)->sum('current_quantity');
        $outOfStockCount = $allUnits->where('current_quantity', '<=', 0)->count();
        $lowStockCount = $allUnits->filter(fn ($u) => $u->current_quantity > 0 && $u->current_quantity <= $u->reorder_quantity)->count();

        $totalValuationCost = (float) $allUnits->sum(fn ($u) => (int) $u->current_quantity * (float) $u->purchase_price);
        $totalValuationRetail = (float) $allUnits->sum(fn ($u) => (int) $u->current_quantity * (float) $u->normal_sale_price);
        $totalValuationWholesale = (float) $allUnits->sum(fn ($u) => (int) $u->current_quantity * (float) $u->whole_sale_price);

        $totalPotentialProfit = $totalValuationRetail - $totalValuationCost;
        $overallMarginPercent = $totalValuationRetail > 0 
            ? round(($totalPotentialProfit / $totalValuationRetail) * 100, 1) 
            : 0;

        // Category-wise Breakdown
        $categoryBreakdown = $allUnits->groupBy(fn ($u) => $u->product?->category?->name ?? 'Uncategorized')
            ->map(function ($items, $catName) {
                $qty = (int) $items->sum('current_quantity');
                $costVal = (float) $items->sum(fn ($u) => (int) $u->current_quantity * (float) $u->purchase_price);
                $retailVal = (float) $items->sum(fn ($u) => (int) $u->current_quantity * (float) $u->normal_sale_price);
                $profit = $retailVal - $costVal;
                return [
                    'category' => $catName,
                    'sku_count' => $items->count(),
                    'total_quantity' => $qty,
                    'valuation_cost' => $costVal,
                    'valuation_retail' => $retailVal,
                    'potential_profit' => $profit,
                    'margin_percent' => $retailVal > 0 ? round(($profit / $retailVal) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('valuation_cost')
            ->values();

        // Paginated sorted table
        $paginatedQuery = clone $baseQuery;

        if ($this->sortBy === 'valuation_cost') {
            $paginatedQuery->orderByRaw('(current_quantity * purchase_price) ' . ($this->sortDirection === 'asc' ? 'ASC' : 'DESC'));
        } elseif ($this->sortBy === 'valuation_retail') {
            $paginatedQuery->orderByRaw('(current_quantity * normal_sale_price) ' . ($this->sortDirection === 'asc' ? 'ASC' : 'DESC'));
        } elseif ($this->sortBy === 'potential_profit') {
            $paginatedQuery->orderByRaw('((current_quantity * normal_sale_price) - (current_quantity * purchase_price)) ' . ($this->sortDirection === 'asc' ? 'ASC' : 'DESC'));
        } elseif ($this->sortBy === 'current_quantity') {
            $paginatedQuery->orderBy('current_quantity', $this->sortDirection);
        } else {
            $paginatedQuery->orderBy('name', $this->sortDirection);
        }

        $units = $paginatedQuery->paginate(25);

        return view('livewire.inventory-valuation', compact(
            'categories',
            'units',
            'totalUnitsTracked',
            'totalQuantityInStock',
            'outOfStockCount',
            'lowStockCount',
            'totalValuationCost',
            'totalValuationRetail',
            'totalValuationWholesale',
            'totalPotentialProfit',
            'overallMarginPercent',
            'categoryBreakdown'
        ));
    }
}
