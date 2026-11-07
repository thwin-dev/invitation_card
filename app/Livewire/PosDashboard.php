<?php

namespace App\Livewire;

use App\Models\Backup;
use App\Modules\Customer\Models\CustomerCreditRecord;
use App\Modules\Customer\Models\CustomerPayment;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Expense\Models\PosExpense;
use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Order\Models\PosOrder;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Sale\Models\Sale;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;

class PosDashboard extends Component
{
    use AuthorizesPosPermissions;

    public string $fromDate = '';

    public string $toDate = '';

    public string $branchId = '';

    public string $activePreset = '7days';

    public function mount(): void
    {
        $today = now()->toDateString();
        $this->fromDate = now()->subDays(6)->toDateString();
        $this->toDate = $today;

        $branch = InventoryBranch::query()->forSales()->find(session('pos.branch_id'))
            ?: InventoryBranch::query()->forSales()->where('is_main', true)->first()
            ?: InventoryBranch::query()->forSales()->orderBy('name')->first();

        $this->branchId = (string) ($branch?->id ?? '');
    }

    public function updatedFromDate(): void
    {
        $this->activePreset = 'custom';
        $this->resetErrorBag('fromDate');
        $this->resetErrorBag('toDate');

        $this->validateOnly('fromDate', ['fromDate' => ['required', 'date_format:Y-m-d']]);
        $this->validateDateOrder('fromDate');
    }

    public function updatedToDate(): void
    {
        $this->activePreset = 'custom';
        $this->resetErrorBag('fromDate');
        $this->resetErrorBag('toDate');

        $this->validateOnly('toDate', ['toDate' => ['required', 'date_format:Y-m-d']]);
        $this->validateDateOrder('toDate');
    }

    public function updatedBranchId(): void
    {
        if ($this->branchId !== '') {
            InventoryBranch::query()->forSales()->findOrFail($this->branchId);
        }
    }

    public function setPeriod(string $period): void
    {
        $today = now();

        [$this->fromDate, $this->toDate, $this->activePreset] = match ($period) {
            'today' => [$today->toDateString(), $today->toDateString(), 'today'],
            'month' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString(), 'month'],
            'last_month' => [
                $today->copy()->subMonth()->startOfMonth()->toDateString(),
                $today->copy()->subMonth()->endOfMonth()->toDateString(),
                'last_month',
            ],
            default => [$today->copy()->subDays(6)->toDateString(), $today->toDateString(), '7days'],
        };

