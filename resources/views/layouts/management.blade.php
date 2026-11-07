<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#465fff">
<link rel="manifest" href="/manifest.json">
<title>@yield('title', 'Management') - {{ config('app.name') }}</title>
@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))) @vite(['resources/css/app.css', 'resources/js/app.js']) @endif
@livewireStyles
</head>
<body x-data="{ sidebarOpen: false, sidebarCollapsed: false, sidebarHover: false, livewireToast: { message: '', title: 'Success', type: 'success' } }" x-on:toast.window="livewireToast = { message: $event.detail.message, title: $event.detail.title || ($event.detail.type === 'error' ? 'Error' : 'Success'), type: $event.detail.type || 'success' }; setTimeout(() => livewireToast.message = '', 3500)" x-init="sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true'" class="min-h-screen bg-[#f8f9fc] text-gray-800">
<div :class="{ 'sidebar-collapsed': sidebarCollapsed, 'sidebar-hover': sidebarHover && sidebarCollapsed }" class="min-h-screen lg:flex">
<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-gray-900/40 lg:hidden"></div>
<aside @mouseenter="if (sidebarCollapsed) sidebarHover = true" @mouseleave="sidebarHover = false" :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen, 'lg:w-20': sidebarCollapsed && !sidebarHover, 'lg:w-64': !sidebarCollapsed || sidebarHover }" class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-gray-200 bg-white transition-all duration-300 ease-in-out lg:translate-x-0 lg:inset-y-0 lg:left-0 lg:flex lg:w-64 lg:flex-col lg:border-b-0 lg:border-r">
<div class="flex h-20 items-center border-b border-gray-100 px-7"><a href="{{ auth('web')->user()?->hasPermissionTo('dashboard', 'view') ? route('management.dashboard') : route('management.password.edit') }}" class="flex items-center gap-3 text-lg font-semibold text-[#1d2939]"><span class="sidebar-label flex h-10 w-10 items-center justify-center rounded-xl bg-[#465fff] text-sm font-bold text-white">SP</span><span class="sidebar-label">Sale POS</span></a></div>
<nav class="flex-1 overflow-x-auto px-4 py-6 lg:overflow-y-auto"><p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Menu</p>
@if(auth('web')->user()?->hasPermissionTo('dashboard', 'view'))<a href="{{ route('management.dashboard') }}" class="mb-2 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('management.dashboard') ? 'bg-[#eef2ff] text-[#465fff]' : 'text-gray-600 hover:bg-gray-50' }}"><i data-lucide="layout-dashboard" class="h-4 w-4 shrink-0" title="Dashboard"></i><span class="sidebar-label">Dashboard</span></a>@endif
@foreach ([
['Inventory', 'boxes', [['Products','management.products.index'],['Brands','management.brands.index'],['Categories','management.product-categories.index'],['Subcategories','management.product-subcategories.index'],['Units','management.product-units.index'],['Unit relations','management.product-unit-relations.index'],['Stock','management.stock.index'],['Inventory operations / transfers','management.inventory.operations'],['Shops and warehouses','management.branches.index'],['Branch prices','management.branches.prices'],['Stock movements','management.stock.movements']]],
['Sales', 'shopping-cart', [['Customers','management.customers.index'],['Sales history','management.sales.index'],['New sale','management.sales.create'],['Discount rules','management.discounts.index']]],
['Orders', 'clipboard-list', [['Order history','management.orders.index'],['New order','management.orders.create']]],
['Purchases', 'truck', [['Suppliers','management.suppliers.index'],['Purchase history','management.purchases.index'],['New purchase','management.purchases.create']]],
['Finance', 'wallet-cards', [['Expenses','management.expenses.index'],['Reports','management.reports.financial']]],
['People', 'users', [['Employees','management.users.index'],['Roles and permissions','management.security.roles.index'],['Change password','management.password.edit']]],
['Security', 'shield-check', [['Activity and audit logs','management.activity-logs.index'],['Backups','management.backups.index']]],
] as [$group, $icon, $items])
<details class="group mb-1" {{ collect($items)->contains(fn ($item) => request()->routeIs($item[1])) ? 'open' : '' }}><summary class="flex cursor-pointer gap-3 list-none items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50"><i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0 text-gray-400" title="{{ $group }}"></i><span class="sidebar-label">{{ $group }}</span> <span class="ml-auto text-xs text-gray-400 transition group-open:rotate-180">&#9662;</span></summary><div class="ml-3 border-l border-gray-200 pl-3">@foreach ($items as [$label, $route]) @php
$permissionModule = match (true) {
    $route === 'management.password.edit' => null,
    str_starts_with($route, 'management.security.roles.') => 'roles',
    str_starts_with($route, 'management.activity-logs.') => 'activity-logs',
    str_starts_with($route, 'management.backups.') => 'backups',
    str_starts_with($route, 'management.users.') => 'users',
    str_starts_with($route, 'management.product-categories.') => 'categories',
    str_starts_with($route, 'management.product-subcategories.') => 'subcategories',
    str_starts_with($route, 'management.product-units.') => 'units',
    str_starts_with($route, 'management.product-unit-relations.') => 'unit-relations',
    str_starts_with($route, 'management.products.') || str_starts_with($route, 'management.brands.') => 'products',
    str_starts_with($route, 'management.discounts.') => 'discounts',
    str_starts_with($route, 'management.sales.') => 'sales',
    str_starts_with($route, 'management.orders.') => 'orders',
    str_starts_with($route, 'management.customers.') => 'customers',
    str_starts_with($route, 'management.purchases.') => 'purchases',
    str_starts_with($route, 'management.suppliers.') => 'suppliers',
    str_starts_with($route, 'management.branches.') => 'branches',
    str_starts_with($route, 'management.inventory.') || str_starts_with($route, 'management.stock.') => 'inventory',
    str_starts_with($route, 'management.expenses.') => 'expenses',
    str_starts_with($route, 'management.reports.') => 'reports',
    default => null,
};
$permissionAction = in_array($route, ['management.sales.create', 'management.orders.create', 'management.purchases.create'], true) ? 'create' : 'view';
$showMenuItem = $permissionModule === null || auth('web')->user()?->hasPermissionTo($permissionModule, $permissionAction);
@endphp @if($showMenuItem)<a href="{{ route($route) }}" class="flex items-center rounded-lg px-3 py-2 text-sm {{ request()->routeIs($route) ? 'bg-[#eef2ff] font-medium text-[#465fff]' : 'text-gray-500 hover:bg-gray-50' }}"><span class="sidebar-label">{{ $label }}</span></a>@endif @endforeach</div></details>
@endforeach
</nav></aside>
<div :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'" class="min-w-0 flex-1 lg:ml-64"><header class="sticky top-0 z-20 flex min-h-20 items-center justify-between border-b border-gray-200 bg-white/95 px-5 backdrop-blur sm:px-8"><div class="flex items-center"><button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)" class="hidden rounded-lg border border-gray-200 p-2 text-gray-500 lg:block" aria-label="Collapse sidebar"><i data-lucide="panel-left-close" class="h-5 w-5"></i></button><button @click="sidebarOpen = !sidebarOpen" class="rounded-lg border border-gray-200 p-2 text-gray-500 lg:hidden" aria-label="Toggle navigation"><i data-lucide="menu" class="h-5 w-5"></i></button></div><div class="relative flex items-center gap-3" x-data="{ profileOpen: false }"><button @click="profileOpen = !profileOpen" class="flex items-center gap-3 rounded-lg p-1.5 hover:bg-gray-50" aria-label="Open profile menu"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">{{ strtoupper(substr(auth('web')->user()?->name ?? 'P', 0, 1)) }}</span><span class="hidden text-sm font-medium text-gray-700 md:inline">{{ auth('web')->user()?->name ?? 'POS User' }}</span><i data-lucide="chevron-down" class="hidden h-4 w-4 text-gray-400 md:block"></i></button><div x-show="profileOpen" @click.outside="profileOpen = false" x-cloak class="absolute right-0 top-12 z-50 w-52 rounded-xl border border-gray-200 bg-white p-2 shadow-xl"><div class="border-b border-gray-100 px-3 py-2"><p class="text-sm font-semibold text-gray-800">{{ auth('web')->user()?->name ?? 'POS User' }}</p><p class="mt-0.5 truncate text-xs text-gray-400">{{ auth('web')->user()?->email ?? '' }}</p></div><a href="{{ route('management.password.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-50"><i data-lucide="key-round" class="h-4 w-4"></i>Change password</a><form method="post" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm text-red-500 hover:bg-red-50"><i data-lucide="log-out" class="h-4 w-4"></i>Sign out</button></form></div></div></header>
<main class="mx-auto max-w-[1600px] px-5 py-7 sm:px-8">@if(session('status'))<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3500)" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-end="translate-y-2 opacity-0" style="position:fixed;top:1rem;right:1rem;width:380px;max-width:calc(100vw - 2rem);z-index:60" class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-xl sm:right-8 sm:top-8"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><i data-lucide="check" class="h-5 w-5"></i></span><div class="min-w-0 flex-1"><p class="text-sm font-semibold text-gray-900">Success</p><p class="mt-0.5 text-sm text-gray-500">{{ session('status') }}</p></div><button type="button" @click="show = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Dismiss notification"><i data-lucide="x" class="h-4 w-4"></i></button></div>@endif @yield('content')</main></div></div>
<div x-show="livewireToast.message" x-cloak x-transition class="fixed right-4 top-4 z-[70] flex w-[380px] max-w-[calc(100vw-2rem)] items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-xl sm:right-8 sm:top-8"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="livewireToast.type === 'error' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'"><i :data-lucide="livewireToast.type === 'error' ? 'circle-alert' : 'check'" class="h-5 w-5"></i></span><div class="min-w-0 flex-1"><p class="text-sm font-semibold text-gray-900" x-text="livewireToast.title"></p><p class="mt-0.5 text-sm text-gray-500" x-text="livewireToast.message"></p></div><button type="button" @click="livewireToast.message = ''" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100" aria-label="Dismiss notification"><i data-lucide="x" class="h-4 w-4"></i></button></div>
@livewireScripts
<script>
document.addEventListener('livewire:init', () => {
    Livewire.hook('morphed', () => {
        window.lucide?.createIcons();
        window.initDatePickers?.();
    });
});
document.addEventListener('DOMContentLoaded', () => {
    window.lucide?.createIcons();
    window.initDatePickers?.();
});
</script>
</body></html>
