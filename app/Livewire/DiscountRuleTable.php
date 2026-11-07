<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Sale\Models\DiscountRule;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class DiscountRuleTable extends Component
{
    use AuthorizesPosPermissions, WithPagination;

    public string $search = '';

    public string $branchFilter = '';

    public string $statusFilter = '';

    public bool $open = false;

    #[Locked]
    public ?int $editingId = null;

    public array $branchIds = [];

    // Kept as a compatibility alias for existing Livewire callers.
    public string $branchId = '';

    public string $unitId = '';

    public string $name = '';

    public string $type = 'percentage';

    public string $value = '';

    public bool $isActive = true;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'branchFilter', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorizePosPermission('discounts', 'create');
        $this->resetValidation();
        $this->reset('editingId', 'unitId', 'name', 'type', 'value', 'isActive');
        $this->branchIds = $this->branchFilter ? [(string) $this->branchFilter] : [];
        $this->branchId = $this->branchFilter;
        $this->open = true;
    }

    public function edit(int $id): void
    {
        $this->authorizePosPermission('discounts', 'edit');
        $rule = DiscountRule::query()->findOrFail($id);
        $this->resetValidation();
        $this->editingId = $rule->id;
        $this->branchIds = $rule->branches()->pluck('inventory_branches.id')->map(fn ($id) => (string) $id)->all();
        if ($this->branchIds === []) {
            $this->branchIds = [(string) $rule->branch_id];
        }
        $this->branchId = (string) $rule->branch_id;
        $this->unitId = (string) ($rule->product_unit_id ?? '');
        $this->name = $rule->name;
        $this->type = $rule->type;
        $this->value = $rule->value;
        $this->isActive = $rule->is_active;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorizePosPermission('discounts', $this->editingId ? 'edit' : 'create');
        if ($this->branchIds === [] && $this->branchId !== '') {
            $this->branchIds = [$this->branchId];
        }
        $this->validate([
            'branchIds' => ['required', 'array', 'min:1'],
            'branchIds.*' => ['required', 'integer', 'distinct', Rule::exists('inventory_branches', 'id')->where('type', 'shop')->where('is_active', true)],
            'unitId' => ['nullable', 'integer', 'exists:product_units,id'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:'.($this->type === 'percentage' ? '100' : '9999999999.99')],
        ], [], ['branchIds' => 'branches', 'branchIds.*' => 'branch', 'unitId' => 'product / unit', 'value' => 'discount value']);
        if ($this->unitId !== '') {
            ProductUnit::query()->whereHas('product')->findOrFail($this->unitId);
        }
        $rule = $this->editingId ? DiscountRule::query()->findOrFail($this->editingId) : new DiscountRule;
        $branchIds = array_map('intval', $this->branchIds);
        $rule->fill(['branch_id' => $branchIds[0], 'product_unit_id' => $this->unitId ?: null,
            'name' => trim($this->name), 'type' => $this->type, 'value' => $this->value, 'is_active' => $this->isActive])->save();
        $rule->branches()->sync($branchIds);
        $this->open = false;
        $this->dispatch('toast', message: 'Branch discount rule saved.');
    }

    public function toggle(int $id): void
    {
        $this->authorizePosPermission('discounts', 'edit');
        $rule = DiscountRule::query()->findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);
        $this->dispatch('toast', message: 'Discount status updated.');
    }

    public function render()
    {
        $this->authorizePosPermission('discounts', 'view');
        $rules = DiscountRule::query()->with(['branch', 'branches', 'unit.product'])
            ->when($this->branchFilter, fn ($q) => $q->forBranch((int) $this->branchFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->latest('id')->paginate(15);
        $branches = InventoryBranch::query()->forSales()->orderBy('name')->get();
        $units = $this->open ? ProductUnit::query()->with('product')->whereHas('product')->orderBy('name')->get() : collect();

        return view('livewire.discount-rule-table', compact('rules', 'branches', 'units'));
    }
}
