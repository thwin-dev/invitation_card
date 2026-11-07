@extends('layouts.management')

@section('title', 'Change password')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('management.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition hover:text-brand-700">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to dashboard
            </a>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900">Change password</h1>
            <p class="mt-1 text-sm text-gray-500">Ensure your account is protected with a secure and unique password.</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Form Section (2 cols) --}}
        <div class="lg:col-span-2">
            <section class="pos-panel p-6">
                <div class="mb-5 border-b border-gray-100 pb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Update password</h2>
                    <p class="mt-1 text-sm text-gray-500">Enter your current password followed by your new password.</p>
                </div>

                <form method="post" action="{{ route('management.password.update') }}" class="space-y-4" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                    @csrf
                    @method('put')

                    <div>
                        <label for="current_password" class="block text-xs font-medium text-gray-700">Current password</label>
                        <div class="relative mt-1">
                            <input
                                :type="showCurrent ? 'text' : 'password'"
                                id="current_password"
                                name="current_password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter current password"
                                class="w-full rounded-lg border @error('current_password') border-red-300 @else border-gray-300 @enderror px-3 py-2 pr-10 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            <button
                                type="button"
                                @click="showCurrent = !showCurrent; $nextTick(() => window.lucide?.createIcons())"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                tabindex="-1"
                                aria-label="Toggle current password visibility"
                            >
                                <span x-show="!showCurrent"><i data-lucide="eye" class="h-4 w-4"></i></span>
                                <span x-show="showCurrent" x-cloak><i data-lucide="eye-off" class="h-4 w-4"></i></span>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-medium text-gray-700">New password</label>
                        <div class="relative mt-1">
                            <input
                                :type="showNew ? 'text' : 'password'"
                                id="password"
                                name="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Minimum 8 characters"
                                class="w-full rounded-lg border @error('password') border-red-300 @else border-gray-300 @enderror px-3 py-2 pr-10 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            <button
                                type="button"
                                @click="showNew = !showNew; $nextTick(() => window.lucide?.createIcons())"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                tabindex="-1"
                                aria-label="Toggle new password visibility"
                            >
                                <span x-show="!showNew"><i data-lucide="eye" class="h-4 w-4"></i></span>
                                <span x-show="showNew" x-cloak><i data-lucide="eye-off" class="h-4 w-4"></i></span>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-gray-700">Confirm new password</label>
                        <div class="relative mt-1">
                            <input
                                :type="showConfirm ? 'text' : 'password'"
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Re-enter new password"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15"
                            >
                            <button
                                type="button"
                                @click="showConfirm = !showConfirm; $nextTick(() => window.lucide?.createIcons())"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                tabindex="-1"
                                aria-label="Toggle confirm password visibility"
                            >
                                <span x-show="!showConfirm"><i data-lucide="eye" class="h-4 w-4"></i></span>
                                <span x-show="showConfirm" x-cloak><i data-lucide="eye-off" class="h-4 w-4"></i></span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                        <a href="{{ route('management.dashboard') }}" class="pos-button-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="pos-button">
                            <i data-lucide="key-round" class="mr-2 h-4 w-4"></i>
                            Save password
                        </button>
                    </div>
                </form>
            </section>
        </div>

        {{-- Security Guidelines / Profile Card (1 col) --}}
        <div>
            <section class="pos-panel p-6">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-xl font-bold text-brand-600">
                        {{ strtoupper(substr(auth('web')->user()?->name ?: 'U', 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-semibold text-gray-900">{{ auth('web')->user()?->name }}</h3>
                        <span class="mt-0.5 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                            {{ auth('web')->user()?->role ?: 'Account holder' }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 space-y-3 border-t border-gray-100 pt-4 text-xs text-gray-600">
                    <div class="flex justify-between">
                        <span class="text-gray-500">User code</span>
                        <span class="font-medium text-gray-900">{{ auth('web')->user()?->user_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Email</span>
                        <span class="truncate font-medium text-gray-900">{{ auth('web')->user()?->email }}</span>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Password Tips</h4>
                    <ul class="mt-3 space-y-2 text-xs text-gray-500">
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500"></i>
                            <span>At least 8 characters long</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500"></i>
                            <span>Use a mix of letters, numbers, and symbols</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500"></i>
                            <span>Do not reuse passwords across multiple systems</span>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
