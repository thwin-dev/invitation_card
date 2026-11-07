<?php

namespace App\Livewire;

use App\Modules\Customer\Models\CustomerPayment;
use App\Modules\Expense\Models\PosExpense;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Sale\Models\Sale;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Carbon;
use Livewire\Component;

class FinancialReport extends Component
{
    use AuthorizesPosPermissions;

    public string $from = '';
    public string $to = '';

    public function mount(?string $from = null, ?string $to = null): void
    {
        $this->from = $from ?: (string) request('from', $this->from ?: now()->startOfMonth()->toDateString());
        $this->to = $to ?: (string) request('to', $this->to ?: now()->toDateString());
    }

    public function setThisMonth(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
        $this->dispatch('date-range-updated', from: $this->from, to: $this->to);
    }

    public function setLastMonth(): void
    {
        $this->from = now()->subMonth()->startOfMonth()->toDateString();
        $this->to = now()->subMonth()->endOfMonth()->toDateString();
        $this->dispatch('date-range-updated', from: $this->from, to: $this->to);
    }

    public function setToday(): void
    {
        $this->from = now()->toDateString();
        $this->to = now()->toDateString();
        $this->dispatch('date-range-updated', from: $this->from, to: $this->to);
    }

    public function render()
    {
        $this->authorizePosPermission('reports', 'view');
        $fromDate = Carbon::parse($this->from ?: now()->startOfMonth());
        $toDate = Carbon::parse($this->to ?: now());

        $sales = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->with('items')
            ->latest('sale_date')
            ->get();

        $purchases = (float) Purchase::query()
            ->whereBetween('purchase_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->sum('total_price');

        $expenses = (float) PosExpense::query()
            ->whereBetween('expense_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('is_profit', false)
            ->sum('amount');

        $salesTotal = (float) $sales->sum('total_price');
        $paidTotal = (float) $sales->sum('paid_amount');
        $creditTotal = (float) $sales->sum('credit_amount');
        $paymentIncome = (float) CustomerPayment::query()
            ->whereBetween('payment_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->sum('amount');
        $quantity = (int) $sales->sum('total_quantity');
        $net = $salesTotal - $purchases - $expenses;

        return view('livewire.financial-report', compact(
            'fromDate',
            'toDate',
            'salesTotal',
            'paidTotal',
            'creditTotal',
            'paymentIncome',
            'purchases',
            'expenses',
            'net',
            'quantity',
            'sales'
        ));
    }
}
