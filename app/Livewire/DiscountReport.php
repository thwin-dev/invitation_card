<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Sale\Models\Sale;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class DiscountReport extends Component
{
    use AuthorizesPosPermissions, WithPagination;

    public string $from = '';

    public string $to = '';

    public string $branchId = '';

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function updated(): void
    {
        $this->resetPage();
        $this->validate();
    }

    protected function rules(): array
    {
        return ['from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'branchId' => ['nullable', 'integer', 'exists:inventory_branches,id']];
    }

    public function render()
    {
        $this->authorizePosPermission('reports', 'view');
        $valid = Validator::make(
            ['from' => $this->from, 'to' => $this->to, 'branchId' => $this->branchId], $this->rules()
        )->passes();
        $query = Sale::query()->where('status', 'completed')->where('discount_amount', '>', 0)
            ->when(! $valid, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($valid, fn ($q) => $q->whereDate('sale_date', '>=', $this->from)->whereDate('sale_date', '<=', $this->to))
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId));
        // Never combine amounts from different receipt currencies.
        $summaries = (clone $query)->selectRaw('branch_id, currency_code_snapshot, COUNT(*) as sale_count, SUM(subtotal) as gross, SUM(discount_amount) as discounts, SUM(total_price) as net')
            ->groupBy('branch_id', 'currency_code_snapshot')->with('branch')->get();
        $sales = $query->with(['branch', 'customer', 'items'])->latest('sale_date')->latest('id')->paginate(15);
        $branches = InventoryBranch::query()->orderBy('name')->get();

        return view('livewire.discount-report', compact('summaries', 'sales', 'branches'));
    }
}
