<?php

namespace App\Livewire;

use App\Modules\Customer\Models\CustomerCreditRecord;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\Sale\Models\DiscountRule;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Services\BranchSalePricing;
use App\Modules\Sale\Services\SaleDiscounts;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SaleScreen extends Component
{
    use AuthorizesPosPermissions;

    public string $search = '';

    public string $customerId = '';

    public string $saleDate = '';

    public string $paidAmount = '0';

    public string $repaymentDate = '';

    public string $categoryId = '';

    public string $branchId = '';

    public ?int $heldSaleId = null;

    public array $cart = [];

    public function mount(?int $selectedCustomer = null): void
    {
        $this->customerId = $selectedCustomer ? (string) $selectedCustomer : '';
        $this->saleDate = now()->toDateString();
        $branch = InventoryBranch::query()->forSales()->find(session('pos.branch_id'))
            ?: InventoryBranch::query()->forSales()->where('is_main', true)->first()
            ?: InventoryBranch::query()->forSales()->orderBy('name')->first();
        $this->branchId = (string) ($branch?->id ?? '');
    }

    public function updatedBranchId(): void
    {
        $this->authorizePosPermission('sales', 'create');
        if ($this->heldSaleId) {
            $heldSale = Sale::query()->where('status', 'held')->find($this->heldSaleId);
            if ($heldSale && (string) $heldSale->branch_id !== $this->branchId) {
                $this->branchId = (string) $heldSale->branch_id;
                $this->dispatch('toast', message: 'Resume this held sale at its original shop.', type: 'error', title: 'Shop cannot be changed');

                return;
            }
        }

        $branch = $this->currentBranch();
        session(['pos.branch_id' => $branch->id]);

        foreach ($this->cart as $unitId => $line) {
            $unit = ProductUnit::query()->with('product')->whereHas('product')->find($unitId);
            if (! $unit) {
                continue;
            }
            $stock = app(InventoryService::class)->balance($unit, $branch);
            $this->cart[$unitId]['stock'] = $stock;
            $this->cart[$unitId]['price'] = app(BranchSalePricing::class)->price($branch, $unit);
            $this->cart[$unitId]['discount_rule_id'] = '';
            if ($stock > 0) {
                $this->cart[$unitId]['qty'] = min((int) $line['qty'], $stock);
            }
        }

        $this->dispatch('pos-branch-changed',
            branchId: (string) $branch->id,
            isMain: $branch->is_main,
            receipt: ['branch_id' => $branch->id, ...app(BranchSalePricing::class)->receiptSnapshot($branch)],
        );
    }

    public function addUnit(int $unitId): void
    {
        $this->authorizePosPermission('sales', 'create');
        $branch = $this->currentBranch();
        $unit = ProductUnit::query()->with('product')->whereHas('product')->findOrFail($unitId);
        $stock = app(InventoryService::class)->balance($unit, $branch);

        if ($stock < 1) {
            throw ValidationException::withMessages(['cart' => 'This unit is out of stock at '.$branch->name.'.']);
        }

        if (isset($this->cart[$unitId])) {
            $this->cart[$unitId]['qty'] = min($stock, $this->cart[$unitId]['qty'] + 1);
            $this->cart[$unitId]['stock'] = $stock;

            return;
        }

        $this->cart[$unitId] = [
            'unit_id' => $unit->id,
            'product' => $unit->product?->name ?: 'Unknown product',
            'unit' => $unit->name,
            'code' => $unit->code ?: $unit->original_code,
            'stock' => $stock,
            'qty' => 1,
            'price' => app(BranchSalePricing::class)->price($branch, $unit),
            'discount_rule_id' => '',
        ];
    }

    public function removeUnit(int $unitId): void
    {
        $this->authorizePosPermission('sales', 'create');
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

        $branch = $this->currentBranch();
        $unitQuery = ProductUnit::query()
            ->whereHas('product')
            ->where(function ($query) use ($scanValue): void {
                $query->where('code', $scanValue)
                    ->orWhere('original_code', $scanValue);
            });
        $this->applyBranchAvailability($unitQuery, $branch);
        $unit = $unitQuery->first();

        if (! $unit) {
            return;
        }

        $this->addUnit($unit->id);
        $this->search = '';
        $this->dispatch('sale-search-focus');
    }

    public function focusScanner(): void
    {
        $this->dispatch('sale-search-focus');
    }

    public function payFullAmount(): void
    {
        $this->paidAmount = (string) $this->total;
    }

    public function holdSale(): void
    {
        $this->authorizePosPermission('sales', 'create');
        try {
            $this->validate([
                'branchId' => ['required', 'integer', 'exists:inventory_branches,id'],
                'customerId' => ['nullable', 'exists:pos_customers,id'],
                'saleDate' => ['required', 'date'],
                'cart' => ['required', 'array', 'min:1'],
                'cart.*.unit_id' => ['required', 'exists:product_units,id'],
                'cart.*.qty' => ['required', 'integer', 'min:1'],
                'cart.*.price' => ['required', 'numeric', 'min:0'],
            ]);
        } catch (ValidationException $exception) {
            $this->toastValidationError($exception);

            return;
        }

        try {
            app(SaleDiscounts::class)->calculate($this->currentBranch(), $this->cart);
        } catch (ValidationException $exception) {
            $this->toastValidationError($exception);

            return;
        }

        DB::transaction(function (): void {
            $branch = $this->currentBranch();
            $totalQuantity = array_sum(array_map(fn ($line) => (int) $line['qty'], $this->cart));
            $calculation = app(SaleDiscounts::class)->calculate($branch, $this->cart);
            $totalPrice = $calculation['total_price'];

            $sale = $this->heldSaleId
                ? $this->heldSalesForBranch($branch)->findOrFail($this->heldSaleId)
                : new Sale(['voucher_code' => $this->nextVoucherCode()]);

            $sale->fill([
                'branch_id' => $branch->id,
                ...app(BranchSalePricing::class)->receiptSnapshot($branch),
                'customer_id' => $this->customerId ?: null,
                'sale_by' => auth('web')->id(),
                'total_quantity' => $totalQuantity,
                'total_price' => $totalPrice,
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $calculation['discount_amount'],
                'paid_amount' => 0,
                'credit_amount' => $totalPrice,
                'repayment_date' => null,
                'sale_date' => $this->saleDate,
                'status' => 'held',
            ])->save();

            $sale->items()->get()->each(fn ($item) => $item->delete());

            foreach ($calculation['items'] as $item) {
                $sale->items()->create($item);
            }

            $this->heldSaleId = $sale->id;
        });

        $this->resetSaleForm();
        $this->dispatch('sale-search-focus');
    }

    public function resumeHeldSale(int $saleId): void
    {
        $this->authorizePosPermission('sales', 'create');
        $branch = $this->currentBranch();
        $sale = $this->heldSalesForBranch($branch)
            ->with(['items.unit.product'])
            ->findOrFail($saleId);

        $this->heldSaleId = $sale->id;
        $this->branchId = (string) $branch->id;
        $this->customerId = $sale->customer_id ? (string) $sale->customer_id : '';
        $this->saleDate = $sale->sale_date?->toDateString() ?: now()->toDateString();
        $this->paidAmount = '0';
        $this->repaymentDate = '';
        $this->cart = [];

        foreach ($sale->items as $item) {
            $unit = $item->unit;

            if (! $unit) {
                continue;
            }

            $this->cart[$unit->id] = [
                'unit_id' => $unit->id,
                'product' => $unit->product?->name ?: 'Unknown product',
                'unit' => $unit->name,
                'code' => $unit->code ?: $unit->original_code,
                'stock' => app(InventoryService::class)->balance($unit, $this->currentBranch()),
                'qty' => min((int) $item->quantity, max(1, app(InventoryService::class)->balance($unit, $this->currentBranch()))),
                'price' => (float) $item->price,
                'discount_rule_id' => (string) ($item->discount_rule_id ?? ''),
            ];
        }

        $this->dispatch('sale-search-focus');
    }

    public function deleteHeldSale(int $saleId): void
    {
        $this->authorizePosPermission('sales', 'delete');
        $sale = $this->heldSalesForBranch($this->currentBranch())->findOrFail($saleId);

        $sale->items()->get()->each(fn ($item) => $item->delete());
        $sale->delete();

        if ($this->heldSaleId === $saleId) {
            $this->resetSaleForm();
        }

        $this->dispatch('toast', message: 'Held order deleted.');
    }

    public function resetCart(): void
    {
        $this->voidCart();
    }

    public function voidCart(): void
    {
        $this->authorizePosPermission('sales', 'create');
        $this->resetSaleForm();
        $this->dispatch('sale-search-focus');
    }

    public function updatedCart(): void
    {
        foreach ($this->cart as $unitId => $line) {
            $qty = max(1, (int) ($line['qty'] ?? 1));
            $stock = max(0, (int) ($line['stock'] ?? 0));

            $this->cart[$unitId]['qty'] = $stock > 0 ? min($qty, $stock) : 1;
            $this->cart[$unitId]['price'] = max(0, (float) ($line['price'] ?? 0));
        }
    }

    public function completeSale()
    {
        $this->authorizePosPermission('sales', 'create');
        try {
            $this->validate([
                'branchId' => ['required', 'integer', 'exists:inventory_branches,id'],
                'customerId' => ['nullable', 'exists:pos_customers,id'],
                'saleDate' => ['required', 'date'],
                'paidAmount' => ['required', 'numeric', 'min:0'],
                'repaymentDate' => ['nullable', 'date'],
                'cart' => ['required', 'array', 'min:1'],
                'cart.*.unit_id' => ['required', 'exists:product_units,id'],
                'cart.*.qty' => ['required', 'integer', 'min:1'],
                'cart.*.price' => ['required', 'numeric', 'min:0'],
            ]);
        } catch (ValidationException $exception) {
            $this->toastValidationError($exception);

            return null;
        }

        try {
            $sale = DB::transaction(function (): Sale {
                $branch = $this->currentBranch();
                $inventory = app(InventoryService::class);
                $pricing = app(BranchSalePricing::class);
                $totalQuantity = array_sum(array_map(fn ($line) => (int) $line['qty'], $this->cart));
                $calculation = app(SaleDiscounts::class)->calculate($branch, $this->cart);
                $totalPrice = $calculation['total_price'];
                $tenderedAmount = (float) $this->paidAmount;
                $paidAmount = min($tenderedAmount, $totalPrice);
                $creditAmount = round($totalPrice - $paidAmount, 2);
                $customer = $this->customerId ? PosCustomer::query()->findOrFail($this->customerId) : null;

                if ($creditAmount > 0 && ! $customer) {
                    throw ValidationException::withMessages(['paidAmount' => 'Walk-in sales must be paid in full.']);
                }

                if ($creditAmount > 0 && ! $customer->allow_credit) {
                    throw ValidationException::withMessages(['customerId' => 'This customer is not allowed to buy on credit.']);
                }

                if ($creditAmount > 0 && (float) $customer->credit_limit > 0) {
                    $existingDebt = (float) CustomerCreditRecord::query()
                        ->where('customer_id', $customer->id)
                        ->whereNull('repayment_date')
                        ->sum('amount');
                    $newTotalDebt = $existingDebt + $creditAmount;
                    if ($newTotalDebt > (float) $customer->credit_limit) {
                        $available = max(0, (float) $customer->credit_limit - $existingDebt);
                        $minPayment = max(0, $totalPrice - $available);
                        throw ValidationException::withMessages([
                            'paidAmount' => 'Credit limit exceeded! Customer credit limit is '.number_format((float) $customer->credit_limit, 2).' (Current debt: '.number_format($existingDebt, 2).', Available: '.number_format($available, 2).'). Minimum payment required is '.number_format($minPayment, 2).'.',
                        ]);
                    }
                }

                $sale = $this->heldSaleId
                    ? $this->heldSalesForBranch($branch)->findOrFail($this->heldSaleId)
                    : new Sale(['voucher_code' => $this->nextVoucherCode()]);

                $sale->fill([
                    'branch_id' => $branch->id,
                    ...$pricing->receiptSnapshot($branch),
                    'customer_id' => $customer?->id,
                    'sale_by' => auth('web')->id(),
                    'total_quantity' => $totalQuantity,
                    'total_price' => $totalPrice,
                    'subtotal' => $calculation['subtotal'],
                    'discount_amount' => $calculation['discount_amount'],
                    'paid_amount' => $paidAmount,
                    'credit_amount' => $creditAmount,
                    'repayment_date' => $creditAmount > 0 ? ($this->repaymentDate ?: null) : null,
                    'sale_date' => $this->saleDate,
                    'status' => 'completed',
                ])->save();

                $sale->items()->get()->each(fn ($item) => $item->delete());

                foreach ($this->cart as $lineKey => $line) {
                    $unit = ProductUnit::query()->lockForUpdate()->findOrFail($line['unit_id']);
                    $quantity = (int) $line['qty'];
                    $available = $inventory->balance($unit, $branch);

                    if ($available < $quantity) {
                        throw ValidationException::withMessages(['cart' => 'Not enough stock for '.$unit->name.' at '.$branch->name.'.']);
                    }

                    $sale->items()->create($calculation['items'][$lineKey]);

                    $inventory->sell($unit, $branch, $quantity, auth('web')->id(), $sale->id);
                }

                if ($creditAmount > 0 && $customer) {
                    CustomerCreditRecord::query()->create([
                        'customer_id' => $customer->id,
                        'sale_id' => $sale->id,
                        'amount' => $creditAmount,
                        'repayment_date' => $this->repaymentDate ?: null,
                    ]);
                }

                return $sale;
            });
        } catch (ValidationException $exception) {
            $this->toastValidationError($exception);

            return null;
        }

        return redirect()->route('management.sales.show', $sale)->with('status', 'Sale completed and stock updated.');
    }

    public function getSubtotalProperty(): float
    {
        return round(array_sum(array_map(fn ($line) => (int) ($line['qty'] ?? 0) * (float) ($line['price'] ?? 0), $this->cart)), 2);
    }

    public function getDiscountCalculationProperty(): array
    {
        if ($this->cart === []) {
            return ['subtotal' => 0, 'discount_amount' => 0, 'total_price' => 0, 'items' => []];
        }
        try {
            return app(SaleDiscounts::class)->calculate($this->currentBranch(), $this->cart);
        } catch (ValidationException $exception) {
            return ['subtotal' => $this->subtotal, 'discount_amount' => 0, 'total_price' => $this->subtotal,
                'items' => [], 'error' => $exception->validator->errors()->first()];
        }
    }

    public function getTotalProperty(): float
    {
        return (float) $this->discountCalculation['total_price'];
    }

    public function getCreditAmountProperty(): float
    {
        return max(0, $this->total - (float) $this->paidAmount);
    }

    public function getChangeAmountProperty(): float
    {
        return max(0, (float) $this->paidAmount - $this->total);
    }

    public function getSelectedCustomerModelProperty(): ?PosCustomer
    {
        return $this->customerId ? PosCustomer::query()->find($this->customerId) : null;
    }

    public function render()
    {
        $this->authorizePosPermission('sales', 'create');
        $branch = $this->currentBranch();
        $inventory = app(InventoryService::class);
        $products = ProductUnit::query()
            ->with(['product.category'])
            ->whereHas('product')
            ->tap(fn ($query) => $this->applyBranchAvailability($query, $branch))
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
            ->limit(24)
            ->get();
        foreach ($products as $unit) {
            $unit->setAttribute('branch_stock', $inventory->balance($unit, $branch));
            $unit->setAttribute('branch_price', app(BranchSalePricing::class)->price($branch, $unit));
        }

        $customers = PosCustomer::query()->orderBy('name')->get();
        $categories = ProductCategory::query()->orderBy('name')->get();
        $heldSales = $this->heldSalesForBranch($branch)
            ->with('customer')
            ->latest()
            ->limit(6)
            ->get();
        $branches = InventoryBranch::query()->forSales()->orderBy('name')->get();

        $discountRules = DiscountRule::query()->forBranch($branch->id)->where('is_active', true)->orderBy('name')->get();

        return view('livewire.sale-screen', compact('discountRules', 'products', 'customers', 'categories', 'heldSales', 'branches', 'branch'));
    }

    private function resetSaleForm(): void
    {
        $this->heldSaleId = null;
        $this->customerId = '';
        $this->saleDate = now()->toDateString();
        $this->paidAmount = '0';
        $this->repaymentDate = '';
        $this->cart = [];
    }

    private function currentBranch(): InventoryBranch
    {
        if ($this->branchId !== '') {
            return InventoryBranch::query()->forSales()->findOrFail($this->branchId);
        }

        $branch = InventoryBranch::query()->forSales()->where('is_main', true)->first()
            ?: InventoryBranch::query()->forSales()->orderBy('name')->first();
        abort_unless($branch, 422, 'Create an active shop before recording sales.');
        $this->branchId = (string) $branch->id;
        session(['pos.branch_id' => $branch->id]);

        return $branch;
    }

    private function heldSalesForBranch(InventoryBranch $branch): Builder
    {
        return Sale::query()
            ->where('status', 'held')
            ->where(function ($query) use ($branch): void {
                $query->where('branch_id', $branch->id);
                if ($branch->is_main) {
                    $query->orWhereNull('branch_id');
                }
            });
    }

    private function applyBranchAvailability($query, InventoryBranch $branch): void
    {
        if ($branch->is_main) {
            $query->where('current_quantity', '>', 0);

            return;
        }

        $query->whereIn('id', InventoryLot::query()
            ->where('branch_id', $branch->id)
            ->where('quantity', '>', 0)
            ->select('product_unit_id'));
    }

    private function nextVoucherCode(): string
    {
        return 'VOU-'.now()->format('dmY').'-'.str_pad((string) (Sale::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT);
    }

    private function toastValidationError(ValidationException $exception): void
    {
        $this->dispatch('toast', message: $exception->validator->errors()->first(), type: 'error', title: 'Error');
    }
}
