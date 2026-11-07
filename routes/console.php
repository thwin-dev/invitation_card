<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Support\PosBackupService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pos:backup {--type=scheduled}', function (PosBackupService $backups): int {
    try {
        $backup = $backups->create((string) $this->option('type'));
        $this->info('Created encrypted POS backup '.$backup->filename);

        return 0;
    } catch (Throwable $exception) {
        Log::error('Scheduled POS backup command failed.', ['exception' => $exception]);
        $this->error($exception->getMessage());

        return 1;
    }
})->purpose('Create an encrypted backup of POS data');

Artisan::command('pos:backup-prune', function (PosBackupService $backups): int {
    try {
        $deleted = $backups->pruneExpired();
        $this->info("Removed {$deleted} expired POS backup record(s).");

        return 0;
    } catch (Throwable $exception) {
        Log::error('POS backup retention command failed.', ['exception' => $exception]);
        $this->error($exception->getMessage());

        return 1;
    }
})->purpose('Remove POS backups past the configured retention period');

if (config('backup.enabled', true)) {
    Schedule::command('pos:backup --type=scheduled')
        ->dailyAt((string) config('backup.schedule_time', '02:00'))
        ->timezone((string) config('app.timezone', 'UTC'))
        ->withoutOverlapping();

    Schedule::command('pos:backup-prune')
        ->dailyAt((string) config('backup.retention_time', '03:00'))
        ->timezone((string) config('app.timezone', 'UTC'))
        ->withoutOverlapping();
}
