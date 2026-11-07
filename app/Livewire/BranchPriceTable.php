<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\BranchUnitPrice;
use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\ProductUnit;
use App\Support\AuthorizesPosPermissions;
use Livewire\Component;
use Livewire\WithPagination;

class BranchPriceTable extends Component
{
    use WithPagination;
    use AuthorizesPosPermissions;

    public string $branchId = '';

    public string $search = '';

    public bool $open = false;

    public ?int $editingUnitId = null;

    public string $normalPrice = '';

    public string $wholeSalePrice = '';

    public function mount(): void
    {
        $this->branchId = (string) (InventoryBranch::query()->forSales()->where('is_main', true)->value('id')
            ?: InventoryBranch::query()->forSales()->orderBy('name')->value('id'));
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['branchId', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function edit(int $unitId): void
    {
        $this->authorizePosPermission('branches', 'edit');
        $this->resetValidation();
        $this->editingUnitId = $unitId;
        $override = BranchUnitPrice::query()->where('branch_id', $this->branchId)->where('product_unit_id', $unitId)->first();
        $this->normalPrice = (string) ($override?->normal_sale_price ?? '');
        $this->wholeSalePrice = (string) ($override?->whole_sale_price ?? '');
        $this->open = true;
    }

    public function save(): void
    {
        $this->authorizePosPermission('branches', 'edit');
        $this->validate([
            'branchId' => ['required', 'integer', 'exists:inventory_branches,id'],
            'editingUnitId' => ['required', 'integer', 'exists:product_units,id'],
            'normalPrice' => ['nullable', 'numeric', 'min:0'],
            'wholeSalePrice' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branch = InventoryBranch::query()->forSales()->findOrFail($this->branchId);
        ProductUnit::query()->whereHas('product')->findOrFail($this->editingUnitId);

        if ($this->normalPrice === '' && $this->wholeSalePrice === '') {
            BranchUnitPrice::query()->where('branch_id', $branch->id)->where('product_unit_id', $this->editingUnitId)
                ->get()->each(fn ($price) => $price->delete());
        } else {
            BranchUnitPrice::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'product_unit_id' => $this->editingUnitId],
                [
                    'normal_sale_price' => $this->normalPrice === '' ? null : $this->normalPrice,
                    'whole_sale_price' => $this->wholeSalePrice === '' ? null : $this->wholeSalePrice,
                ],
            );
        }

        $this->open = false;
        $this->dispatch('toast', message: 'Branch prices updated.');
    }

    public function render()
    {
        $this->authorizePosPermission('branches', 'view');
        $branches = InventoryBranch::query()->forSales()->orderBy('name')->get();
        $branch = InventoryBranch::query()->forSales()->find($this->branchId) ?: $branches->first();
        $units = ProductUnit::query()
            ->with('product')
            ->whereHas('product')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('code', 'like', '%'.$this->search.'%')
                ->orWhere('original_code', 'like', '%'.$this->search.'%')
                ->orWhereHas('product', fn ($product) => $product->where('name', 'like', '%'.$this->search.'%'))))
            ->orderBy('name')
            ->paginate(15);
        $overrides = $branch
            ? BranchUnitPrice::query()->where('branch_id', $branch->id)->whereIn('product_unit_id', $units->pluck('id'))->get()->keyBy('product_unit_id')
            : collect();
        $editingUnit = $this->open && $this->editingUnitId
            ? ProductUnit::query()->with('product')->find($this->editingUnitId)
            : null;

        return view('livewire.branch-price-table', compact('branches', 'branch', 'units', 'overrides', 'editingUnit'));
    }
}
