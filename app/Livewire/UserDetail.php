<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Role;
use App\Support\ActivityLogger;
use App\Support\AuthorizesPosPermissions;
use App\Modules\Sale\Models\Sale;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserDetail extends Component
{
    use AuthorizesPosPermissions;

    public User $user;

    public string $user_code = '';
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $roleId = '';
    public string $password = '';
    public bool $prohibition_flag = false;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->user_code = (string) $user->user_code;
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->phone = (string) ($user->phone ?? '');
        $this->roleId = (string) ($user->role_id ?? '');
        $this->prohibition_flag = (bool) $user->prohibition_flag;
    }

    public function toggleStatus(): void
    {
        $this->authorizePosPermission('users', 'edit');
        if (! $this->user->prohibition_flag && $this->user->roleRecord?->slug === 'admin') {
            abort_unless(User::hasOtherActiveAdmin($this->user->id), 422, 'Keep at least one active Admin account.');
        }
        $this->user->update([
            'prohibition_flag' => ! $this->user->prohibition_flag,
        ]);

        $this->prohibition_flag = (bool) $this->user->prohibition_flag;
        $this->dispatch('toast', message: $this->prohibition_flag ? 'Employee prohibited.' : 'Employee activated.');
    }

    public function updateUser(): void
    {
        $this->authorizePosPermission('users', 'edit');
        $canAssignRole = auth('web')->user()?->hasPermissionTo('roles', 'edit') ?? false;
        $data = $this->validate([
            'user_code' => ['required', 'string', 'max:100', 'unique:users,user_code,' . $this->user->id],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'roleId' => $canAssignRole ? ['required', 'integer', 'exists:roles,id'] : ['nullable'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updateData = [
            'user_code' => $data['user_code'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
        ];

        if ($canAssignRole) {
            $role = Role::query()->findOrFail($data['roleId']);
            if ($this->user->roleRecord?->slug === 'admin' && $role->id !== $this->user->role_id) {
                abort_unless(User::hasOtherActiveAdmin($this->user->id), 422, 'Keep at least one active Admin account.');
            }
            $updateData['role_id'] = $role->id;
            $updateData['role'] = $role->name;
        }

        $passwordChanged = filled($data['password'] ?? null);
        if ($passwordChanged) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $this->user->update($updateData);
        if ($passwordChanged) {
            ActivityLogger::record('users', 'password_changed', 'Changed password for '.$this->user->name, $this->user);
        }
        $this->password = '';
        $this->dispatch('toast', message: 'Employee updated.');
    }

    public function render()
    {
        $this->authorizePosPermission('users', 'view');
        $canViewSales = auth('web')->user()?->hasPermissionTo('sales', 'view') ?? false;
        $recentSales = $canViewSales
            ? Sale::query()->where('sale_by', $this->user->id)->with('customer')->latest('sale_date')->latest('id')->take(10)->get()
            : collect();
        $salesCount = $canViewSales ? Sale::query()->where('sale_by', $this->user->id)->count() : 0;
        $totalSalesAmount = $canViewSales ? (float) Sale::query()->where('sale_by', $this->user->id)->sum('total_price') : 0.0;

        return view('livewire.user-detail', [
            'recentSales' => $recentSales,
            'salesCount' => $salesCount,
            'totalSalesAmount' => $totalSalesAmount,
            'roleOptions' => Role::query()->orderBy('name')->get(['id', 'name']),
            'canEditUsers' => auth('web')->user()?->hasPermissionTo('users', 'edit') ?? false,
            'canAssignRole' => auth('web')->user()?->hasPermissionTo('roles', 'edit') ?? false,
            'canViewSales' => $canViewSales,
        ]);
    }
}
