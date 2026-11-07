<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Support\PosBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function index(): View
    {
        $backups = Backup::query()->with('creator')->latest('id')->paginate(20);
        $latestSuccess = Backup::query()->where('status', 'success')->latest('id')->first();
        $latestAttempt = Backup::query()->latest('id')->first();

        $nextScheduledAt = now()->setTimeFromTimeString((string) config('backup.schedule_time', '02:00'));
        if ($nextScheduledAt->lte(now())) {
            $nextScheduledAt->addDay();
        }

        return view('management.security.backups', [
            'backups' => $backups,
            'latestSuccess' => $latestSuccess,
            'latestAttempt' => $latestAttempt,
            'nextScheduledAt' => $nextScheduledAt,
            'scheduleEnabled' => (bool) config('backup.enabled', true),
            'scheduleTime' => (string) config('backup.schedule_time', '02:00'),
            'retentionDays' => (int) config('backup.retention_days', 30),
            'maxUploadKb' => (int) config('backup.max_restore_upload_kb', 512000),
            'canCreate' => request()->user('web')?->hasPermissionTo('backups', 'create') ?? false,
            'canDelete' => request()->user('web')?->hasPermissionTo('backups', 'delete') ?? false,
            'canRestore' => request()->user('web')?->hasPermissionTo('backups', 'restore') ?? false,
            'canExport' => request()->user('web')?->hasPermissionTo('backups', 'export') ?? false,
        ]);
    }

    public function store(Request $request, PosBackupService $backups): RedirectResponse
    {
        try {
            $backup = $backups->create('manual', $request->user('web'));
        } catch (Throwable $exception) {
            report($exception);

            return to_route('management.backups.index')->with('error', $exception->getMessage());
        }

        try {
            $backups->pruneExpired();
        } catch (Throwable $exception) {
            Log::warning('POS backup was created but expired backups could not be pruned.', ['exception' => $exception]);
        }

        return to_route('management.backups.index')
            ->with('status', 'Encrypted backup created: '.$backup->filename);
    }

    public function download(Backup $backup): StreamedResponse
    {
        abort_unless($backup->status === 'success' && $backup->path, 404);
        abort_unless($backup->disk === config('backup.disk', 'local'), 404);
        $disk = Storage::disk($backup->disk);
        abort_unless($disk->exists($backup->path), 404, 'Backup file not found.');

        $path = $disk->path($backup->path);
        abort_unless(is_file($path), 404, 'Backup file not found.');
        $checksum = hash_file('sha256', $path);
        abort_unless($backup->checksum && is_string($checksum) && hash_equals($backup->checksum, $checksum), 409, 'Backup checksum verification failed.');

        return $disk->download($backup->path, $backup->filename, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Backup $backup, PosBackupService $backups): RedirectResponse
    {
        try {
            $backups->delete($backup);
        } catch (Throwable $exception) {
            report($exception);

            return to_route('management.backups.index')->with('error', $exception->getMessage());
        }

        return to_route('management.backups.index')->with('status', 'Backup deleted.');
    }

    public function restore(Request $request, PosBackupService $backups): RedirectResponse
    {
        $data = $request->validate([
            'backup_file' => [
                'required',
                'file',
                'mimes:zip',
                'max:'.(int) config('backup.max_restore_upload_kb', 512000),
            ],
        ]);

        try {
            $backups->restore($data['backup_file'], $request->user('web'));
        } catch (Throwable $exception) {
            report($exception);

            return to_route('management.backups.index')->with('error', $exception->getMessage());
        }

        return $this->finishRestore($request);
    }

    public function restoreStored(Request $request, Backup $backup, PosBackupService $backups): RedirectResponse
    {
        try {
            $backups->restoreStored($backup, $request->user('web'));
        } catch (Throwable $exception) {
            report($exception);

            return to_route('management.backups.index')->with('error', $exception->getMessage());
        }

        return $this->finishRestore($request);
    }

    private function finishRestore(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('status', 'Restore completed. Sign in with an account from the restored backup.');
    }
}
