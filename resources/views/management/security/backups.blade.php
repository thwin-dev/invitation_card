@extends('layouts.management')

@section('title', 'Automatic backups')

@section('content')
<div class="mb-7 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-500">Data protection</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">Automatic backups</h1>
        <p class="mt-2 text-sm text-gray-500">Encrypted copies of the POS database and uploaded product media.</p>
    </div>
    @if($canCreate)
        <form method="post" action="{{ route('management.backups.store') }}">
            @csrf
            <button type="submit" class="pos-button" wire:loading.attr="disabled">
                <i data-lucide="database" class="mr-2 h-4 w-4"></i>Create backup now
            </button>
        </form>
    @endif
</div>

@if(session('error'))
    <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

<section class="mb-6 grid gap-4 md:grid-cols-3" aria-label="Backup schedule and status">
    <article class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Automatic schedule</p>
        <p class="mt-2 text-lg font-semibold text-gray-900">{{ $scheduleEnabled ? 'Enabled' : 'Disabled' }}</p>
        <p class="mt-1 text-sm text-gray-500">Daily at {{ $scheduleTime }} · {{ config('app.timezone') }}</p>
    </article>
    <article class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Last successful backup</p>
        @if($latestSuccess)
            <p class="mt-2 text-lg font-semibold text-gray-900">{{ $latestSuccess->created_at?->format('d M Y, H:i') }}</p>
            <p class="mt-1 truncate text-sm text-gray-500" title="{{ $latestSuccess->filename }}">{{ $latestSuccess->filename }} · {{ number_format(($latestSuccess->size_bytes ?? 0) / 1048576, 2) }} MB</p>
        @else
            <p class="mt-2 text-lg font-semibold text-gray-900">None yet</p>
            <p class="mt-1 text-sm text-gray-500">Create the first backup now.</p>
        @endif
    </article>
    <article class="pos-panel p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Next scheduled run</p>
        <p class="mt-2 text-lg font-semibold text-gray-900">{{ $scheduleEnabled ? $nextScheduledAt->format('d M Y, H:i') : 'Not scheduled' }}</p>
        <p class="mt-1 text-sm text-gray-500">Keep {{ $retentionDays }} days of backup history.</p>
    </article>
</section>

<section class="pos-panel mb-6 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="font-semibold text-gray-900">Backup history</h2>
            <p class="mt-1 text-xs text-gray-500">Files are stored on the private local disk and encrypted with AES-256.</p>
        </div>
        @if($latestAttempt?->status === 'failed')
            <span class="rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">Latest attempt failed</span>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3 sm:px-6">Backup</th>
                    <th class="px-5 py-3">Created</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Size</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($backups as $backup)
                    <tr>
                        <td class="max-w-72 px-5 py-3.5 sm:px-6">
                            <span class="block truncate font-medium text-gray-800" title="{{ $backup->filename }}">{{ $backup->filename }}</span>
                            <span class="mt-0.5 block text-xs text-gray-400">{{ $backup->creator?->name ?: ($backup->type === 'scheduled' ? 'Scheduler' : 'System') }}</span>
                            @if($backup->error_message)
                                <span class="mt-1 block text-xs text-red-600">{{ $backup->error_message }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-gray-600">{{ $backup->created_at?->format('d M Y, H:i') }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-gray-600">{{ str($backup->type)->replace('-', ' ')->title() }}</td>
                        <td class="px-5 py-3.5">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-semibold',
                                'bg-emerald-50 text-emerald-700' => $backup->status === 'success',
                                'bg-red-50 text-red-700' => $backup->status === 'failed',
                            ])>{{ str($backup->status)->title() }}</span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right tabular-nums text-gray-600">{{ $backup->size_bytes ? number_format($backup->size_bytes / 1048576, 2).' MB' : '—' }}</td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                @if($backup->status === 'success' && $canExport)
                                    <a href="{{ route('management.backups.download', $backup) }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Download</a>
                                @endif
                                @if($backup->status === 'success' && $canRestore)
                                    <form method="post" action="{{ route('management.backups.restore-backup', $backup) }}" onsubmit="return confirm('Restore this backup? Current POS data and uploaded files will be replaced after a pre-restore backup is created.')">
                                        @csrf
                                        <button type="submit" class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50">Restore</button>
                                    </form>
                                @endif
                                @if($canDelete)
                                    <form method="post" action="{{ route('management.backups.destroy', $backup) }}" onsubmit="return confirm('Delete this backup permanently?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No backups yet. Use “Create backup now” to make the first one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($backups->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 sm:px-6">{{ $backups->links() }}</div>
    @endif
</section>

@if($canRestore)
    <section class="pos-panel overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <h2 class="font-semibold text-gray-900">Restore an external backup</h2>
            <p class="mt-1 text-sm text-gray-500">Upload an encrypted backup created by this POS installation.</p>
        </div>
        <form method="post" action="{{ route('management.backups.restore') }}" enctype="multipart/form-data" class="space-y-4 p-5 sm:p-6" onsubmit="return confirm('This will replace the current database and uploaded files. The app creates a pre-restore backup first, then signs you out. Continue?')">
            @csrf
            <label for="backup-file" class="block text-sm font-medium text-gray-700">Encrypted backup file</label>
            <input id="backup-file" name="backup_file" type="file" accept=".zip,application/zip" required class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold">
            @error('backup_file') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="text-xs text-gray-500">Maximum upload size: {{ number_format($maxUploadKb / 1024) }} MB. The current account will be signed out when restore finishes.</p>
            <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-900 hover:bg-amber-100">Upload and restore</button>
        </form>
    </section>
@endif
@endsection
