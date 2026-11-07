<?php

namespace App\Modules\Sale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\CustomerCreditRecord;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Services\BranchSalePricing;
use App\Modules\Sale\Services\SaleDiscounts;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleWebController extends Controller
{
    public function discounts(): View
    {
        return view('management.sales.discounts');
    }

    public function index(Request $request): View
    {
        $sales = Sale::query()->with(['customer', 'branch'])
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where(fn ($query) => $query
                ->where('voucher_code', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))))
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('sale_date', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('sale_date', '<=', $to))
            ->when($request->string('status')->trim()->value(), fn ($query, string $status) => $query->where('status', $status))
            ->latest('sale_date')->paginate(15)->withQueryString();

        return view('management.sales.index', compact('sales'));
    }

    public function create(Request $request): View
    {
        $selectedCustomer = $request->integer('customer_id');

        return view('management.sales.create', compact('selectedCustomer'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:inventory_branches,id'],
            'customer_id' => ['nullable', 'exists:pos_customers,id'],
            'sale_date' => ['required', 'date'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'repayment_date' => ['nullable', 'date'],
            'unit' => ['required', 'array', 'min:1'],
            'unit.*' => ['required', 'exists:product_units,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'array'],
            'price.*' => ['required', 'numeric', 'min:0'],
            'discount_rule' => ['nullable', 'array'],
            'discount_rule.*' => ['nullable', 'integer'],
            'item_id' => ['nullable', 'array'],
            'item_id.*' => ['nullable', 'integer', 'distinct'],
        ]);

        $sale = DB::transaction(function () use ($data, $request): Sale {
            $branch = $this->branchForId($data['branch_id'] ?? null);
            $inventory = app(InventoryService::class);
            $lines = $this->formLines($data);
            $calculation = app(SaleDiscounts::class)->calculate($branch, $lines);
            $totalQuantity = array_sum(array_column($lines, 'qty'));
            $totalPrice = $calculation['total_price'];
            $paidAmount = min((float) $data['paid_amount'], $totalPrice);
            $creditAmount = round($totalPrice - $paidAmount, 2);
            $customer = ! empty($data['customer_id']) ? PosCustomer::query()->findOrFail($data['customer_id']) : null;

            abort_if($creditAmount > 0 && ! $customer, 422, 'A customer is required for credit sales.');
            abort_if($creditAmount > 0 && ! $customer->allow_credit, 422, 'This customer is not allowed to buy on credit.');
            $this->ensureCreditLimit($customer, $creditAmount);

            $sale = Sale::query()->create([
                'voucher_code' => 'VOU-'.now()->format('dmY').'-'.str_pad((string) (Sale::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT),
                'branch_id' => $branch->id,
                ...app(BranchSalePricing::class)->receiptSnapshot($branch),
                'customer_id' => $customer?->id,
                'sale_by' => $request->user()->id,
                'total_quantity' => $totalQuantity,
                'total_price' => $totalPrice,
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $calculation['discount_amount'],
                'paid_amount' => $paidAmount,
                'credit_amount' => $creditAmount,
                'repayment_date' => $creditAmount > 0 ? ($data['repayment_date'] ?? null) : null,
                'sale_date' => $data['sale_date'],
            ]);

            foreach ($data['unit'] as $index => $unitId) {
                $unit = ProductUnit::query()->lockForUpdate()->findOrFail($unitId);
                $quantity = (int) $data['qty'][$index];
                abort_if($inventory->balance($unit, $branch) < $quantity, 422, 'Not enough stock for '.$unit->name.' at '.$branch->name.'.');
                $sale->items()->create($calculation['items'][$index]);
                $inventory->sell($unit, $branch, $quantity, $request->user()->id, $sale->id);
            }

            if ($creditAmount > 0) {
                CustomerCreditRecord::query()->create([
                    'customer_id' => $customer->id,
                    'sale_id' => $sale->id,
                    'amount' => $creditAmount,
                    'repayment_date' => $data['repayment_date'] ?? null,
                ]);
            }

            return $sale;
        });

        return to_route('management.sales.show', $sale)->with('status', 'Sale completed and stock updated.');
    }

    public function show(Sale $sale): View
    {
        return view('management.sales.show', ['sale' => $sale->load(['customer', 'items.unit.product', 'seller', 'branch'])]);
    }

    public function print(Sale $sale): View
    {
        return view('management.sales.print', ['sale' => $sale->load(['customer', 'items.unit.product', 'seller', 'branch'])]);
    }

    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        abort_if($sale->status === 'cancelled', 422, 'This sale is already cancelled.');
        abort_if($sale->status === 'held', 422, 'Held sales cannot be cancelled as completed sales.');

        DB::transaction(function () use ($sale, $request): void {
            app(InventoryService::class)->restoreSale($sale, $request->user()->id, 'Sale cancelled');
            CustomerCreditRecord::query()->where('sale_id', $sale->id)->get()->each(fn ($credit) => $credit->delete());
            $sale->update(['status' => 'cancelled', 'paid_amount' => 0, 'credit_amount' => 0, 'repayment_date' => null]);
        });

        return to_route('management.sales.show', $sale)->with('status', 'Sale cancelled and stock restored.');
    }

    public function edit(Sale $sale): View
    {
        abort_if($sale->status === 'cancelled' || $sale->status === 'held', 422, 'Only completed sales can be edited.');
        $sale->load('items');
        $branch = $this->branchForId($sale->branch_id);
        $inventory = app(InventoryService::class);
        $saleQuantities = $sale->items->groupBy('product_unit_id')->map(fn ($items) => $items->sum('quantity'));
        $units = ProductUnit::query()->with('product')->whereHas('product')->orderBy('name')->get()
            ->filter(fn (ProductUnit $unit) => $inventory->balance($unit, $branch) > 0 || $saleQuantities->has($unit->id))
            ->values();
        foreach ($units as $unit) {
            $unit->setAttribute('branch_quantity', $inventory->balance($unit, $branch) + (int) $saleQuantities->get($unit->id, 0));
        }
        $customers = PosCustomer::query()->orderBy('name')->get();

        return view('management.sales.edit', compact('sale', 'units', 'customers', 'branch'));
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        abort_if($sale->status === 'cancelled' || $sale->status === 'held', 422, 'Only completed sales can be edited.');
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:pos_customers,id'],
            'sale_date' => ['required', 'date'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'repayment_date' => ['nullable', 'date'],
            'unit' => ['required', 'array', 'min:1'],
            'unit.*' => ['required', 'exists:product_units,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'array'],
            'price.*' => ['required', 'numeric', 'min:0'],
            'discount_rule' => ['nullable', 'array'],
            'discount_rule.*' => ['nullable', 'integer'],
            'item_id' => ['nullable', 'array'],
            'item_id.*' => ['nullable', 'integer', 'distinct'],
        ]);

        DB::transaction(function () use ($data, $sale, $request): void {
            $sale->load('items');
            $branch = $this->branchForId($sale->branch_id);
            $inventory = app(InventoryService::class);
            $inventory->restoreSale($sale, $request->user()->id, 'Sale edit rollback');

            $lines = $this->formLines($data);
            $calculation = app(SaleDiscounts::class)->calculate($branch, $lines, $sale->items->keyBy('id')->all());
            $totalQuantity = array_sum(array_column($lines, 'qty'));
            $totalPrice = $calculation['total_price'];
            $paid = min((float) $data['paid_amount'], $totalPrice);
            $credit = round($totalPrice - $paid, 2);
            $customer = ! empty($data['customer_id']) ? PosCustomer::query()->findOrFail($data['customer_id']) : null;

            abort_if($credit > 0 && ! $customer, 422, 'A customer is required for credit sales.');
            abort_if($credit > 0 && ! $customer->allow_credit, 422, 'This customer is not allowed to buy on credit.');
            $this->ensureCreditLimit($customer, $credit, $sale->id);

            $sale->update([
                'customer_id' => $customer?->id,
                'sale_date' => $data['sale_date'],
                'total_quantity' => $totalQuantity,
                'total_price' => $totalPrice,
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $calculation['discount_amount'],
                'paid_amount' => $paid,
                'credit_amount' => $credit,
                'repayment_date' => $credit > 0 ? ($data['repayment_date'] ?? null) : null,
                ...app(BranchSalePricing::class)->receiptSnapshot($branch),
            ]);
            $sale->items()->get()->each(fn ($item) => $item->delete());
            CustomerCreditRecord::query()->where('sale_id', $sale->id)->get()->each(fn ($credit) => $credit->delete());

            foreach ($data['unit'] as $index => $unitId) {
                $unit = ProductUnit::query()->lockForUpdate()->findOrFail($unitId);
                $quantity = (int) $data['qty'][$index];
                abort_if($inventory->balance($unit, $branch) < $quantity, 422, 'Not enough stock for '.$unit->name.' at '.$branch->name.'.');
                $inventory->sell($unit, $branch, $quantity, $request->user()->id, $sale->id);
                $sale->items()->create($calculation['items'][$index]);
            }

            if ($credit > 0) {
                CustomerCreditRecord::query()->create(['customer_id' => $customer->id, 'sale_id' => $sale->id, 'amount' => $credit, 'repayment_date' => $data['repayment_date'] ?? null]);
            }
        });

        return to_route('management.sales.show', $sale)->with('status', 'Sale updated and stock reconciled.');
    }

    public function export(Request $request)
    {
        $sales = Sale::query()->with(['customer', 'branch'])
            ->when($request->date('from'), fn ($query, $date) => $query->whereDate('sale_date', '>=', $date))
            ->when($request->date('to'), fn ($query, $date) => $query->whereDate('sale_date', '<=', $date))
            ->latest('sale_date')->get();
        ActivityLogger::record('sales', 'exported', 'Exported sales CSV');

        return response()->streamDownload(function () use ($sales): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Voucher', 'Date', 'Branch', 'Customer', 'Subtotal', 'Discount', 'Total', 'Paid', 'Credit', 'Status']);
            foreach ($sales as $sale) {
                fputcsv($out, [$sale->voucher_code, $sale->sale_date?->toDateString(), $sale->branch?->name ?? $sale->branch_name_snapshot, $sale->customer?->name ?: 'Walk-in', $sale->subtotal, $sale->discount_amount, $sale->total_price, $sale->paid_amount, $sale->credit_amount, $sale->status]);
            }
            fclose($out);
        }, 'sales-'.now()->format('Y-m-d').'.csv');
    }

    private function branchForId(?int $branchId): InventoryBranch
    {
        $branches = InventoryBranch::query()->forSales();
        if ($branchId !== null) {
            return $branches->findOrFail($branchId);
        }

        $branch = $branches->find(session('pos.branch_id'))
            ?: InventoryBranch::query()->forSales()->where('is_main', true)->first()
            ?: InventoryBranch::query()->forSales()->orderBy('name')->first();
        abort_unless($branch, 422, 'Create an active shop before recording sales.');

        return $branch;
    }

    private function formLines(array $data): array
    {
        if (array_keys($data['unit']) !== array_keys($data['qty']) || array_keys($data['unit']) !== array_keys($data['price'])) {
            throw ValidationException::withMessages(['unit' => 'Every sale item needs a quantity and price.']);
        }
        $lines = [];
        foreach ($data['unit'] as $index => $unitId) {
            $lines[$index] = ['unit_id' => $unitId, 'qty' => $data['qty'][$index], 'price' => $data['price'][$index],
                'discount_rule_id' => $data['discount_rule'][$index] ?? null, 'item_id' => $data['item_id'][$index] ?? null];
        }

        return $lines;
    }

    private function ensureCreditLimit(?PosCustomer $customer, float $creditAmount, ?int $exceptSaleId = null): void
    {
        if (! $customer || $creditAmount <= 0 || (float) $customer->credit_limit <= 0) {
            return;
        }

        $existingDebt = (float) CustomerCreditRecord::query()
            ->where('customer_id', $customer->id)
            ->when($exceptSaleId, fn ($query) => $query->where('sale_id', '!=', $exceptSaleId))
            ->whereNull('repayment_date')
            ->sum('amount');
        abort_if(($existingDebt + $creditAmount) > (float) $customer->credit_limit, 422, 'Credit limit exceeded. Customer credit limit is '.number_format((float) $customer->credit_limit, 2).', current debt is '.number_format($existingDebt, 2).', available credit is '.number_format(max(0, (float) $customer->credit_limit - $existingDebt), 2).'.');
    }
}
