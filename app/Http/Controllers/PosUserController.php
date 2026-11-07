<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PosUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->where(function ($query): void {
                $query->whereNull('role')->orWhereNotIn('role', ['Customer', 'customer']);
            })
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('user_code', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($request->string('role')->trim()->value(), fn ($query, string $role) => $query->where('role', $role))
            ->latest()->paginate(20)->withQueryString();
        $roles = User::query()->whereNotNull('role')->distinct()->orderBy('role')->pluck('role');
        return view('management.users.index', compact('users', 'roles'));
    }

    public function show(User $user): View
    {
        return view('management.users.show', compact('user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $canAssignRole = $request->user('web')?->hasPermissionTo('roles', 'edit') ?? false;
        $data = $request->validate([
            'user_code' => ['required', 'string', 'max:100', 'unique:users,user_code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role_id' => $canAssignRole ? ['required', 'integer', 'exists:roles,id'] : ['nullable'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $role = $canAssignRole
            ? Role::query()->findOrFail($data['role_id'])
            : Role::query()->where('slug', 'cashier')->firstOrFail();
        unset($data['role_id']);
        User::query()->create([
            ...$data,
            'role_id' => $role->id,
            'role' => $role->name,
            'password' => Hash::make($data['password']),
            'prohibition_flag' => false,
            'photo_path' => '',
        ]);
        return to_route('management.users.index')->with('status', 'Employee created.');
    }

    public function toggle(User $user): RedirectResponse
    {
        if (! $user->prohibition_flag && $user->roleRecord?->slug === 'admin') {
            abort_unless(User::hasOtherActiveAdmin($user->id), 422, 'Keep at least one active Admin account.');
        }
        $user->update(['prohibition_flag' => ! $user->prohibition_flag]);
        return back()->with('status', $user->prohibition_flag ? 'Employee prohibited.' : 'Employee activated.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $canAssignRole = $request->user('web')?->hasPermissionTo('roles', 'edit') ?? false;
        $data = $request->validate([
            'user_code' => ['required', 'string', 'max:100', 'unique:users,user_code,'.$user->id],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'role_id' => $canAssignRole ? ['required', 'integer', 'exists:roles,id'] : ['nullable'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        if ($canAssignRole) {
            $role = Role::query()->findOrFail($data['role_id']);
            if ($user->roleRecord?->slug === 'admin' && $role->id !== $user->role_id) {
                abort_unless(User::hasOtherActiveAdmin($user->id), 422, 'Keep at least one active Admin account.');
            }
            $data['role_id'] = $role->id;
            $data['role'] = $role->name;
        } else {
            unset($data['role_id']);
        }
        $passwordChanged = filled($data['password'] ?? null);
        if (! $passwordChanged) { unset($data['password']); } else { $data['password'] = Hash::make($data['password']); }
        $user->update($data);
        if ($passwordChanged) {
            ActivityLogger::record('users', 'password_changed', 'Changed password for '.$user->name, $user);
        }
        return back()->with('status', 'Employee updated.');
    }
}
