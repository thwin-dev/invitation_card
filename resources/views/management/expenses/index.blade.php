@extends('layouts.management')

@section('title', 'Expenses')

@section('content')
    <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
        <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Expenses</h1>
                <p class="mt-1 text-sm text-gray-500">Record operating expenses and review the expense ledger.</p>
                <p class="mt-2 text-sm text-gray-500">Total expenses <strong class="text-gray-900">{{ number_format((float) $total, 2) }}</strong></p>
            </div>
            @if(auth('web')->user()?->hasPermissionTo('expenses', 'create'))<button type="button" @click="open = true" class="pos-button">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Add expense
            </button>@endif
        </div>

        @if(session('status'))
            <div
                x-data
                x-init="$dispatch('pos-toast', { type: 'success', title: 'Success', message: @js(session('status')) })"
            ></div>
        @endif

        <livewire:expense-table />

        @if(auth('web')->user()?->hasPermissionTo('expenses', 'create'))<section x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Add expense</h2>
                        <p class="mt-1 text-sm text-gray-500">Create a ledger entry for expense or income.</p>
                    </div>
                    <button type="button" @click="open = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                @if($errors->any())
                    <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('management.expenses.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700">
                        Type
                        <input name="type" value="{{ old('type') }}" placeholder="Expense type" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                    </label>
                    <label class="block text-sm font-medium text-gray-700">
                        Period
                        <input name="period" value="{{ old('period') }}" placeholder="Period" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                    </label>
                    <label class="block text-sm font-medium text-gray-700">
                        Date
                        <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                    </label>
                    <label class="block text-sm font-medium text-gray-700">
                        Title
                        <input name="title" value="{{ old('title') }}" placeholder="Title" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                    </label>
                    <label class="block text-sm font-medium text-gray-700">
                        Description
                        <textarea name="description" placeholder="Description" class="mt-2 min-h-24 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">{{ old('description') }}</textarea>
                    </label>
                    <label class="block text-sm font-medium text-gray-700">
                        Amount
                        <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" min="0" required placeholder="Amount" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                    </label>
                    <label class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700">
                        Income / profit entry
                        <input type="checkbox" name="is_profit" value="1" @checked(old('is_profit')) class="h-4 w-4 rounded border-gray-300 text-[#465fff] focus:ring-[#465fff]">
                    </label>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="open = false" class="pos-button-secondary">Cancel</button>
                        <button class="pos-button">
                            <i data-lucide="save" class="mr-2 h-4 w-4"></i>
                            Record expense
                        </button>
                    </div>
                </form>
            </div>
        </section>
        @endif
    </div>
@endsection
