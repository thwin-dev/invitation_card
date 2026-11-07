<?php

namespace App\Livewire;

use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Customer\Services\CustomerExcelService;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CustomerTable extends Component
{
    use WithPagination;
    use WithFileUploads;
    use AuthorizesPosPermissions;

    public string $search = '';
    public bool $createOpen = false;
    public bool $importOpen = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // File attachments for create
    public $createLogo = null;
    public $createQrCode = null;

    // File attachments for edit
    public $editLogo = null;
    public $editQrCode = null;
    public ?string $currentLogoUrl = null;
    public ?string $currentQrCodeUrl = null;
    public bool $removeEditLogo = false;
    public bool $removeEditQrCode = false;

    // Preview modal
    public ?string $previewUrl = null;
    public ?string $previewTitle = null;

    // Import state
    public $importFile = null;
    public array $importErrors = [];
    public ?string $importSuccessMessage = null;

    // Form fields
    public string $createName = '';
    public string $createPhone = '';
    public string $createAddress = '';
    public int $createCustomerLevel = 1;

    public string $editName = '';
    public string $editPhone = '';
    public string $editAddress = '';
    public int $editCustomerLevel = 1;

    public function mount(): void
    {
        $this->search = (string) request('search', '');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('open-customer-create')]
    #[On('open-exhibitor-create')]
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
        $this->resetCreateForm();
    }

    #[On('open-customer-import')]
    #[On('open-exhibitor-import')]
    public function openImport(): void
    {
        $this->authorizePosPermission('customers', 'import');
        $this->resetValidation();
        $this->importFile = null;
        $this->importErrors = [];
        $this->importSuccessMessage = null;
        $this->importOpen = true;
    }

    public function closeImport(): void
    {
        $this->importOpen = false;
        $this->importFile = null;
        $this->importErrors = [];
        $this->importSuccessMessage = null;
    }

    public function openPreview(string $url, string $title = 'Attachment Preview'): void
    {
        $this->previewUrl = $url;
        $this->previewTitle = $title;
    }

    public function closePreview(): void
    {
        $this->previewUrl = null;
        $this->previewTitle = null;
    }

    public function createCustomer(): void
    {
        $this->authorizePosPermission('customers', 'create');
        $data = $this->validate($this->createRules());

        $logoPath = $this->createLogo ? $this->createLogo->store('customers/logos', 'public') : null;
        $qrCodePath = $this->createQrCode ? $this->createQrCode->store('customers/qr_codes', 'public') : null;

        PosCustomer::query()->create([
            'name' => $data['createName'],
            'phone' => $data['createPhone'] ?: null,
            'address' => $data['createAddress'] ?: null,
            'customer_level' => $data['createCustomerLevel'],
            'logo_path' => $logoPath,
            'qr_code_path' => $qrCodePath,
            'created_by' => auth('web')->id(),
        ]);

        $this->resetCreateForm();
        $this->resetPage();
        $this->createOpen = false;
        $this->dispatch('toast', message: 'Exhibitor created successfully.');
    }

    public function editCustomer(int $customerId): void
    {
        $this->authorizePosPermission('customers', 'edit');
        $customer = PosCustomer::query()->findOrFail($customerId);
        $this->resetValidation();
        $this->editingId = $customer->id;
        $this->editName = $customer->name;
        $this->editPhone = (string) ($customer->phone ?? '');
        $this->editAddress = (string) ($customer->address ?? '');
        $this->editCustomerLevel = (int) $customer->customer_level;
        $this->currentLogoUrl = $customer->logo_url;
        $this->currentQrCodeUrl = $customer->qr_code_url;
        $this->editLogo = null;
        $this->editQrCode = null;
        $this->removeEditLogo = false;
        $this->removeEditQrCode = false;
    }

    public function closeEdit(): void
    {
        $this->editingId = null;
        $this->editLogo = null;
        $this->editQrCode = null;
        $this->currentLogoUrl = null;
        $this->currentQrCodeUrl = null;
        $this->removeEditLogo = false;
        $this->removeEditQrCode = false;
        $this->resetValidation();
    }

    public function updateCustomer(): void
    {
        $this->authorizePosPermission('customers', 'edit');
        $data = $this->validate($this->editRules());

        if (! $this->editingId) {
            return;
        }

        $customer = PosCustomer::query()->findOrFail($this->editingId);

        $updateData = [
            'name' => $data['editName'],
            'phone' => $data['editPhone'] ?: null,
            'address' => $data['editAddress'] ?: null,
            'customer_level' => $data['editCustomerLevel'],
        ];

        // Handle logo update / removal
        if ($this->editLogo) {
            if ($customer->logo_path && Storage::disk('public')->exists($customer->logo_path)) {
                Storage::disk('public')->delete($customer->logo_path);
            }
            $updateData['logo_path'] = $this->editLogo->store('customers/logos', 'public');
        } elseif ($this->removeEditLogo) {
            if ($customer->logo_path && Storage::disk('public')->exists($customer->logo_path)) {
                Storage::disk('public')->delete($customer->logo_path);
            }
            $updateData['logo_path'] = null;
        }

        // Handle QR code update / removal
        if ($this->editQrCode) {
            if ($customer->qr_code_path && Storage::disk('public')->exists($customer->qr_code_path)) {
                Storage::disk('public')->delete($customer->qr_code_path);
            }
            $updateData['qr_code_path'] = $this->editQrCode->store('customers/qr_codes', 'public');
        } elseif ($this->removeEditQrCode) {
            if ($customer->qr_code_path && Storage::disk('public')->exists($customer->qr_code_path)) {
                Storage::disk('public')->delete($customer->qr_code_path);
            }
            $updateData['qr_code_path'] = null;
        }

        $customer->update($updateData);

        $this->closeEdit();
        $this->dispatch('toast', message: 'Exhibitor updated successfully.');
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
        $customer = PosCustomer::query()->findOrFail($this->deletingId);

        if ($customer->logo_path && Storage::disk('public')->exists($customer->logo_path)) {
            Storage::disk('public')->delete($customer->logo_path);
        }
        if ($customer->qr_code_path && Storage::disk('public')->exists($customer->qr_code_path)) {
            Storage::disk('public')->delete($customer->qr_code_path);
        }

        $customer->delete();
        $this->deletingId = null;
        $this->dispatch('toast', message: 'Exhibitor deleted successfully.');
    }

    public function importCustomers(CustomerExcelService $service): void
    {
        $this->authorizePosPermission('customers', 'import');
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = $service->import($this->importFile->getRealPath(), auth('web')->id());

        $this->importErrors = $result['errors'];
        $this->resetPage();

        if ($result['imported'] > 0) {
            $msg = "Successfully imported {$result['imported']} exhibitor(s).";
            if ($result['skipped'] > 0) {
                $msg .= " ({$result['skipped']} row(s) skipped)";
            }
            $this->dispatch('toast', message: $msg);
            $this->importSuccessMessage = $msg;

            if (empty($this->importErrors)) {
                $this->closeImport();
            }
        } else {
            if ($result['skipped'] > 0) {
                $this->addError('importFile', "No exhibitors imported. {$result['skipped']} row(s) were skipped due to missing name.");
            } else {
                $this->addError('importFile', 'No exhibitor records found in the uploaded file.');
            }
        }
    }

    public function render()
    {
        $this->authorizePosPermission('customers', 'view');
        $customers = PosCustomer::query()
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('address', 'like', "%{$this->search}%")))
            ->latest('id')
            ->paginate(15);

        return view('livewire.customer-table', compact('customers'));
    }

    protected function validationAttributes(): array
    {
        return [
            'createName' => 'name',
            'createPhone' => 'phone',
            'createAddress' => 'address',
            'createCustomerLevel' => 'exhibitor level',
            'createLogo' => 'exhibitor logo',
            'createQrCode' => 'QR attachment',
            'editName' => 'name',
            'editPhone' => 'phone',
            'editAddress' => 'address',
            'editCustomerLevel' => 'exhibitor level',
            'editLogo' => 'exhibitor logo',
            'editQrCode' => 'QR attachment',
        ];
    }

    private function createRules(): array
    {
        return [
            'createName' => ['required', 'string', 'max:255'],
            'createPhone' => ['nullable', 'string', 'max:255'],
            'createAddress' => ['nullable', 'string'],
            'createCustomerLevel' => ['required', 'integer', 'in:1,2,3'],
            'createLogo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:10240'],
            'createQrCode' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:10240'],
        ];
    }

    private function editRules(): array
    {
        return [
            'editName' => ['required', 'string', 'max:255'],
            'editPhone' => ['nullable', 'string', 'max:255'],
            'editAddress' => ['nullable', 'string'],
            'editCustomerLevel' => ['required', 'integer', 'in:1,2,3'],
            'editLogo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:10240'],
            'editQrCode' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:10240'],
        ];
    }

    private function resetCreateForm(): void
    {
        $this->createName = '';
        $this->createPhone = '';
        $this->createAddress = '';
        $this->createCustomerLevel = 1;
        $this->createLogo = null;
        $this->createQrCode = null;
    }
}
