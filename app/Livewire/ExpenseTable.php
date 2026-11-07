<?php

namespace App\Livewire;

use App\Modules\Expense\Models\PosExpense;
use App\Support\AuthorizesPosPermissions;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseTable extends Component
{
    use AuthorizesPosPermissions;

    use WithPagination;

    public string $search = '';
    public string $from = '';
    public string $to = '';
    public string $kind = '';

    public bool $createOpen = false;
    public string $createType = '';
    public string $createPeriod = '';
    public string $createExpenseDate = '';
    public string $createTitle = '';
    public string $createDescription = '';
    public string $createAmount = '';
    public bool $createIsProfit = false;

    public function mount(): void
    {
        $this->search = (string) request('search', '');
        $this->from = (string) request('from', '');
        $this->to = (string) request('to', '');
        $this->kind = (string) request('kind', '');
        $this->createExpenseDate = now()->toDateString();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'from', 'to', 'kind'], true)) {
            $this->resetPage();
        }
    }

    #[On('open-expense-create')]
    public function openCreate(): void
    {
        $this->resetValidation();
        $this->resetCreateForm();
        $this->createOpen = true;
    }

    public function closeCreate(): void
    {
        $this->createOpen = false;
    }

    public function createExpense(): void
    {
        $this->authorizePosPermission('expenses', 'create');
        $data = $this->validate([
            'createType' => ['required', 'string', 'max:255'],
            'createPeriod' => ['nullable', 'string', 'max:255'],
            'createExpenseDate' => ['required', 'date'],
            'createTitle' => ['required', 'string', 'max:255'],
            'createDescription' => ['nullable', 'string'],
            'createAmount' => ['required', 'numeric', 'min:0'],
            'createIsProfit' => ['boolean'],
        ]);

        PosExpense::query()->create([
            'type' => $data['createType'],
            'period' => $data['createPeriod'] ?: null,
            'expense_date' => $data['createExpenseDate'],
            'title' => $data['createTitle'],
            'description' => $data['createDescription'] ?: null,
            'amount' => $data['createAmount'],
            'is_profit' => $data['createIsProfit'] ?? false,
            'created_by' => auth('web')->id() ?? auth()->id(),
        ]);

        $this->resetCreateForm();
        $this->createOpen = false;
        $this->dispatch('toast', message: 'Expense recorded.');
    }

    private function resetCreateForm(): void
    {
        $this->createType = '';
        $this->createPeriod = '';
        $this->createExpenseDate = now()->toDateString();
        $this->createTitle = '';
        $this->createDescription = '';
        $this->createAmount = '';
        $this->createIsProfit = false;
    }

    public function render()
    {
        $this->authorizePosPermission('expenses', 'view');
        $baseQuery = PosExpense::query()
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('title', 'like', "%{$this->search}%")
                        ->orWhere('type', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->from, fn ($query) => $query->whereDate('expense_date', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->whereDate('expense_date', '<=', $this->to))
            ->when($this->kind, fn ($query) => $query->where('is_profit', $this->kind === 'profit'));

        $filteredTotal = (clone $baseQuery)->sum('amount');
        $expenses = $baseQuery->latest('expense_date')->latest('id')->paginate(15);

        return view('livewire.expense-table', compact('expenses', 'filteredTotal'));
    }
}
