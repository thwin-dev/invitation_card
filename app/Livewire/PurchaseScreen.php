<?php

namespace App\Livewire;

use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Support\StockMovementRecorder;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\Purchase\Models\PosSupplier;
use App\Modules\Purchase\Models\Purchase;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PurchaseScreen extends Component
{
    use AuthorizesPosPermissions;

    public string $search = '';

    public string $supplierId = '';

    public string $supplierName = '';

    public bool $isCustomSupplier = false;

    public string $purchaseDate = '';

    public string $paidAmount = '0';

    public string $paymentDueDate = '';

    public string $categoryId = '';

    public array $cart = [];

    public function mount(?int $selectedSupplierId = null): void
    {
        $this->supplierId = $selectedSupplierId ? (string) $selectedSupplierId : '';
        $this->purchaseDate = now()->toDateString();
    }

    public function addUnit(int $unitId): void
    {
        $this->authorizePosPermission('purchases', 'create');
        $unit = ProductUnit::query()->with('product')->whereHas('product')->findOrFail($unitId);

        if (isset($this->cart[$unitId])) {
            $this->cart[$unitId]['qty'] = (int) $this->cart[$unitId]['qty'] + 1;

            return;
        }

        $this->cart[$unitId] = [
            'unit_id' => $unit->id,
            'product' => $unit->product?->name ?: 'Unknown product',
            'unit' => $unit->name,
            'code' => $unit->code ?: $unit->original_code,
            'stock' => $unit->current_quantity,
            'qty' => 1, 'batch_number' => '', 'serial_number' => '', 'expires_at' => '',
            'price' => (float) ($unit->purchase_price ?: 0),
        ];
    }

    public function removeUnit(int $unitId): void
    {
        $this->authorizePosPermission('purchases', 'create');
        unset($this->cart[$unitId]);
    }

    public function selectCategory(string $categoryId = ''): void
    {
        $this->categoryId = $categoryId;
    }

    public function updatedSearch(): void
    {
        $scanValue = trim($this->search);

        if ($scanValue === '') {
            return;
        }

        $unit = ProductUnit::query()
            ->whereHas('product')
            ->where(function ($query) use ($scanValue): void {
                $query->where('code', $scanValue)
                    ->orWhere('original_code', $scanValue);
            })
            ->first();

        if (! $unit) {
            return;
        }

        $this->addUnit($unit->id);
        $this->search = '';
        $this->dispatch('purchase-search-focus');
    }

    public function focusScanner(): void
    {
        $this->dispatch('purchase-search-focus');
    }

    public function toggleCustomSupplier(): void
    {
        $this->isCustomSupplier = ! $this->isCustomSupplier;
        if (! $this->isCustomSupplier) {
            $this->supplierName = '';
        } else {
            $this->supplierId = '';
        }
    }

    public function payFullAmount(): void
    {
        $this->paidAmount = (string) $this->subtotal;
    }

    public function resetCart(): void
    {
        $this->cart = [];
        $this->paidAmount = '0';
        $this->paymentDueDate = '';
        $this->dispatch('purchase-search-focus');
    }

    public function updatedCart(): void
    {
        foreach ($this->cart as $unitId => $line) {
            $this->cart[$unitId]['qty'] = max(1, (int) ($line['qty'] ?? 1));
            $this->cart[$unitId]['price'] = max(0, (float) ($line['price'] ?? 0));
        }
    }

    public function savePurchase()
    {
        $this->authorizePosPermission('purchases', 'create');
        try {
            $rules = [
                'purchaseDate' => ['required', 'date'],
                'paidAmount' => ['required', 'numeric', 'min:0'],
                'paymentDueDate' => ['nullable', 'date'],
                'cart' => ['required', 'array', 'min:1'],
                'cart.*.unit_id' => ['required', 'exists:product_units,id'],
                'cart.*.qty' => ['required', 'integer', 'min:1'],
                'cart.*.batch_number' => ['nullable', 'string', 'max:255'],
                'cart.*.serial_number' => ['nullable', 'string', 'max:255'],
                'cart.*.expires_at' => ['nullable', 'date'],
                'cart.*.price' => ['required', 'numeric', 'min:0'],
            ];

            if ($this->isCustomSupplier) {
                $rules['supplierName'] = ['required', 'string', 'max:255'];
            } else {
                $rules['supplierId'] = ['required_without:supplierName', 'nullable', 'exists:pos_suppliers,id'];
            }

            $this->validate($rules, [
                'supplierId.required_without' => 'Please choose a supplier or enter a supplier name.',
                'supplierName.required' => 'Supplier name is required.',
                'cart.required' => 'Please add at least one product unit to the purchase cart.',
                'cart.min' => 'Please add at least one product unit to the purchase cart.',
            ]);
        } catch (ValidationException $exception) {
            $this->toastValidationError($exception);

            return null;
        }

        try {
            $purchase = DB::transaction(function (): Purchase {
                $totalQuantity = array_sum(array_map(fn ($line) => (int) $line['qty'], $this->cart));
                $totalPrice = array_sum(array_map(fn ($line) => (int) $line['qty'] * (float) $line['price'], $this->cart));
                $tenderedPaid = (float) $this->paidAmount;
                $paidAmount = min($tenderedPaid, $totalPrice);
                $payableAmount = max(0, $totalPrice - $paidAmount);
                $paymentStatus = $payableAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

                $supplier = ! empty($this->supplierId) ? PosSupplier::query()->find($this->supplierId) : null;
                $supplierName = $supplier
                    ? ($supplier->name.($supplier->company_name ? " ({$supplier->company_name})" : ''))
                    : ($this->supplierName ?: 'Supplier');

                $purchase = Purchase::query()->create([
                    'supplier_id' => $supplier?->id,
                    'supplier_name' => $supplierName,
                    'total_quantity' => $totalQuantity,
                    'total_price' => $totalPrice,
                    'paid_amount' => $paidAmount,
                    'payable_amount' => $payableAmount,
                    'payment_status' => $paymentStatus,
                    'payment_due_date' => $this->paymentDueDate ?: null,
                    'purchase_date' => $this->purchaseDate,
                    'purchase_by' => auth('web')->id(),
                ]);

                foreach ($this->cart as $line) {
                    $unit = ProductUnit::query()->lockForUpdate()->findOrFail($line['unit_id']);
                    $quantity = (int) $line['qty'];
                    $price = (float) $line['price'];
                    $before = $unit->current_quantity;

                    $unit->increment('current_quantity', $quantity);
                    StockMovementRecorder::record(
                        $unit,
                        'IN',
                        $before,
                        $before + $quantity,
                        auth('web')->id(),
                        'purchase',
                        $purchase->id,
                        'Purchase order received',
                        ['batch_number' => ($line['batch_number'] ?? '') ?: null, 'serial_number' => ($line['serial_number'] ?? '') ?: null, 'expires_at' => ($line['expires_at'] ?? '') ?: null]
                    );
                    $unit->update(['purchase_price' => $price]);

                    $purchase->items()->create([
                        'product_unit_id' => $unit->id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'batch_number' => ($line['batch_number'] ?? '') ?: null,
                        'serial_number' => ($line['serial_number'] ?? '') ?: null,
                        'expires_at' => ($line['expires_at'] ?? '') ?: null,
                    ]);
                }

                return $purchase;
            });

            session()->flash('status', 'Purchase order #'.$purchase->id.' recorded successfully.');

            return redirect()->route('management.purchases.index');
        } catch (\Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error', title: 'Purchase Failed');

            return null;
        }
    }

    public function getSubtotalProperty(): float
    {
        return array_sum(array_map(fn ($line) => (int) $line['qty'] * (float) $line['price'], $this->cart));
    }

    public function getTotalQuantityProperty(): int
    {
        return array_sum(array_map(fn ($line) => (int) $line['qty'], $this->cart));
    }

    public function getPayableAmountProperty(): float
    {
        return max(0, $this->subtotal - (float) $this->paidAmount);
    }

    public function getSelectedSupplierModelProperty(): ?PosSupplier
    {
        return ! empty($this->supplierId) ? PosSupplier::query()->find($this->supplierId) : null;
    }

    public function render()
    {
        $this->authorizePosPermission('purchases', 'create');
        $products = ProductUnit::query()
            ->with(['product.category'])
            ->whereHas('product')
            ->when($this->categoryId, fn ($query) => $query->whereHas('product', fn ($product) => $product->where('category_id', $this->categoryId)))
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('original_code', 'like', "%{$this->search}%")
                        ->orWhereHas('product', fn ($product) => $product
                            ->where('name', 'like', "%{$this->search}%")
                            ->orWhere('code', 'like', "%{$this->search}%"));
                });
            })
            ->orderBy('name')
            ->limit(32)
            ->get();

        $suppliers = PosSupplier::query()->orderBy('name')->get();
        $categories = ProductCategory::query()->orderBy('name')->get();

        return view('livewire.purchase-screen', compact('products', 'suppliers', 'categories'));
    }

    private function toastValidationError(ValidationException $exception): void
    {
        $this->dispatch('toast', message: $exception->validator->errors()->first(), type: 'error', title: 'Error');
    }
}
