<?php

namespace App\Livewire;

use App\Modules\Order\Models\PosOrder;
use App\Support\AuthorizesPosPermissions;
use Livewire\Component;
use Livewire\WithPagination;

class OrderTable extends Component
{
    use AuthorizesPosPermissions;

    use WithPagination;

    public string $search = '';
    public string $from = '';
    public string $to = '';
    public string $status = '';

    public function mount(): void
    {
        $this->search = (string) request('search', '');
        $this->from = (string) request('from', '');
        $this->to = (string) request('to', '');
        $this->status = (string) request('status', '');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'from', 'to', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->from = '';
        $this->to = '';
        $this->status = '';
        $this->resetPage();
    }

    public function render()
    {
        $this->authorizePosPermission('orders', 'view');
        $orders = PosOrder::query()
            ->with('customer')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('order_number', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%"))))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->from, fn ($query) => $query->whereDate('order_date', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->whereDate('order_date', '<=', $this->to))
            ->latest('order_date')
            ->paginate(15);

        return view('livewire.order-table', compact('orders'));
    }
}
