<?php

namespace App\Http\Middleware;

use App\Support\PermissionCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManagementPermission
{
    /** @var array<string, string> */
    private const ROUTE_MODULES = [
        'users' => 'users',
        'security.roles' => 'roles',
        'activity-logs' => 'activity-logs',
        'backups' => 'backups',
        'products' => 'products',
        'brands' => 'products',
        'product-categories' => 'categories',
        'product-subcategories' => 'subcategories',
        'product-units' => 'units',
        'product-unit-relations' => 'unit-relations',
        'discounts' => 'discounts',
        'sales' => 'sales',
        'orders' => 'orders',
        'customers' => 'customers',
        'customer-credits' => 'customers',
        'customer-payments' => 'customers',
        'purchases' => 'purchases',
        'suppliers' => 'suppliers',
        'stock' => 'inventory',
        'inventory' => 'inventory',
        'inventory-operations' => 'inventory',
        'branches' => 'branches',
        'expenses' => 'expenses',
        'reports' => 'reports',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        $routeName = (string) $request->route()?->getName();

        if ($routeName === 'management.dashboard') {
            abort_unless($user?->hasPermissionTo('dashboard', 'view'), 403);

            return $next($request);
        }

        $module = $this->moduleForRoute($routeName);
        $action = $this->actionForRoute($request, $routeName);

        abort_unless($module !== null && $action !== null && $user?->hasPermissionTo($module, $action), 403);

        return $next($request);
    }

    private function moduleForRoute(string $routeName): ?string
    {
        if (! str_starts_with($routeName, 'management.')) {
            return null;
        }

        $routePath = substr($routeName, strlen('management.'));
        if (str_starts_with($routePath, 'security.roles.')) {
            return 'roles';
        }

        $segment = explode('.', $routePath, 2)[0];

        return self::ROUTE_MODULES[$segment] ?? null;
    }

    private function actionForRoute(Request $request, string $routeName): ?string
    {
        $routePath = str_starts_with($routeName, 'management.')
            ? substr($routeName, strlen('management.'))
            : '';
        $segments = explode('.', $routePath);
        $last = end($segments) ?: '';

        if ($last === 'export' || $request->isMethod('GET') && str_ends_with($routePath, '.export')) {
            return 'export';
        }

        if ($last === 'download') {
            return 'export';
        }

        if ($last === 'import' || str_ends_with($routePath, '.import')) {
            return 'import';
        }

        if (in_array($last, ['restore', 'restore-backup'], true)) {
            return 'restore';
        }

        if ($request->isMethod('DELETE') || in_array($last, ['destroy', 'delete', 'photo.delete'], true)) {
            return 'delete';
        }

        if (in_array($last, ['create', 'store', 'sync-offline'], true)) {
            return 'create';
        }

        if (in_array($last, ['update', 'status', 'toggle', 'cancel', 'convert', 'level'], true)) {
            return 'edit';
        }

        if ($last === 'payments' && $request->isMethod('POST')) {
            return 'create';
        }

        if ($request->isMethod('POST')) {
            return 'create';
        }

        if (in_array($last, ['edit', 'update'], true)) {
            return 'edit';
        }

        if ($request->isMethod('PUT') || $request->isMethod('PATCH')) {
            return 'edit';
        }

        if (in_array($last, ['index', 'show', 'print', 'photo', 'barcode', 'financial', 'branches', 'movements', 'operations', 'statement', 'receipt', 'offline-catalog', 'prices'], true)) {
            return 'view';
        }

        if ($request->isMethod('GET')) {
            return 'view';
        }

        return PermissionCatalog::contains($this->moduleForRoute($routeName) ?? '', 'view') ? 'view' : null;
    }
}
