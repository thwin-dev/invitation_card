<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Sale\Models\Sale;
use App\Support\AuthorizesPosPermissions;
use Livewire\Component;
use Livewire\WithPagination;

class SaleTable extends Component
{
    use AuthorizesPosPermissions;

    use WithPagination;

    public string $search = '';
    public string $from = '';
    public string $to = '';
    public string $status = '';
    public string $branchId = '';

    public function mount(): void
    {
        $this->search = (string) request('search', '');
        $this->from = (string) request('from', '');
        $this->to = (string) request('to', '');
        $this->status = (string) request('status', '');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'from', 'to', 'status', 'branchId'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->from = '';
        $this->to = '';
        $this->status = '';
        $this->branchId = '';
        $this->resetPage();
    }

    public function render()
    {
        $this->authorizePosPermission('sales', 'view');
        $sales = Sale::query()
            ->with(['customer', 'branch'])
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('voucher_code', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%"))))
            ->when($this->from, fn ($query) => $query->whereDate('sale_date', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->whereDate('sale_date', '<=', $this->to))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
            ->latest('sale_date')
            ->paginate(15);
        $branches = InventoryBranch::query()->where('type', 'shop')->orderBy('name')->get();

        return view('livewire.sale-table', compact('sales', 'branches'));
    }
}
