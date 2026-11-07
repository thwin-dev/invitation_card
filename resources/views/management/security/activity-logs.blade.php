@extends('layouts.management')

@section('title', 'Activity and audit logs')

@section('content')
<div class="mb-7 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Activity and audit logs</h1>
        <p class="mt-1 text-sm text-gray-500">Review employee actions and field changes across POS modules.</p>
    </div>
    @if(auth('web')->user()?->hasPermissionTo('activity-logs', 'export'))
        <a href="{{ route('management.activity-logs.export', request()->query()) }}" class="pos-button-secondary"><i data-lucide="download" class="mr-2 h-4 w-4"></i>Export CSV</a>
    @endif
</div>

<section class="pos-panel mb-6 p-5">
    <form method="get" action="{{ route('management.activity-logs.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
        <label class="block text-xs font-medium text-gray-700 lg:col-span-2">Search
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Employee, action, record, route" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </label>
        <label class="block text-xs font-medium text-gray-700">Module
            <select name="module" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">All modules</option>
                @foreach($modules as $key => $label)
                    <option value="{{ $key }}" @selected(request('module') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs font-medium text-gray-700">Action
            <select name="action" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">All actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ str_replace('_', ' ', ucfirst($action)) }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs font-medium text-gray-700">Employee
            <select name="user_id" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
                <option value="">All employees</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}{{ $user->user_code ? ' · '.$user->user_code : '' }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs font-medium text-gray-700">From
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </label>
        <label class="block text-xs font-medium text-gray-700">To
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15">
        </label>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
            <button class="pos-button"><i data-lucide="filter" class="mr-2 h-4 w-4"></i>Filter logs</button>
            <a href="{{ route('management.activity-logs.index') }}" class="pos-button-secondary">Clear</a>
        </div>
    </form>
</section>

<section class="pos-panel overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-4">
        <h2 class="font-semibold text-gray-900">Recorded activity</h2>
        <p class="text-xs text-gray-500">{{ number_format($logs->total()) }} entries</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Employee</th><th class="px-5 py-3">Module</th><th class="px-5 py-3">Action</th><th class="px-5 py-3">Activity</th><th class="px-5 py-3">Record</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-5 py-4 text-gray-500">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="whitespace-nowrap px-5 py-4">
                            <span class="font-medium text-gray-800">{{ $log->actor_name ?? $log->actor?->name ?? 'Former employee' }}</span>
                            @if($log->actor_user_code)<span class="mt-0.5 block text-xs text-gray-400">{{ $log->actor_user_code }}</span>@endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $modules[$log->module] ?? str_replace('-', ' ', ucfirst($log->module)) }}</td>
                        <td class="whitespace-nowrap px-5 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">{{ str_replace('_', ' ', ucfirst($log->action)) }}</span></td>
                        <td class="min-w-64 px-5 py-4 text-gray-700">{{ $log->description }}
                            @if($log->route_name)<span class="mt-1 block text-xs text-gray-400">{{ $log->http_method }} · {{ $log->route_name }}</span>@endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-gray-500">{{ $log->subject_type ? class_basename($log->subject_type) : '—' }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</td>
                    </tr>
                    @if($log->changes)
                        <tr class="bg-gray-50/60">
                            <td colspan="6" class="px-5 py-3">
                                <details class="group">
                                    <summary class="w-fit cursor-pointer text-xs font-semibold text-brand-600">Show changed fields</summary>
                                    <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200 bg-white">
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-gray-50 text-left uppercase tracking-wide text-gray-500"><tr><th class="px-3 py-2">Field</th><th class="px-3 py-2">Before</th><th class="px-3 py-2">After</th></tr></thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach($log->changes as $field => $change)
                                                    @php($beforeValue = $change['from'] ?? null)
                                                    @php($afterValue = $change['to'] ?? null)
                                                    <tr>
                                                        <td class="whitespace-nowrap px-3 py-2 font-medium text-gray-700">{{ str_replace('_', ' ', ucfirst($field)) }}</td>
                                                        <td class="max-w-xl break-all px-3 py-2 text-gray-500">{{ is_array($beforeValue) ? json_encode($beforeValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (is_bool($beforeValue) ? ($beforeValue ? 'true' : 'false') : ($beforeValue ?? '—')) }}</td>
                                                        <td class="max-w-xl break-all px-3 py-2 text-gray-700">{{ is_array($afterValue) ? json_encode($afterValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (is_bool($afterValue) ? ($afterValue ? 'true' : 'false') : ($afterValue ?? '—')) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-500">No activity matches these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $logs->links() }}</div>@endif
</section>
@endsection
