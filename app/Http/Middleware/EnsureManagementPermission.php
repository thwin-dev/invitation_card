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
        'customers' => 'customers',
        'exhibitors' => 'exhibitors',
        'events' => 'events',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        $routeName = (string) $request->route()?->getName();

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

        if ($last === 'import' || str_ends_with($routePath, '.import')) {
            return 'import';
        }

        if ($last === 'sample-excel' || str_ends_with($routePath, '.sample-excel')) {
            return 'view';
        }

        if ($request->isMethod('DELETE') || in_array($last, ['destroy', 'delete'], true)) {
            return 'delete';
        }

        if (in_array($last, ['create', 'store'], true) || $request->isMethod('POST')) {
            return 'create';
        }

        if (in_array($last, ['edit', 'update', 'status', 'toggle', 'level'], true) || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            return 'edit';
        }

        if (in_array($last, ['index', 'show'], true) || $request->isMethod('GET')) {
            return 'view';
        }

        return PermissionCatalog::contains($this->moduleForRoute($routeName) ?? '', 'view') ? 'view' : null;
    }
}
