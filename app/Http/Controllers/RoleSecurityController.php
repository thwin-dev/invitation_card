<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Support\ActivityLogger;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleSecurityController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::query()->withCount('users')->orderBy('name')->get();
        $selectedRole = $request->integer('role_id')
            ? Role::query()->withCount('users')->with('permissions')->find($request->integer('role_id'))
            : null;
        $selectedRole ??= $roles->first(fn (Role $role): bool => $role->slug !== 'admin') ?? $roles->first();
        $enabledPermissionKeys = collect(PermissionCatalog::entries())->pluck('key')->flip();
        $permissions = Permission::query()->orderBy('module')->orderBy('action')->get()
            ->filter(fn (Permission $permission): bool => $enabledPermissionKeys->has($permission->module.'.'.$permission->action))
            ->values();

        return view('management.security.roles', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'permissionsByModule' => $permissions->groupBy('module'),
            'moduleLabels' => PermissionCatalog::modules(),
            'actions' => PermissionCatalog::ACTIONS,
            'canCreateRoles' => $request->user('web')?->hasPermissionTo('roles', 'create') ?? false,
            'canEditRoles' => $request->user('web')?->hasPermissionTo('roles', 'edit') ?? false,
            'canDeleteRoles' => $request->user('web')?->hasPermissionTo('roles', 'delete') ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
        ]);

        $baseSlug = Str::slug($data['name']) ?: 'role';
        $slug = $baseSlug;
        $suffix = 2;
        while (Role::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $role = Role::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'is_system' => false,
        ]);

        return to_route('management.security.roles.index', ['role_id' => $role->id])
            ->with('status', 'Role created. Select its permissions to finish setup.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->slug === 'admin', 403, 'The Admin role must keep all permissions.');

        $enabledPermissionIds = Permission::query()->get()
            ->filter(fn (Permission $permission): bool => PermissionCatalog::contains($permission->module, $permission->action))
            ->pluck('id')
            ->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'distinct', Rule::in($enabledPermissionIds)],
        ]);

        $before = $role->permissions()->orderBy('permissions.module')->orderBy('permissions.action')
            ->get(['permissions.module', 'permissions.action'])
            ->map(fn (Permission $permission): string => $permission->module.'.'.$permission->action)
            ->all();

        $role->update(['name' => $data['name']]);
        $role->permissions()->sync($data['permissions'] ?? []);

        $after = $role->permissions()->orderBy('permissions.module')->orderBy('permissions.action')
            ->get(['permissions.module', 'permissions.action'])
            ->map(fn (Permission $permission): string => $permission->module.'.'.$permission->action)
            ->all();
        if ($before !== $after) {
            ActivityLogger::record('roles', 'permissions_updated', 'Updated permissions for role '.$role->name, $role, [
                'permissions' => ['from' => $before, 'to' => $after],
            ]);
        }

        return to_route('management.security.roles.index', ['role_id' => $role->id])
            ->with('status', 'Role permissions updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403, 'Built-in roles cannot be deleted.');
        abort_if($role->users()->exists(), 422, 'Reassign this role’s employees before deleting it.');

        $role->delete();

        return to_route('management.security.roles.index')->with('status', 'Role deleted.');
    }
}
