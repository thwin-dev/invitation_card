<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Services\InventoryService;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryWorkspace extends Component
{
    use WithPagination;
    use AuthorizesPosPermissions;

    public string $search = '';

    public string $branchId = '';

    public string $status = '';

    public bool $open = false;

    public array $form = [];

    #[Locked]
    public string $token = '';

    #[Locked]
    public int $expected = 0;

    public function mount(): void
    {
        $this->branchId = (string) InventoryBranch::where('is_main', true)->value('id');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'branchId', 'status'])) {
            $this->resetPage();
            $this->resetPage('lotsPage');
        }
        if (in_array($property, ['form.unit_id', 'form.branch_id'])) {
            $this->refreshExpected();
        }
    }

    private function refreshExpected(): void
    {
        $unit = ProductUnit::find($this->form['unit_id'] ?? null);
        $branch = InventoryBranch::find($this->form['branch_id'] ?? null);
        $this->expected = $unit && $branch ? app(InventoryService::class)->balance($unit, $branch) : 0;
        $this->form['lot_id'] = '';
    }

    public function start(?int $unitId = null): void
    {
        $this->authorizePosPermission('inventory', 'edit');
        $this->resetValidation();
        $this->token = (string) Str::uuid();
        $this->form = ['kind' => 'in', 'unit_id' => $unitId ?: '', 'branch_id' => $this->branchId,
            'destination_id' => '', 'quantity' => 1, 'lot_id' => '', 'batch_number' => '', 'serial_number' => '', 'expires_at' => '', 'note' => ''];
        $this->refreshExpected();
        $this->open = true;
    }

    public function save(): void
    {
        $this->authorizePosPermission('inventory', 'edit');
        $data = array_map(fn ($value) => $value === '' ? null : $value, $this->form);
        app(InventoryService::class)->execute([...$data, 'token' => $this->token, 'expected' => $this->expected], auth('web')->id());
        $this->open = false;
        $this->dispatch('toast', message: 'Inventory updated.');
    }

    public function render()
    {
        $this->authorizePosPermission('inventory', 'view');
        $branch = InventoryBranch::findOrFail($this->branchId);
        $units = ProductUnit::with('product')->whereHas('product')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")->orWhere('original_code', 'like', "%{$this->search}%")->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))))
            ->select('product_units.*');
        $balanceSql = $branch->is_main ? 'current_quantity' : '(select coalesce(sum(quantity), 0) from inventory_lots where inventory_lots.product_unit_id = product_units.id and inventory_lots.branch_id = '.(int) $branch->id.')';
        $units->selectRaw($balanceSql.' as branch_quantity');
        if ($this->status === 'low') {
            $units->whereRaw($balanceSql.' <= reorder_quantity')->whereRaw($balanceSql.' > 0');
        }
        if ($this->status === 'out') {
            $units->whereRaw($balanceSql.' = 0');
        }
        $lots = InventoryLot::with(['unit.product', 'branch'])->where('branch_id', $branch->id)->where('quantity', '>', 0)
            ->whereHas('unit.product')->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('batch_number', 'like', "%{$this->search}%")->orWhere('serial_number', 'like', "%{$this->search}%")->orWhereHas('unit.product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))))
            ->when($this->status === 'expired', fn ($q) => $q->whereDate('expires_at', '<', today()))
            ->when($this->status === 'expiring', fn ($q) => $q->whereBetween('expires_at', [today(), today()->addDays(30)]))
            ->orderByRaw('expires_at IS NULL')->orderBy('expires_at')->paginate(15, ['*'], 'lotsPage');

        return view('livewire.inventory-workspace', [
            'units' => $units->orderBy('name')->paginate(15), 'lots' => $lots,
            'branches' => InventoryBranch::orderBy('name')->get(),
            'unitOptions' => ProductUnit::with('product')->whereHas('product')->orderBy('name')->get(),
            'lotOptions' => $this->open ? InventoryLot::where('product_unit_id', $this->form['unit_id'])->where('branch_id', $this->form['branch_id'])->where('quantity', '>', 0)->get() : collect(),
        ]);
    }
}
