<?php

namespace App\Livewire;

use App\Modules\Purchase\Models\Purchase;
use App\Support\AuthorizesPosPermissions;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseTable extends Component
{
    use AuthorizesPosPermissions;

    use WithPagination;

    public string $search = '';
    public string $from = '';
    public string $to = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $this->authorizePosPermission('purchases', 'view');
        $purchases = Purchase::query()
            ->with('buyer')
            ->when($this->search, function ($query): void {
                $query->where('supplier_name', 'like', "%{$this->search}%");
            })
            ->when($this->from, fn ($query) => $query->whereDate('purchase_date', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->whereDate('purchase_date', '<=', $this->to))
            ->latest('purchase_date')
            ->paginate(15);

        return view('livewire.purchase-table', compact('purchases'));
    }
}
