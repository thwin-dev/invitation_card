<?php

namespace App\Livewire;

use App\Modules\Purchase\Models\PosSupplier;
use App\Support\AuthorizesPosPermissions;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierTable extends Component
{
    use WithPagination;
    use AuthorizesPosPermissions;

    public string $search = '';
    public bool $createOpen = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $createName = '';
    public string $createCompany = '';
    public string $createPhone = '';
    public string $createAddress = '';

    public string $editName = '';
    public string $editCompany = '';
    public string $editPhone = '';
    public string $editAddress = '';

    public function mount(): void
    {
        $this->search = (string) request('search', '');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('open-supplier-create')]
    public function openCreate(): void
    {
        $this->authorizePosPermission('suppliers', 'create');
        $this->resetValidation();
        $this->resetCreateForm();
        $this->createOpen = true;
    }

    public function closeCreate(): void
    {
        $this->createOpen = false;
    }

    public function createSupplier(): void
    {
        $this->authorizePosPermission('suppliers', 'create');
        $data = $this->validate($this->createRules());

        PosSupplier::query()->create([
            'name' => $data['createName'],
            'company_name' => $data['createCompany'] ?: null,
            'phone' => $data['createPhone'] ?: null,
            'address' => $data['createAddress'] ?: null,
            'created_by' => auth('web')->id(),
        ]);

        $this->resetCreateForm();
        $this->resetPage();
        $this->createOpen = false;
        $this->dispatch('toast', message: 'Supplier created successfully.');
    }

    public function editSupplier(int $supplierId): void
    {
        $this->authorizePosPermission('suppliers', 'edit');
        $supplier = PosSupplier::query()->findOrFail($supplierId);
        $this->resetValidation();
        $this->editingId = $supplier->id;
        $this->editName = $supplier->name;
        $this->editCompany = (string) $supplier->company_name;
        $this->editPhone = (string) $supplier->phone;
        $this->editAddress = (string) $supplier->address;
    }

    public function closeEdit(): void
    {
        $this->editingId = null;
    }

    public function updateSupplier(): void
    {
        $this->authorizePosPermission('suppliers', 'edit');
        $data = $this->validate($this->editRules());

        PosSupplier::query()->findOrFail($this->editingId)->update([
            'name' => $data['editName'],
            'company_name' => $data['editCompany'] ?: null,
            'phone' => $data['editPhone'] ?: null,
            'address' => $data['editAddress'] ?: null,
        ]);

        $this->editingId = null;
        $this->dispatch('toast', message: 'Supplier updated successfully.');
    }

    public function confirmDelete(int $supplierId): void
    {
        $this->deletingId = $supplierId;
    }

    public function closeDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteSupplier(): void
    {
        $this->authorizePosPermission('suppliers', 'delete');
        PosSupplier::query()->findOrFail($this->deletingId)->delete();
        $this->deletingId = null;
        $this->dispatch('toast', message: 'Supplier deleted successfully.');
    }

    public function render()
    {
        $this->authorizePosPermission('suppliers', 'view');
        $suppliers = PosSupplier::query()
            ->withCount('purchases')
            ->withSum('purchases as total_purchased', 'total_price')
            ->withSum('purchases as total_paid', 'paid_amount')
            ->withSum('purchases as outstanding_payable', 'payable_amount')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('company_name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('address', 'like', "%{$this->search}%")))
            ->latest('id')
            ->paginate(15);

        return view('livewire.supplier-table', compact('suppliers'));
    }

    private function createRules(): array
    {
        return [
            'createName' => ['required', 'string', 'max:255'],
            'createCompany' => ['nullable', 'string', 'max:255'],
            'createPhone' => ['nullable', 'string', 'max:255'],
            'createAddress' => ['nullable', 'string'],
        ];
    }

    private function editRules(): array
    {
        return [
            'editName' => ['required', 'string', 'max:255'],
            'editCompany' => ['nullable', 'string', 'max:255'],
            'editPhone' => ['nullable', 'string', 'max:255'],
            'editAddress' => ['nullable', 'string'],
        ];
    }

    private function resetCreateForm(): void
    {
        $this->createName = '';
        $this->createCompany = '';
        $this->createPhone = '';
        $this->createAddress = '';
    }
}
