@extends('layouts.management')

@section('title', 'Employees')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Employees</h1>
        <p class="mt-2 text-sm text-gray-500">Review and manage users from the POS account table.</p>
    </div>
    @if(auth('web')->user()?->hasPermissionTo('users', 'create'))
        <button type="button" onclick="Livewire.dispatch('open-user-create')" class="pos-button">
            <i data-lucide="plus" class="mr-2 h-4 w-4"></i>Add employee
        </button>
    @endif
</div>

<livewire:user-table />
@endsection
