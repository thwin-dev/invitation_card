<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\StockMovement;
use App\Support\AuthorizesPosPermissions;
use Livewire\Component;
use Livewire\WithPagination;

class StockMovementTable extends Component
{
    use AuthorizesPosPermissions;

    use WithPagination;

    public string $search = '';

    public string $direction = '';

    public string $referenceType = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'direction', 'referenceType'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $this->authorizePosPermission('inventory', 'view');
        $movements = StockMovement::query()
            ->with(['unit.product', 'user', 'branch', 'lot'])
            ->when($this->direction, fn ($query) => $query->where('direction', $this->direction))
            ->when($this->referenceType, fn ($query) => $query->where('reference_type', $this->referenceType))
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('note', 'like', "%{$this->search}%")
                    ->orWhere('reference_type', 'like', "%{$this->search}%")
                    ->orWhereHas('unit', fn ($unitQuery) => $unitQuery
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery
                            ->where('name', 'like', "%{$this->search}%")
                            ->orWhere('code', 'like', "%{$this->search}%")));
            }))
            ->latest()
            ->paginate(30);

        $referenceTypes = StockMovement::query()
            ->whereNotNull('reference_type')
            ->distinct()
            ->orderBy('reference_type')
            ->pluck('reference_type');

        return view('livewire.stock-movement-table', compact('movements', 'referenceTypes'));
    }
}
