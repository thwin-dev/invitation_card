<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Modules\Customer\Models\CustomerCreditRecord;
use App\Modules\Customer\Models\CustomerPayment;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Expense\Models\PosExpense;
use App\Modules\Inventory\Models\BranchUnitPrice;
use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Models\ProductUnitConversion;
use App\Modules\Inventory\Models\ProductUnitRelation;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Order\Models\PosOrder;
use App\Modules\Order\Models\PosOrderItem;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductBrand;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\Purchase\Models\PosSupplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Purchase\Models\SupplierPayment;
use App\Modules\Sale\Models\DiscountRule;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogger
{
    public const ACTIONS = [
        'created', 'updated', 'deleted', 'restored', 'permissions_updated',
        'password_changed', 'login', 'logout', 'exported',
    ];

    /** @var array<class-string<Model>, string> */
    private const MODEL_MODULES = [
        User::class => 'users',
        Role::class => 'roles',
        Permission::class => 'roles',
        Backup::class => 'backups',
        Product::class => 'products',
        ProductBrand::class => 'products',
        ProductCategory::class => 'categories',
        ProductSubcategory::class => 'subcategories',
        ProductUnit::class => 'units',
        ProductUnitRelation::class => 'unit-relations',
        ProductUnitConversion::class => 'inventory',
        PosCustomer::class => 'customers',
        CustomerCreditRecord::class => 'customers',
        CustomerPayment::class => 'customers',
        PosSupplier::class => 'suppliers',
        SupplierPayment::class => 'suppliers',
        Purchase::class => 'purchases',
        PurchaseItem::class => 'purchases',
        DiscountRule::class => 'discounts',
        Sale::class => 'sales',
        SaleItem::class => 'sales',
        PosOrder::class => 'orders',
        PosOrderItem::class => 'orders',
        PosExpense::class => 'expenses',
        InventoryBranch::class => 'branches',
        BranchUnitPrice::class => 'branches',
        InventoryLot::class => 'inventory',
        StockMovement::class => 'inventory',
    ];

    /** @return list<class-string<Model>> */
    public static function auditableModels(): array
    {
        return array_keys(self::MODEL_MODULES);
    }

    public static function recordModelChange(Model $model, string $action, array $before, array $after): void
    {
        $module = self::MODEL_MODULES[$model::class] ?? null;
        if ($module === null) {
            return;
        }

        $changes = self::makeChanges($before, $after);
        if ($changes === []) {
            return;
        }

        $subject = self::subjectLabel($model);
        $verb = match ($action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            default => Str::headline($action),
        };

        self::record($module, $action, $verb.' '.$subject, $model, $changes);
    }

    public static function record(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        array $changes = [],
    ): void {
        $actor = Auth::guard('web')->user();
        if ($actor === null) {
            return;
        }

        $request = app()->bound('request') ? app('request') : null;
        $route = $request?->route();

        ActivityLog::query()->create([
            'actor_id' => $actor->getAuthIdentifier(),
            'actor_name' => $actor->name,
            'actor_user_code' => $actor->user_code,
            'module' => $module,
            'action' => $action,
            'description' => Str::limit($description, 255, ''),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey() === null ? null : (string) $subject->getKey(),
            'changes' => self::sanitize($changes),
            'route_name' => $route?->getName(),
            'http_method' => $request?->method(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? Str::limit($request->userAgent(), 1000, '') : null,
            'created_at' => now(),
        ]);
    }

    /** @return array<string, array{from: mixed, to: mixed}> */
    public static function makeChanges(array $before, array $after): array
    {
        $changes = [];
        $keys = array_unique([...array_keys($before), ...array_keys($after)]);

        foreach ($keys as $key) {
            if (in_array($key, ['created_at', 'updated_at', 'last_login_at'], true) || self::isSensitiveField((string) $key)) {
                continue;
            }

            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            if ($old === $new) {
                continue;
            }

            if (str_contains(strtolower((string) $key), 'photo_path')) {
                $old = $old === null ? null : '[hidden]';
                $new = $new === null ? null : '[hidden]';
            }

            $changes[$key] = ['from' => $old, 'to' => $new];
        }

        return self::sanitize($changes);
    }

    private static function subjectLabel(Model $model): string
    {
        $attributes = $model->getAttributes();
        $name = null;

        foreach (['name', 'title', 'voucher_code', 'order_number', 'user_code', 'code', 'company_name'] as $key) {
            if (filled($attributes[$key] ?? null)) {
                $name = (string) $attributes[$key];
                break;
            }
        }

        $label = Str::headline(class_basename($model));

        return $name === null
            ? $label.' #'.$model->getKey()
            : $label.' “'.Str::limit($name, 80, '…').'”';
    }

    private static function isSensitiveField(string $field): bool
    {
        return preg_match('/password|remember|token|secret|credential|authorization|api[_-]?key/i', $field) === 1;
    }

    private static function sanitize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 5) {
            return '[nested value omitted]';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value)) {
            return Str::limit($value, 1000, '…');
        }

        if (is_array($value)) {
            $clean = [];
            foreach (array_slice($value, 0, 100, true) as $key => $item) {
                if (is_string($key) && self::isSensitiveField($key)) {
                    continue;
                }
                $clean[$key] = self::sanitize($item, $depth + 1);
            }

            return $clean;
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? Str::limit((string) $value, 1000, '…')
                : '[value omitted]';
        }

        return is_resource($value) ? '[value omitted]' : $value;
    }
}
