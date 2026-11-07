@extends('layouts.management')

@section('title', 'Roles and Permissions')

@section('content')
<div class="mb-7 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Roles and permissions</h1>
        <p class="mt-1 text-sm text-gray-500">Choose what each role can view, create, edit, delete, import, and export.</p>
    </div>
</div>

<div class="grid items-start gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
    <aside class="space-y-5">
        <section class="pos-panel overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Roles</h2>
                <p class="mt-1 text-xs text-gray-500">{{ $roles->count() }} roles · {{ $roles->sum('users_count') }} employees</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($roles as $role)
                    <a href="{{ route('management.security.roles.index', ['role_id' => $role->id]) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-gray-50 {{ $selectedRole?->is($role) ? 'bg-brand-50/70' : '' }}">
                        <span>
                            <span class="block text-sm font-medium text-gray-800">{{ $role->name }}</span>
                            <span class="mt-0.5 block text-xs text-gray-500">{{ $role->users_count }} employees</span>
                        </span>
                        @if($role->slug === 'admin')
                            <span class="rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-semibold uppercase text-indigo-600">Full access</span>
                        @elseif($role->is_system)
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-[10px] font-semibold uppercase text-gray-500">Built in</span>
                        @endif
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-gray-500">No roles yet. Run the permission seeder to create the standard roles.</p>
                @endforelse
            </div>
        </section>

        @if($canCreateRoles)
        <section class="pos-panel p-5">
            <h2 class="font-semibold text-gray-900">Create a role</h2>
            <p class="mt-1 text-xs text-gray-500">New roles start with no access. Assign permissions after creating the role.</p>
            <form method="post" action="{{ route('management.security.roles.store') }}" class="mt-4 space-y-3">
                @csrf
                <label for="new-role-name" class="block text-xs font-medium text-gray-700">Role name</label>
                <input id="new-role-name" name="name" value="{{ old('name') }}" required maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15" placeholder="e.g. Branch supervisor">
                @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button class="pos-button w-full justify-center">Create role</button>
            </form>
        </section>
        @endif
    </aside>

    <section class="pos-panel overflow-hidden">
        @if($selectedRole)
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $selectedRole->name }}</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($selectedRole->slug === 'admin')
                            Admin has all permissions so at least one account can manage access.
                        @else
                            Select the actions employees with this role can use.
                        @endif
                    </p>
                </div>
                @if($canDeleteRoles && ! $selectedRole->is_system && $selectedRole->users_count === 0)
                    <form method="post" action="{{ route('management.security.roles.destroy', $selectedRole) }}" onsubmit="return confirm('Delete this role?')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Delete role</button>
                    </form>
                @endif
            </div>

            @if($selectedRole->slug === 'admin')
                <div class="p-5 sm:p-6">
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                        The Admin role is protected and always has every permission.
                    </div>
                </div>
            @elseif($canEditRoles)
                <form method="post" action="{{ route('management.security.roles.update', $selectedRole) }}">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-wrap items-end gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
                        <label for="role-name" class="min-w-60 flex-1 text-xs font-medium text-gray-700">
                            Role name
                            <input id="role-name" name="name" value="{{ old('name', $selectedRole->name) }}" required maxlength="100" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                        </label>
                        <button type="submit" class="pos-button">Save permissions</button>
                    </div>
                    @error('name') <p class="px-6 pt-3 text-xs text-red-600">{{ $message }}</p> @enderror

                    @php($assignedPermissionIds = $selectedRole->permissions->pluck('id')->all())
                    <div class="divide-y divide-gray-100">
                        @foreach($moduleLabels as $module => $moduleLabel)
                            @php($modulePermissions = $permissionsByModule->get($module, collect()))
                            <fieldset class="px-5 py-4 sm:px-6">
                                <legend class="mb-3 text-sm font-semibold text-gray-800">{{ $moduleLabel }}</legend>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                                    @foreach($actions as $action)
                                        @php($permission = $modulePermissions->firstWhere('action', $action))
                                        @if($permission)
                                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $assignedPermissionIds, true)) class="h-4 w-4 rounded border-gray-300 text-[#465fff] focus:ring-[#465fff]">
                                                {{ ucfirst($action) }}
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                    <div class="flex justify-end border-t border-gray-100 px-5 py-4 sm:px-6">
                        <button type="submit" class="pos-button">Save permissions</button>
                    </div>
                </form>
            @else
                <div class="p-5 sm:p-6">
                    <p class="text-sm text-gray-600">You can view this role. Editing permissions requires the roles edit permission.</p>
                    <div class="mt-5 divide-y divide-gray-100 rounded-xl border border-gray-200">
                        @foreach($moduleLabels as $module => $moduleLabel)
                            @php($modulePermissions = $permissionsByModule->get($module, collect()))
                            <div class="px-5 py-4">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800">{{ $moduleLabel }}</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($modulePermissions as $permission)
                                        @if($selectedRole->permissions->contains('id', $permission->id))
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ ucfirst($permission->action) }}</span>
                                        @endif
                                    @endforeach
                                    @if($modulePermissions->every(fn ($permission) => ! $selectedRole->permissions->contains('id', $permission->id)))
                                        <span class="text-xs text-gray-400">No permissions</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="p-8 text-center">
                <h2 class="font-semibold text-gray-900">No roles are available</h2>
                <p class="mt-2 text-sm text-gray-500">Run <code>php artisan db:seed --class=RolePermissionSeeder</code> after applying migrations.</p>
            </div>
        @endif
    </section>
</div>
@endsection
