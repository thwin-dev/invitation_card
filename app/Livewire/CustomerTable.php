<?php

namespace App\Livewire;

use App\Modules\Customer\Models\PosCustomer;
use App\Support\AuthorizesPosPermissions;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerTable extends Component
{
    use WithPagination;
    use AuthorizesPosPermissions;

    public string $search = '';
    public bool $createOpen = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $createName = '';
    public string $createPhone = '';
    public string $createAddress = '';
    public int $createCustomerLevel = 1;
    public bool $createAllowCredit = false;
    public string $createCreditLimit = '50000';

    public string $editName = '';
    public string $editPhone = '';
    public string $editAddress = '';
    public int $editCustomerLevel = 1;
    public bool $editAllowCredit = false;
    public string $editCreditLimit = '0';

    public function mount(): void
    {
        $this->search = (string) request('search', '');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCreateCustomerLevel(int $level): void
    {
        $defaults = [1 => '50000', 2 => '200000', 3 => '500000'];
        if (isset($defaults[$level])) {
            $this->createCreditLimit = $defaults[$level];
        }
    }

    #[On('open-customer-create')]
    public function openCreate(): void
    {
        $this->authorizePosPermission('customers', 'create');
        $this->resetValidation();
        $this->resetCreateForm();
        $this->createOpen = true;
    }

    public function closeCreate(): void
    {
        $this->createOpen = false;
    }

    public function createCustomer(): void
    {
        $this->authorizePosPermission('customers', 'create');
        $data = $this->validate($this->createRules());

        PosCustomer::query()->create([
            'name' => $data['createName'],
            'phone' => $data['createPhone'] ?: null,
            'address' => $data['createAddress'] ?: null,
            'customer_level' => $data['createCustomerLevel'],
            'allow_credit' => $data['createAllowCredit'],
            'credit_limit' => $data['createAllowCredit'] ? (float) ($data['createCreditLimit'] ?? 0) : 0,
            'created_by' => auth('web')->id(),
        ]);

        $this->resetCreateForm();
        $this->resetPage();
        $this->createOpen = false;
        $this->dispatch('toast', message: 'Customer created.');
    }

    public function editCustomer(int $customerId): void
    {
        $this->authorizePosPermission('customers', 'edit');
        $customer = PosCustomer::query()->findOrFail($customerId);
        $this->resetValidation();
        $this->editingId = $customer->id;
        $this->editName = $customer->name;
        $this->editPhone = (string) $customer->phone;
        $this->editAddress = (string) $customer->address;
        $this->editCustomerLevel = (int) $customer->customer_level;
        $this->editAllowCredit = (bool) $customer->allow_credit;
        $this->editCreditLimit = (string) ((float) $customer->credit_limit);
    }

    public function closeEdit(): void
    {
        $this->editingId = null;
    }

    public function updateCustomer(): void
    {
        $this->authorizePosPermission('customers', 'edit');
        $data = $this->validate($this->editRules());

        PosCustomer::query()->findOrFail($this->editingId)->update([
            'name' => $data['editName'],
            'phone' => $data['editPhone'] ?: null,
            'address' => $data['editAddress'] ?: null,
            'customer_level' => $data['editCustomerLevel'],
            'allow_credit' => $data['editAllowCredit'],
            'credit_limit' => $data['editAllowCredit'] ? (float) ($data['editCreditLimit'] ?? 0) : 0,
        ]);

        $this->editingId = null;
        $this->dispatch('toast', message: 'Customer updated.');
    }

    public function confirmDelete(int $customerId): void
    {
        $this->deletingId = $customerId;
    }

    public function closeDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteCustomer(): void
    {
        $this->authorizePosPermission('customers', 'delete');
        PosCustomer::query()->findOrFail($this->deletingId)->delete();
        $this->deletingId = null;
        $this->dispatch('toast', message: 'Customer deleted.');
    }

    public function render()
    {
        $this->authorizePosPermission('customers', 'view');
        $customers = PosCustomer::query()
            ->withSum(['credits as outstanding_debt' => fn ($query) => $query->whereNull('repayment_date')], 'amount')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('address', 'like', "%{$this->search}%")))
            ->latest('id')
            ->paginate(15);

        return view('livewire.customer-table', compact('customers'));
    }

    private function createRules(): array
    {
        return [
            'createName' => ['required', 'string', 'max:255'],
            'createPhone' => ['nullable', 'string', 'max:255'],
            'createAddress' => ['nullable', 'string'],
            'createCustomerLevel' => ['required', 'integer', 'in:1,2,3'],
            'createAllowCredit' => ['boolean'],
            'createCreditLimit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function editRules(): array
    {
        return [
            'editName' => ['required', 'string', 'max:255'],
            'editPhone' => ['nullable', 'string', 'max:255'],
            'editAddress' => ['nullable', 'string'],
            'editCustomerLevel' => ['required', 'integer', 'in:1,2,3'],
            'editAllowCredit' => ['boolean'],
            'editCreditLimit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function resetCreateForm(): void
    {
        $this->createName = '';
        $this->createPhone = '';
        $this->createAddress = '';
        $this->createCustomerLevel = 1;
        $this->createAllowCredit = false;
        $this->createCreditLimit = '50000';
    }
}
