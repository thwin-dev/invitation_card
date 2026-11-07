<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Role;
use App\Support\ActivityLogger;
use App\Support\AuthorizesPosPermissions;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;
    use AuthorizesPosPermissions;

    public string $search = '';
    public string $role = '';

    public bool $createOpen = false;
    public string $createCode = '';
    public string $createName = '';
    public string $createEmail = '';
    public string $createRoleId = '';
    public string $createPassword = '';

    public ?int $editingId = null;
    public string $editCode = '';
    public string $editName = '';
    public string $editEmail = '';
    public string $editRoleId = '';
    public string $editPassword = '';

    public function mount(): void
    {
        $this->search = (string) request('search', '');
        $this->role = (string) request('role', '');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    #[On('open-user-create')]
    public function openCreate(): void
    {
        $this->authorizePosPermission('users', 'create');
        $this->resetValidation();
        $this->resetCreateForm();
        $this->createOpen = true;
    }

    public function closeCreate(): void
    {
        $this->createOpen = false;
    }

    public function createUser(): void
    {
        $this->authorizePosPermission('users', 'create');
        $canAssignRole = auth('web')->user()?->hasPermissionTo('roles', 'edit') ?? false;
        $data = $this->validate([
            'createCode' => ['required', 'string', 'max:100', 'unique:users,user_code'],
            'createName' => ['required', 'string', 'max:255'],
            'createEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'createRoleId' => $canAssignRole ? ['required', 'integer', 'exists:roles,id'] : ['nullable'],
            'createPassword' => ['required', 'string', 'min:8'],
        ], [], [
            'createCode' => 'user code',
            'createName' => 'name',
            'createEmail' => 'email',
            'createRoleId' => 'role',
            'createPassword' => 'password',
        ]);

        $role = $canAssignRole
            ? Role::query()->findOrFail($data['createRoleId'])
            : Role::query()->where('slug', 'cashier')->firstOrFail();

        User::query()->create([
            'user_code' => $data['createCode'],
            'name' => $data['createName'],
            'email' => $data['createEmail'],
            'role_id' => $role->id,
            'role' => $role->name,
            'password' => Hash::make($data['createPassword']),
            'prohibition_flag' => false,
            'photo_path' => '',
        ]);

        $this->resetCreateForm();
        $this->createOpen = false;
        $this->dispatch('toast', message: 'Employee created.');
    }

    public function editUser(int $userId): void
    {
        $this->authorizePosPermission('users', 'edit');
        $user = User::query()->findOrFail($userId);
        $this->resetValidation();
        $this->editingId = $user->id;
        $this->editCode = (string) $user->user_code;
        $this->editName = (string) $user->name;
        $this->editEmail = (string) $user->email;
        $this->editRoleId = (string) ($user->role_id ?? '');
        $this->editPassword = '';
    }

    public function closeEdit(): void
    {
        $this->editingId = null;
    }

    public function updateUser(): void
    {
        $this->authorizePosPermission('users', 'edit');
        $canAssignRole = auth('web')->user()?->hasPermissionTo('roles', 'edit') ?? false;
        $data = $this->validate([
            'editCode' => ['required', 'string', 'max:100', 'unique:users,user_code,' . $this->editingId],
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->editingId],
            'editRoleId' => $canAssignRole ? ['required', 'integer', 'exists:roles,id'] : ['nullable'],
            'editPassword' => ['nullable', 'string', 'min:8'],
        ], [], [
            'editCode' => 'user code',
            'editName' => 'name',
            'editEmail' => 'email',
            'editRoleId' => 'role',
            'editPassword' => 'password',
        ]);

        $user = User::query()->findOrFail($this->editingId);
        $updateData = [
            'user_code' => $data['editCode'],
            'name' => $data['editName'],
            'email' => $data['editEmail'],
        ];

        if ($canAssignRole) {
            $role = Role::query()->findOrFail($data['editRoleId']);
            if ($user->role_id === Role::query()->where('slug', 'admin')->value('id') && $role->id !== $user->role_id) {
                abort_unless(User::hasOtherActiveAdmin($user->id), 422, 'Keep at least one active Admin account.');
            }
            $updateData['role_id'] = $role->id;
            $updateData['role'] = $role->name;
        }

        $passwordChanged = filled($data['editPassword'] ?? null);
        if ($passwordChanged) {
            $updateData['password'] = Hash::make($data['editPassword']);
        }

        $user->update($updateData);
        if ($passwordChanged) {
            ActivityLogger::record('users', 'password_changed', 'Changed password for '.$user->name, $user);
        }

        $this->editingId = null;
        $this->dispatch('toast', message: 'Employee updated.');
    }

    public function toggleStatus(int $userId): void
    {
        $this->authorizePosPermission('users', 'edit');
        $user = User::query()->findOrFail($userId);
        if (! $user->prohibition_flag && $user->roleRecord?->slug === 'admin') {
            abort_unless(User::hasOtherActiveAdmin($user->id), 422, 'Keep at least one active Admin account.');
        }
        $user->update(['prohibition_flag' => ! $user->prohibition_flag]);

        $this->dispatch('toast', message: $user->prohibition_flag ? 'Employee prohibited.' : 'Employee activated.');
    }

    public function render()
    {
        $this->authorizePosPermission('users', 'view');
        $users = User::query()
            ->where(function ($query): void {
                $query->whereNull('role')->orWhereNotIn('role', ['Customer', 'customer']);
            })
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('user_code', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->when($this->role, fn ($query) => $query->where('role', $this->role))
            ->latest()
            ->paginate(15);

        $roles = Role::query()->orderBy('name')->pluck('name');
        $roleOptions = Role::query()->orderBy('name')->get(['id', 'name']);
        $canCreate = auth('web')->user()?->hasPermissionTo('users', 'create') ?? false;
        $canEdit = auth('web')->user()?->hasPermissionTo('users', 'edit') ?? false;
        $canAssignRole = auth('web')->user()?->hasPermissionTo('roles', 'edit') ?? false;

        return view('livewire.user-table', compact('users', 'roles', 'roleOptions', 'canCreate', 'canEdit', 'canAssignRole'));
    }

    private function resetCreateForm(): void
    {
        $this->createCode = '';
        $this->createName = '';
        $this->createEmail = '';
        $this->createRoleId = '';
        $this->createPassword = '';
    }
}
