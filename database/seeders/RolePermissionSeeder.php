<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $entries = collect(PermissionCatalog::entries());
        $enabledPermissionKeys = $entries->pluck('key')->flip();

        Permission::query()->get()->each(function (Permission $permission) use ($enabledPermissionKeys): void {
            if (! $enabledPermissionKeys->has($permission->module.'.'.$permission->action)) {
                $permission->delete();
            }
        });

        $permissions = $entries
            ->mapWithKeys(fn (array $entry): array => [
                $entry['key'] => Permission::query()->updateOrCreate(
                    ['module' => $entry['module'], 'action' => $entry['action']],
                ),
            ]);

        $rolePermissions = [
            'admin' => $permissions->keys()->all(),
            'manager' => $permissions->keys()
                ->reject(fn (string $key): bool => str_starts_with($key, 'roles.')
                    || $key === 'users.delete'
                    || str_starts_with($key, 'activity-logs.') && ! in_array($key, ['activity-logs.view', 'activity-logs.export'], true)
                    || in_array($key, ['backups.delete', 'backups.restore'], true))
                ->values()->all(),
            'cashier' => [
                'products.view', 'categories.view', 'subcategories.view', 'units.view',
                'sales.view', 'sales.create',
                'orders.view', 'orders.create',
                'customers.view', 'customers.create', 'customers.edit',
                'inventory.view', 'branches.view',
            ],
            'stock-controller' => [
                'products.view', 'products.create', 'products.edit',
                'categories.view', 'categories.create', 'categories.edit',
                'subcategories.view', 'subcategories.create', 'subcategories.edit',
                'units.view', 'units.create', 'units.edit',
                'unit-relations.view', 'unit-relations.create', 'unit-relations.edit', 'unit-relations.delete',
                'orders.view', 'orders.create', 'orders.edit',
                'purchases.view', 'purchases.create', 'purchases.edit',
                'suppliers.view',
                'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete', 'inventory.import', 'inventory.export',
                'branches.view', 'branches.edit',
            ],
        ];

        $roleNames = [
            'admin' => 'Admin',
            'manager' => 'Manager',
            'cashier' => 'Cashier',
            'stock-controller' => 'Stock Controller',
        ];

        $roles = [];
        foreach ($roleNames as $slug => $name) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_system' => true],
            );
            if ($role->wasRecentlyCreated || $slug === 'admin') {
                $role->permissions()->sync(collect($rolePermissions[$slug])
                    ->map(fn (string $key) => $permissions->get($key)?->id)
                    ->filter()->values()->all());
            } elseif ($slug === 'manager') {
                $role->permissions()->syncWithoutDetaching($permissions
                    ->only(['backups.view', 'backups.create', 'backups.export', 'discounts.view', 'discounts.create', 'discounts.edit'])
                    ->pluck('id')->all());
            }
            $roles[$slug] = $role;
        }

        $legacyRoleMap = [
            'owner' => 'admin',
            'admin' => 'admin',
            'administrator' => 'admin',
            'manager' => 'manager',
            'sales' => 'cashier',
            'sales staff' => 'cashier',
            'cashier' => 'cashier',
            'store keeper' => 'stock-controller',
            'stock controller' => 'stock-controller',
            'stock-controller' => 'stock-controller',
        ];

        User::query()->where(function ($query): void {
            $query->whereNull('role')->orWhereNotIn('role', ['Customer', 'customer']);
        })->chunkById(200, function ($users) use ($legacyRoleMap, $roles): void {
            foreach ($users as $user) {
                $legacyName = trim((string) $user->role);
                $normalized = strtolower($legacyName);

                if ($normalized === 'customer') {
                    continue;
                }

                $slug = $legacyRoleMap[$normalized] ?? null;
                $role = $slug ? $roles[$slug] : $this->legacyRole($legacyName, (int) $user->id, $roles['cashier']);

                $user->forceFill([
                    'role_id' => $role->id,
                    'role' => $role->name,
                ])->save();
            }
        });
    }

    private function legacyRole(string $name, int $userId, Role $fallback): Role
    {
        if ($name === '') {
            return $fallback;
        }

        $slug = Str::slug($name) ?: 'legacy-role';
        $namedRole = Role::query()->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($namedRole) {
            return $namedRole;
        }

        return Role::query()->create([
            'name' => $name,
            'slug' => $slug.'-'.$userId,
            'is_system' => false,
        ]);
    }
}
