<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\ProductUnit;
use App\Support\AuthorizesPosPermissions;
use Livewire\Component;
use Livewire\WithPagination;

class StockTable extends Component
{
    use AuthorizesPosPermissions;

    use WithPagination;

    public string $search = '';
    public string $mode = 'overview';

    public function mount(string $mode = 'overview'): void
    {
        $this->mode = $mode;
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['overview', 'price', 'reorder'], true) ? $mode : 'overview';
        $this->resetPage();
    }

    public function render()
    {
        $this->authorizePosPermission('inventory', 'view');
        $units = ProductUnit::query()->with('product')->whereHas('product')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")->orWhere('original_code', 'like', "%{$this->search}%")->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))))
            ->when($this->mode === 'reorder', fn ($q) => $q->whereColumn('current_quantity', '<=', 'reorder_quantity'))
            ->orderBy('name')->paginate(20);

        $unitsTracked = ProductUnit::query()->whereHas('product')->count();
        $reorderCount = ProductUnit::query()->whereHas('product')->whereColumn('current_quantity', '<=', 'reorder_quantity')->count();
        $stockValue = ProductUnit::query()->whereHas('product')->selectRaw('SUM(current_quantity * purchase_price) as value')->value('value') ?? 0;

        return view('livewire.stock-table', compact('units', 'unitsTracked', 'reorderCount', 'stockValue'));
    }
}