        $this->resetErrorBag('fromDate');
        $this->resetErrorBag('toDate');
    }

    public function render()
    {
        $this->authorizePosPermission('dashboard', 'view');
        $canViewBackups = auth('web')->user()?->hasPermissionTo('backups', 'view') ?? false;
        $latestBackup = $canViewBackups ? Backup::query()->where('status', 'success')->latest('id')->first() : null;
        $latestBackupAttempt = $canViewBackups ? Backup::query()->latest('id')->first() : null;
        $nextBackupAt = now()->setTimeFromTimeString((string) config('backup.schedule_time', '02:00'));
        if ($nextBackupAt->lte(now())) {
            $nextBackupAt->addDay();
        }

        [$from, $to] = $this->resolvedDateRange();
        $branches = InventoryBranch::query()->forSales()->orderBy('name')->get();
        $branch = $branches->firstWhere('id', (int) $this->branchId);

        if (! $branch && $branches->isNotEmpty()) {
            $branch = $branches->first();
            $this->branchId = (string) $branch->id;
        }

        $salesQuery = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->when($branch, fn (Builder $query) => $query->where('branch_id', $branch->id));

        $salesMetrics = (clone $salesQuery)
            ->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(total_price), 0) as total_sales, COALESCE(SUM(total_quantity), 0) as quantity_sold, COALESCE(SUM(paid_amount), 0) as paid_total, COALESCE(SUM(credit_amount), 0) as credit_total, COALESCE(AVG(total_price), 0) as average_sale')
            ->first();

        $trendFrom = $from->copy();
        if ($from->diffInDays($to) > 30) {
            $trendFrom = $to->copy()->subDays(30);
        }

        $dailySales = (clone $salesQuery)
            ->whereBetween('sale_date', [$trendFrom->toDateString(), $to->toDateString()])
            ->selectRaw('DATE(sale_date) as sales_day, SUM(total_price) as total_sales')
            ->groupByRaw('DATE(sale_date)')
            ->orderByRaw('DATE(sale_date)')
            ->get()
            ->keyBy('sales_day');

        $salesTrend = collect();
        for ($day = $trendFrom->copy(); $day->lte($to); $day->addDay()) {
            $date = $day->toDateString();
            $salesTrend->push((object) [
                'sales_day' => $date,
                'total_sales' => (float) ($dailySales->get($date)->total_sales ?? 0),
            ]);
        }

        $maxTrendValue = max(1, (float) $salesTrend->max('total_sales'));
        $sales = (clone $salesQuery)->with('customer')->latest('sale_date')->limit(5)->get();

        // Purchases and expenses do not have branch columns, so those totals cover all shops.
        $purchaseTotal = (float) Purchase::query()
            ->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total_price');
        $expenseTotal = (float) PosExpense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->where('is_profit', false)
            ->sum('amount');
        $customerPayments = (float) CustomerPayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $stockValue = $this->stockValue($branch);
        $stockUnitQuery = $this->stockUnitQuery($branch);
        $stockQuantityExpression = $branch && ! $branch->is_main
            ? 'COALESCE(branch_stock.available_quantity, 0)'
            : 'product_units.current_quantity';
        $lowStockExpression = $stockQuantityExpression.' <= product_units.reorder_quantity';
        $outOfStockExpression = $stockQuantityExpression.' < 1';

        $lowStockCount = (clone $stockUnitQuery)->whereRaw($lowStockExpression)->count('product_units.id');
        $outOfStockCount = (clone $stockUnitQuery)->whereRaw($outOfStockExpression)->count('product_units.id');
        $lowStockUnits = (clone $stockUnitQuery)
            ->whereRaw($lowStockExpression)
            ->orderByRaw($stockQuantityExpression.' asc')
            ->limit(6)
            ->get();

        $lotQuery = InventoryLot::query()
            ->where('quantity', '>', 0)
            ->whereNotNull('expires_at')
            ->when($branch, fn (Builder $query) => $query->where('branch_id', $branch->id));
        $expiredLotCount = (clone $lotQuery)->whereDate('expires_at', '<', today())->count();
        $expiringLotCount = (clone $lotQuery)->whereBetween('expires_at', [today(), today()->addDays(30)])->count();
        $expiryLots = (clone $lotQuery)
            ->with(['unit.product', 'branch'])
            ->whereDate('expires_at', '<=', today()->addDays(30))
            ->orderBy('expires_at')
            ->limit(6)
            ->get();

        $creditQuery = CustomerCreditRecord::query()
            ->whereNull('repayment_date')
            ->where('amount', '>', 0)
            ->whereHas('sale', fn (Builder $query) => $query
                ->where('status', 'completed')
                ->when($branch, fn (Builder $query) => $query->where('branch_id', $branch->id)));
        $receivableTotals = (clone $creditQuery)
            ->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(DISTINCT customer_id) as customer_count')
            ->first();
        $customerDebts = PosCustomer::query()
            ->withSum(['credits as outstanding_debt' => fn (Builder $query) => $query
                ->whereNull('repayment_date')
                ->where('amount', '>', 0)
                ->whereHas('sale', fn (Builder $query) => $query
                    ->where('status', 'completed')
                    ->when($branch, fn (Builder $query) => $query->where('branch_id', $branch->id)))], 'amount')
            ->having('outstanding_debt', '>', 0)
            ->orderByDesc('outstanding_debt')
            ->limit(4)
            ->get();

        $payablesQuery = Purchase::query()->where('payable_amount', '>', 0);
        $payableTotal = (float) (clone $payablesQuery)->sum('payable_amount');
        $overduePayableTotal = (float) (clone $payablesQuery)
            ->whereNotNull('payment_due_date')
            ->whereDate('payment_due_date', '<', today())
            ->sum('payable_amount');
        $payables = (clone $payablesQuery)
            ->with('supplier')
            ->orderByRaw('payment_due_date IS NULL')
            ->orderBy('payment_due_date')
            ->limit(4)
            ->get();

        $branchAggregates = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('branch_id, COUNT(*) as transaction_count, COALESCE(SUM(total_price), 0) as total_sales, COALESCE(SUM(paid_amount), 0) as paid_total, COALESCE(SUM(credit_amount), 0) as credit_total')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $branchPerformance = $branches->map(function (InventoryBranch $shop) use ($branchAggregates): object {
            $totals = $branchAggregates->get($shop->id);

            return (object) [
                'branch' => $shop,
                'transaction_count' => (int) ($totals->transaction_count ?? 0),
                'total_sales' => (float) ($totals->total_sales ?? 0),
                'paid_total' => (float) ($totals->paid_total ?? 0),
                'credit_total' => (float) ($totals->credit_total ?? 0),
            ];
        });

        $openOrderQuery = PosOrder::query()
            ->whereIn('status', ['pending', 'confirmed', 'changed']);
        $openOrderCount = (clone $openOrderQuery)->count();
        $openOrders = (clone $openOrderQuery)->with('customer')->latest('order_date')->limit(4)->get();
        $activityOrders = PosOrder::query()
            ->with('customer')
            ->whereIn('status', ['pending', 'confirmed', 'changed'])
            ->whereBetween('order_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('order_date')
            ->limit(3)
            ->get();

        $offlineSyncedCount = (clone $salesQuery)->where('is_offline_sync', true)->count();

        $activities = collect();

        foreach ($sales as $sale) {
            $activities->push($this->activity(
                'sale-'.$sale->id,
                'Sale '.$sale->voucher_code,
                $sale->customer?->name ?: 'Walk-in customer',
                $sale->sale_date,
                (float) $sale->total_price,
                'receipt',
                route('management.sales.show', $sale)
            ));
        }

        $purchases = Purchase::query()
            ->with('supplier')
            ->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->latest('purchase_date')
            ->limit(4)
            ->get();

        foreach ($purchases as $purchase) {
            $activities->push($this->activity(
                'purchase-'.$purchase->id,
                'Purchase #'.$purchase->id,
                $purchase->supplier?->name ?: $purchase->supplier_name,
                $purchase->purchase_date,
                (float) $purchase->total_price,
                'truck',
                route('management.purchases.show', $purchase)
            ));
        }

        $expenses = PosExpense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->where('is_profit', false)
            ->latest('expense_date')
            ->limit(4)
            ->get();

        foreach ($expenses as $expense) {
            $activities->push($this->activity(
                'expense-'.$expense->id,
                $expense->title,
                'Expense · '.$expense->type,
                $expense->expense_date,
                (float) $expense->amount,
                'receipt-text',
                route('management.expenses.index')
            ));
        }

        $movements = StockMovement::query()
            ->with(['unit.product', 'branch'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($branch, fn (Builder $query) => $query->where('branch_id', $branch->id))
            ->latest('created_at')
            ->limit(4)
            ->get();

        foreach ($movements as $movement) {
            $activities->push($this->activity(
                'movement-'.$movement->id,
                ($movement->direction === 'IN' ? 'Stock in · ' : 'Stock out · ').($movement->unit?->product?->name ?: $movement->unit?->name ?: 'Inventory item'),
                number_format((int) $movement->quantity).' '.($movement->unit?->name ?: 'units').' · '.str($movement->reference_type)->replace('_', ' ')->title(),
                $movement->created_at,
                null,
                'boxes',
                route('management.stock.movements')
            ));
        }

        foreach ($activityOrders as $order) {
            $activities->push($this->activity(
                'order-'.$order->id,
                'Order '.$order->order_number,
                ($order->customer?->name ?: 'Customer').' · '.str($order->status)->title(),
                $order->order_date,
                (float) $order->estimated_price,
                'clipboard-list',
                route('management.orders.show', $order)
            ));
        }

        $recentActivities = $activities
            ->sortByDesc(fn (array $activity) => $activity['sort_at']?->timestamp ?? 0)
            ->take(8)
            ->values();

        return view('livewire.pos-dashboard', [
            'branches' => $branches,
            'branch' => $branch,
            'currencyCode' => $branch?->currency_code ?: 'MMK',
            'from' => $from,
            'to' => $to,
            'salesMetrics' => $salesMetrics,
            'salesTrend' => $salesTrend,
            'maxTrendValue' => $maxTrendValue,
            'trendCaption' => $trendFrom->gt($from)
                ? 'Daily completed sales · latest 31 days in the selected period'
                : 'Daily completed sales · '.($branch?->name ?: 'all shops'),
            'purchaseTotal' => $purchaseTotal,
            'expenseTotal' => $expenseTotal,
            'customerPayments' => $customerPayments,
            'stockValue' => $stockValue,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'lowStockUnits' => $lowStockUnits,
            'expiredLotCount' => $expiredLotCount,
            'expiringLotCount' => $expiringLotCount,
            'expiryLots' => $expiryLots,
            'receivableTotal' => (float) ($receivableTotals->total ?? 0),
            'receivableCustomerCount' => (int) ($receivableTotals->customer_count ?? 0),
            'customerDebts' => $customerDebts,
            'payableTotal' => $payableTotal,
            'overduePayableTotal' => $overduePayableTotal,
            'payables' => $payables,
            'branchPerformance' => $branchPerformance,
            'openOrderCount' => $openOrderCount,
            'openOrders' => $openOrders,
            'offlineSyncedCount' => $offlineSyncedCount,
            'recentActivities' => $recentActivities,
            'canViewBackups' => $canViewBackups,
            'latestBackup' => $latestBackup,
            'latestBackupAttempt' => $latestBackupAttempt,
            'nextBackupAt' => $nextBackupAt,
            'backupScheduleEnabled' => (bool) config('backup.enabled', true),
        ]);
    }

    private function validateDateOrder(string $changedField): void
    {
        if ($this->fromDate !== '' && $this->toDate !== '' && $this->fromDate > $this->toDate) {
            if ($changedField === 'fromDate') {
                $this->toDate = $this->fromDate;
            } else {
                $this->fromDate = $this->toDate;
            }
        }
    }

    /** @return array{Carbon, Carbon} */
    private function resolvedDateRange(): array
    {
        try {
            $from = Carbon::createFromFormat('!Y-m-d', $this->fromDate);
            $to = Carbon::createFromFormat('!Y-m-d', $this->toDate);

            if (! $from || ! $to || $from->format('Y-m-d') !== $this->fromDate || $to->format('Y-m-d') !== $this->toDate || $from->gt($to)) {
                throw new \InvalidArgumentException('Invalid dashboard date range.');
            }

            return [$from->startOfDay(), $to->startOfDay()];
        } catch (\Throwable) {
            return [now()->startOfDay(), now()->startOfDay()];
        }
    }

    private function stockValue(?InventoryBranch $branch): float
    {
        if (! $branch || $branch->is_main) {
            return (float) (ProductUnit::query()
                ->whereHas('product')
                ->selectRaw('COALESCE(SUM(current_quantity * purchase_price), 0) as value')
                ->value('value') ?? 0);
        }

        return (float) (InventoryLot::query()
            ->join('product_units', 'product_units.id', '=', 'inventory_lots.product_unit_id')
            ->where('inventory_lots.branch_id', $branch->id)
            ->where('inventory_lots.quantity', '>', 0)
            ->whereNull('product_units.deleted_at')
            ->selectRaw('COALESCE(SUM(inventory_lots.quantity * product_units.purchase_price), 0) as value')
            ->value('value') ?? 0);
    }

    private function stockUnitQuery(?InventoryBranch $branch): Builder
    {
        $query = ProductUnit::query()->with('product')->whereHas('product');

        if (! $branch || $branch->is_main) {
            return $query->select('product_units.*')->selectRaw('product_units.current_quantity as dashboard_quantity');
        }

        $branchStock = InventoryLot::query()
            ->select('product_unit_id')
            ->selectRaw('SUM(quantity) as available_quantity')
            ->where('branch_id', $branch->id)
            ->groupBy('product_unit_id');

        return $query
            ->leftJoinSub($branchStock, 'branch_stock', 'branch_stock.product_unit_id', '=', 'product_units.id')
            ->select('product_units.*')
            ->selectRaw('COALESCE(branch_stock.available_quantity, 0) as dashboard_quantity');
    }

    /** @return array{key:string,label:string,detail:string,sort_at:?Carbon,date:string,amount:?float,icon:string,url:string} */
    private function activity(string $key, string $label, string $detail, mixed $date, ?float $amount, string $icon, string $url): array
    {
        $at = $date ? Carbon::parse($date) : null;

        return [
            'key' => $key,
            'label' => $label,
            'detail' => $detail,
            'sort_at' => $at,
            'date' => $at?->format('d M Y'),
            'amount' => $amount,
            'icon' => $icon,
            'url' => $url,
        ];
    }
}
