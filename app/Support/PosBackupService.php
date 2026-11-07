<?php

namespace App\Support;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class PosBackupService
{
    private bool $keepMaintenanceModeAfterRestoreFailure = false;

    private const ARCHIVE_FORMAT = 'sale-pos-backup';

    private const DATABASE_ENTRY = 'database/database.sqlite';

    private const MYSQL_DATABASE_ENTRY = 'database/database.sql';

    public function create(string $type = 'manual', ?User $actor = null): Backup
    {
        $diskName = $this->privateDiskName();
        $disk = Storage::disk($diskName);
        [$directory, $backupRoot] = $this->backupDirectory($disk);
        $lock = $this->acquireLock($backupRoot);
        $workingDirectory = $backupRoot.'/.work-'.Str::uuid();
        $startedAt = now();
        $filename = 'sale-pos-backup-'.$startedAt->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.zip';
        $relativePath = $directory.'/'.$filename;
        $archivePath = $workingDirectory.'/backup.zip';
        $finalPath = $disk->path($relativePath);

        try {
            $this->assertRuntime();
            $this->ensureDirectory($workingDirectory);
            $driver = $this->databaseDriver();
            $databaseEntry = $this->databaseEntry($driver);
            $databaseSnapshot = $workingDirectory.($driver === 'sqlite' ? '/database.sqlite' : '/database.sql');
            if ($driver === 'sqlite') {
                $this->snapshotSqlite($databaseSnapshot);
                $this->assertHealthySqlite($databaseSnapshot);
            } else {
                $this->dumpMysqlDatabase($databaseSnapshot, $workingDirectory);
            }

            $databaseChecksum = hash_file('sha256', $databaseSnapshot);
            if (! is_string($databaseChecksum)) {
                throw new RuntimeException('The database backup checksum could not be calculated.');
            }

            $manifest = [
                'format' => self::ARCHIVE_FORMAT,
                'version' => 1,
                'created_at' => $startedAt->toIso8601String(),
                'database_driver' => $driver,
                'database_entry' => $databaseEntry,
                'database_sha256' => $databaseChecksum,
                'database_schema_sha256' => $driver === 'sqlite' ? $this->sqliteSchemaFingerprint($databaseSnapshot) : null,
                'migration_fingerprint' => $this->migrationFingerprint(),
                'app_name' => (string) config('app.name'),
            ];

            $this->writeEncryptedArchive($archivePath, $databaseSnapshot, $databaseEntry, $manifest);
            $checksum = hash_file('sha256', $archivePath);
            if (! is_string($checksum)) {
                throw new RuntimeException('The backup archive checksum could not be calculated.');
            }
            $this->ensureDirectory(dirname($finalPath));

            if (! rename($archivePath, $finalPath)) {
                throw new RuntimeException('The backup archive could not be saved.');
            }
            @chmod($finalPath, 0600);

            try {
                return Backup::query()->create([
                    'type' => $type,
                    'status' => 'success',
                    'disk' => $diskName,
                    'path' => $relativePath,
                    'filename' => $filename,
                    'size_bytes' => filesize($finalPath) ?: 0,
                    'checksum' => $checksum,
                    'created_by' => $actor?->getKey(),
                    'metadata' => [
                        'database_driver' => $driver,
                        'includes_public_storage' => true,
                        'encrypted' => true,
                    ],
                ]);
            } catch (Throwable $exception) {
                @unlink($finalPath);
                throw $exception;
            }
        } catch (Throwable $exception) {
            try {
                Backup::query()->create([
                    'type' => $type,
                    'status' => 'failed',
                    'disk' => $diskName,
                    'filename' => $filename,
                    'created_by' => $actor?->getKey(),
                    'error_message' => 'Backup failed. Check the application log for details.',
                ]);
            } catch (Throwable $recordException) {
                Log::error('Could not save POS backup failure status.', ['exception' => $recordException]);
            }

            Log::error('POS backup failed.', [
                'type' => $type,
                'filename' => $filename,
                'exception' => $exception,
            ]);

            throw new RuntimeException('Backup could not be completed. Check the backup history and application log.', 0, $exception);
        } finally {
            $this->removeDirectory($workingDirectory);
            $this->releaseLock($lock);
        }
    }

    public function restore(UploadedFile $upload, User $actor): Backup
    {
        $archivePath = $upload->getRealPath();
        if (! is_string($archivePath) || ! is_file($archivePath)) {
            throw new RuntimeException('The uploaded backup file could not be read.');
        }

        return $this->restoreArchive($archivePath, $actor);
    }

    public function restoreStored(Backup $backup, User $actor): Backup
    {
        if ($backup->status !== 'success' || ! $backup->path || $backup->disk !== $this->privateDiskName()) {
            throw new RuntimeException('Only completed private backups can be restored.');
        }
        $this->assertManagedBackupPath($backup->path);

        $disk = Storage::disk($backup->disk);
        if (! $disk->exists($backup->path)) {
            throw new RuntimeException('The selected backup file could not be found.');
        }

        $archivePath = $disk->path($backup->path);
        $archiveChecksum = hash_file('sha256', $archivePath);
        if (! $backup->checksum || ! is_string($archiveChecksum) || ! hash_equals($backup->checksum, $archiveChecksum)) {
            throw new RuntimeException('The selected backup failed its checksum verification.');
        }

        return $this->restoreArchive($archivePath, $actor);
    }

    private function restoreArchive(string $archivePath, User $actor): Backup
    {
        $this->keepMaintenanceModeAfterRestoreFailure = false;
        $this->assertRuntime();
        $diskName = $this->privateDiskName();
        $disk = Storage::disk($diskName);
        [, $backupRoot] = $this->backupDirectory($disk);
        $workingDirectory = $backupRoot.'/.restore-'.Str::uuid();
        $this->ensureDirectory($workingDirectory);
        $driver = $this->databaseDriver();
        $databaseStage = $workingDirectory.($driver === 'sqlite' ? '/database.sqlite' : '/database.sql');
        $publicStage = $workingDirectory.'/public';
        $this->ensureDirectory($publicStage);
        $maintenanceMode = app()->maintenanceMode();
        $wasInMaintenance = $maintenanceMode->active();
        $startedMaintenance = false;

        try {
            $manifest = $this->extractAndValidateArchive($archivePath, $databaseStage, $publicStage, $driver);
            if (! $wasInMaintenance) {
                $maintenanceMode->activate(['retry' => 60, 'refresh' => 5, 'status' => 503]);
                $startedMaintenance = true;
            }

            $preRestoreBackup = $this->create('pre-restore', $actor);
            $lock = $this->acquireLock($backupRoot);

            try {
                return $this->replaceCurrentData($databaseStage, $publicStage, $manifest, $preRestoreBackup, $actor, $workingDirectory);
            } finally {
                $this->releaseLock($lock);
            }
        } finally {
            $this->removeDirectory($workingDirectory);
            if ($startedMaintenance && ! $this->keepMaintenanceModeAfterRestoreFailure && $maintenanceMode->active()) {
                $maintenanceMode->deactivate();
            }
        }
    }

    public function pruneExpired(): int
    {
        $disk = Storage::disk($this->privateDiskName());
        $cutoff = now()->subDays(max(1, (int) config('backup.retention_days', 30)));
        $expired = Backup::query()->where('created_at', '<', $cutoff)->orderBy('id')->get();
        $deleted = 0;

        foreach ($expired as $backup) {
            if ($backup->disk !== $this->privateDiskName()) {
                Log::warning('Expired POS backup record uses an unmanaged storage disk and was skipped.', ['backup_id' => $backup->id]);
                continue;
            }

            if ($backup->path) {
                try {
                    $this->assertManagedBackupPath($backup->path);
                } catch (RuntimeException $exception) {
                    Log::warning('Expired POS backup record has an unmanaged path and was skipped.', ['backup_id' => $backup->id]);
                    continue;
                }
            }

            if ($backup->path && $disk->exists($backup->path) && ! $disk->delete($backup->path)) {
                Log::warning('Expired POS backup file could not be removed.', ['backup_id' => $backup->id]);
                continue;
            }

            $backup->delete();
            $deleted++;
        }

        return $deleted;
    }

    public function delete(Backup $backup): void
    {
        if ($backup->disk !== $this->privateDiskName()) {
            throw new RuntimeException('Only private local backups can be deleted.');
        }

        if ($backup->path) {
            $this->assertManagedBackupPath($backup->path);
        }

        $disk = Storage::disk($backup->disk);
        if ($backup->path && $disk->exists($backup->path) && ! $disk->delete($backup->path)) {
            throw new RuntimeException('The backup file could not be deleted.');
        }

        $backup->delete();
    }

    private function snapshotSqlite(string $destination): void
    {
        $databasePath = $this->sqliteDatabasePath();
        if (! is_file($databasePath)) {
            throw new RuntimeException('The SQLite database file could not be found.');
        }

        $pdo = DB::connection()->getPdo();
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            throw new RuntimeException('The active database connection is not SQLite.');
        }

        $pdo->exec('VACUUM INTO '.$pdo->quote($destination));
    }

    private function assertHealthySqlite(string $path): void
    {
        $pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $result = $pdo->query('PRAGMA integrity_check')->fetchColumn();
        $pdo = null;

        if ($result !== 'ok') {
            throw new RuntimeException('The SQLite backup did not pass its integrity check.');
        }
    }

    private function databaseDriver(): string
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['sqlite', 'mysql', 'mariadb'], true)) {
            throw new RuntimeException('Automatic backups support SQLite, MySQL, and MariaDB databases.');
        }

        return $driver;
    }

    private function databaseEntry(string $driver): string
    {
        return match ($driver) {
            'sqlite' => self::DATABASE_ENTRY,
            'mysql', 'mariadb' => self::MYSQL_DATABASE_ENTRY,
            default => throw new RuntimeException('This backup database driver is not supported.'),
        };
    }

    private function migrationFingerprint(): string
    {
        try {
            $migrations = DB::table('migrations')->orderBy('migration')->pluck('migration')->all();
        } catch (Throwable $exception) {
            throw new RuntimeException('Backups require the Laravel migrations table. Run the pending migrations first.', 0, $exception);
        }

        sort($migrations, SORT_STRING);

        return hash('sha256', json_encode($migrations, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function dumpMysqlDatabase(string $destination, string $workingDirectory): void
    {
        $defaultsFile = $this->writeMysqlDefaultsFile($workingDirectory);
        $database = (string) DB::connection()->getConfig('database');
        if ($database === '') {
            throw new RuntimeException('The MySQL database name is not configured.');
        }

        $process = new Process([
            (string) config('backup.mysql_dump_binary', 'mysqldump'),
            '--defaults-extra-file='.$defaultsFile,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--hex-blob',
            '--default-character-set=utf8mb4',
            '--no-tablespaces',
            '--add-drop-table',
            '--result-file='.$destination,
            '--',
            $database,
        ]);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($destination) || filesize($destination) === 0) {
            throw new RuntimeException('MySQL backup export failed. Check the MySQL client tools and database privileges.');
        }

        @chmod($destination, 0600);
    }

    private function importMysqlDatabase(string $dumpPath, string $workingDirectory): void
    {
        $defaultsFile = $this->writeMysqlDefaultsFile($workingDirectory);
        $database = (string) DB::connection()->getConfig('database');
        if ($database === '') {
            throw new RuntimeException('The MySQL database name is not configured.');
        }

        $input = fopen($dumpPath, 'rb');
        if (! is_resource($input)) {
            throw new RuntimeException('The MySQL backup export could not be read.');
        }

        try {
            $process = new Process([
                (string) config('backup.mysql_client_binary', 'mysql'),
                '--defaults-extra-file='.$defaultsFile,
                '--default-character-set=utf8mb4',
                '--database='.$database,
            ]);
            $process->setTimeout(3600);
            $process->setInput($input);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('MySQL backup import failed. Check the MySQL client tools and database privileges.');
            }
        } finally {
            fclose($input);
        }

        DB::purge();
        DB::connection()->getPdo();
    }

    private function writeMysqlDefaultsFile(string $workingDirectory): string
    {
        $connection = DB::connection();
        $config = $connection->getConfig();
        $lines = ['[client]'];
        foreach (['host', 'port', 'user', 'password', 'socket'] as $key) {
            $value = $config[$key] ?? null;
            if ($value === null || ($value === '' && $key !== 'password')) {
                continue;
            }

            $value = str_replace(["\\", '"', "\n", "\r"], ["\\\\", '\\"', '\\n', '\\r'], (string) $value);
            $lines[] = $key.'="'.$value.'"';
        }

        $path = $workingDirectory.'/mysql-client.cnf';
        if (file_put_contents($path, implode("\n", $lines)."\n", LOCK_EX) === false) {
            throw new RuntimeException('A private MySQL client configuration could not be created.');
        }
        @chmod($path, 0600);

        return $path;
    }

    private function mysqlDumpHasTable(string $dumpPath, string $table): bool
    {
        $file = new \SplFileObject($dumpPath, 'r');
        $pattern = '/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?'.preg_quote($table, '/').'`?\s*\(/i';
        foreach ($file as $line) {
            if (is_string($line) && preg_match($pattern, trim($line)) === 1) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $manifest */
    private function writeEncryptedArchive(string $archivePath, string $databaseSnapshot, string $databaseEntry, array $manifest): void
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The backup archive could not be created.');
        }

        try {
            $this->setArchivePassword($zip);
            $this->addEncryptedFile($zip, $databaseSnapshot, $databaseEntry);
            $this->addPublicStorageFiles($zip);

            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (! $zip->addFromString('manifest.json', $manifestJson)) {
                throw new RuntimeException('The backup manifest could not be written.');
            }
            $this->encryptEntry($zip, 'manifest.json');
        } catch (Throwable $exception) {
            $zip->close();
            @unlink($archivePath);
            throw $exception;
        }

        if (! $zip->close()) {
            @unlink($archivePath);
            throw new RuntimeException('The encrypted backup archive could not be finalized.');
        }

        @chmod($archivePath, 0600);
    }

    private function addPublicStorageFiles(ZipArchive $zip): void
    {
        $publicRoot = rtrim((string) config('filesystems.disks.public.root', storage_path('app/public')), DIRECTORY_SEPARATOR);
        if (! is_dir($publicRoot)) {
            return;
        }

        if (is_link($publicRoot)) {
            throw new RuntimeException('The public storage root cannot be a symbolic link.');
        }

        $rootLength = strlen($publicRoot) + 1;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($publicRoot, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), $rootLength));
            if ($relativePath === '' || str_contains($relativePath, '../')) {
                throw new RuntimeException('A storage file has an invalid archive path.');
            }

            $entry = 'storage/public/'.$relativePath;
            $this->addEncryptedFile($zip, $file->getPathname(), $entry);
        }
    }

    private function addEncryptedFile(ZipArchive $zip, string $source, string $entry): void
    {
        if (! $zip->addFile($source, $entry)) {
            throw new RuntimeException('A file could not be added to the backup archive.');
        }

        $this->encryptEntry($zip, $entry);
    }

    private function encryptEntry(ZipArchive $zip, string $entry): void
    {
        if (! $zip->setEncryptionName($entry, ZipArchive::EM_AES_256)) {
            throw new RuntimeException('Backup encryption could not be applied. Check the PHP zip extension and libzip version.');
        }
    }

    private function setArchivePassword(ZipArchive $zip): void
    {
        if (! $zip->setPassword($this->archivePassword())) {
            throw new RuntimeException('The backup encryption key could not be initialized.');
        }
    }

    private function archivePassword(): string
    {
        $key = trim((string) config('backup.encryption_key', ''));
        if ($key === '') {
            throw new RuntimeException('Set APP_KEY or BACKUP_ENCRYPTION_KEY before creating backups.');
        }

        return bin2hex(hash_hkdf('sha256', $key, 32, 'sale-pos-backup-archive'));
    }

    /** @return array<string, mixed> */
    private function extractAndValidateArchive(string $archivePath, string $databaseStage, string $publicStage, string $currentDriver): array
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('The uploaded file is not a readable backup archive.');
        }

        try {
            $this->setArchivePassword($zip);
            $this->assertSafeArchiveEntries($zip);
            $manifestStatistics = $zip->statName('manifest.json');
            if (! is_array($manifestStatistics) || (int) ($manifestStatistics['size'] ?? PHP_INT_MAX) > 65536) {
                throw new RuntimeException('The backup manifest is missing or too large.');
            }

            $manifestJson = $zip->getFromName('manifest.json');
            if (! is_string($manifestJson)) {
                throw new RuntimeException('The backup is invalid or was encrypted with a different application key.');
            }

            $manifest = json_decode($manifestJson, true, 32, JSON_THROW_ON_ERROR);
            $backupDriver = (string) ($manifest['database_driver'] ?? '');
            $databaseEntry = $this->databaseEntry($backupDriver);
            if (($manifest['format'] ?? null) !== self::ARCHIVE_FORMAT
                || ($manifest['version'] ?? null) !== 1
                || $backupDriver !== $currentDriver
                || ($manifest['database_entry'] ?? null) !== $databaseEntry) {
                throw new RuntimeException('This backup format is not compatible with the current POS.');
            }

            $this->copyArchiveEntry($zip, $databaseEntry, $databaseStage);

            $databaseChecksum = hash_file('sha256', $databaseStage);
            if (! is_string($databaseChecksum) || ! hash_equals((string) ($manifest['database_sha256'] ?? ''), $databaseChecksum)) {
                throw new RuntimeException('The database file checksum does not match this backup.');
            }

            if (! hash_equals($this->migrationFingerprint(), (string) ($manifest['migration_fingerprint'] ?? ''))) {
                throw new RuntimeException('The backup migrations do not match this POS version. Apply the matching migrations before restoring.');
            }

            if ($backupDriver === 'sqlite') {
                $this->assertHealthySqlite($databaseStage);
                $backupSchema = $this->sqliteSchemaFingerprint($databaseStage);
                $currentSchema = $this->sqliteSchemaFingerprint($this->sqliteDatabasePath());
                if (! hash_equals((string) ($manifest['database_schema_sha256'] ?? ''), $backupSchema)
                    || ! hash_equals($currentSchema, $backupSchema)) {
                    throw new RuntimeException('The backup schema does not match this POS version. Apply the matching migrations before restoring.');
                }

                if (! $this->sqliteHasTable($databaseStage, 'backups')) {
                    throw new RuntimeException('The backup does not contain the POS backup history table.');
                }
            } elseif (! $this->mysqlDumpHasTable($databaseStage, 'backups') || ! $this->mysqlDumpHasTable($databaseStage, 'migrations')) {
                throw new RuntimeException('The backup does not contain the POS backup history and migration tables.');
            }

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->getNameIndex($index);
                if (! is_string($entry) || ! str_starts_with($entry, 'storage/public/') || str_ends_with($entry, '/')) {
                    continue;
                }

                $relativePath = substr($entry, strlen('storage/public/'));
                $destination = $publicStage.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
                $this->ensureDirectory(dirname($destination));
                $this->copyArchiveEntry($zip, $entry, $destination);
            }

            return $manifest;
        } finally {
            $zip->close();
        }
    }

    private function assertSafeArchiveEntries(ZipArchive $zip): void
    {
        $maximumFiles = max(1, (int) config('backup.max_restore_files', 25000));
        $maximumSize = max(1, (int) config('backup.max_restore_uncompressed_kb', 1048576)) * 1024;
        $totalSize = 0;
        $seen = [];

        if ($zip->numFiles > $maximumFiles) {
            throw new RuntimeException('The backup contains too many files to restore safely.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);
            if (! is_string($entry) || str_contains($entry, "\\") || str_starts_with($entry, '/') || preg_match('/^[A-Za-z]:/', $entry) === 1) {
                throw new RuntimeException('The backup contains an unsafe file path.');
            }

            $segments = explode('/', $entry);
            if (in_array('..', $segments, true)) {
                throw new RuntimeException('The backup contains an unsafe file path.');
            }

            if (isset($seen[$entry])) {
                throw new RuntimeException('The backup contains duplicate file paths.');
            }
            $seen[$entry] = true;

            $statistics = $zip->statIndex($index);
            $totalSize += max(0, (int) ($statistics['size'] ?? 0));
            if ($totalSize > $maximumSize) {
                throw new RuntimeException('The backup expands beyond the configured restore size limit.');
            }

            if ($entry !== 'manifest.json' && $entry !== self::DATABASE_ENTRY && $entry !== self::MYSQL_DATABASE_ENTRY && ! str_starts_with($entry, 'storage/public/')) {
                throw new RuntimeException('The backup contains an unsupported file.');
            }
        }
    }

    private function copyArchiveEntry(ZipArchive $zip, string $entry, string $destination): void
    {
        $input = $zip->getStream($entry);
        if (! is_resource($input)) {
            throw new RuntimeException('A backup file could not be decrypted. Check the application backup key.');
        }

        $this->ensureDirectory(dirname($destination));
        $output = fopen($destination, 'wb');
        if (! is_resource($output)) {
            fclose($input);
            throw new RuntimeException('The backup could not be extracted to private temporary storage.');
        }

        try {
            if (stream_copy_to_stream($input, $output) === false) {
                throw new RuntimeException('A backup file could not be extracted.');
            }
        } finally {
            fclose($input);
            fclose($output);
        }
    }

    /** @param array<string, mixed> $manifest */
    private function replaceCurrentData(
        string $databaseStage,
        string $publicStage,
        array $manifest,
        Backup $preRestoreBackup,
        User $actor,
        string $workingDirectory,
    ): Backup {
        $driver = (string) ($manifest['database_driver'] ?? '');
        $sqlite = $driver === 'sqlite';
        if (! in_array($driver, ['sqlite', 'mysql', 'mariadb'], true) || DB::connection()->getDriverName() !== $driver) {
            throw new RuntimeException('The backup database driver does not match the active POS database.');
        }

        $databasePath = $sqlite ? $this->sqliteDatabasePath() : null;
        $publicPath = rtrim((string) config('filesystems.disks.public.root', storage_path('app/public')), DIRECTORY_SEPARATOR);
        $suffix = Str::uuid();
        $newDatabasePath = $databasePath ? $databasePath.'.restore-'.$suffix : null;
        $oldDatabasePath = $databasePath ? $databasePath.'.before-restore-'.$suffix : null;
        $newPublicPath = $publicPath.'.restore-'.$suffix;
        $oldPublicPath = $publicPath.'.before-restore-'.$suffix;
        $databaseMoved = false;
        $databaseInstalled = false;
        $databaseImportAttempted = false;
        $publicMoved = false;
        $publicInstalled = false;
        $committed = false;
        $keepMaintenanceMode = false;

        if ($databasePath !== null) {
            $this->ensureDirectory(dirname($databasePath));
        }
        $this->ensureDirectory(dirname($publicPath));

        try {
            if (is_link($publicPath) || ($databasePath !== null && is_link($databasePath))) {
                throw new RuntimeException('Database and upload storage paths cannot be symbolic links during restore.');
            }

            if ($sqlite && $newDatabasePath !== null) {
                $this->copyFile($databaseStage, $newDatabasePath);
            }
            $this->copyDirectory($publicStage, $newPublicPath);

            $backupHistory = Backup::query()->get()->map(fn (Backup $backup): array => $backup->getAttributes())->all();
            $preRestoreAttributes = $preRestoreBackup->getAttributes();

            if ($sqlite && $databasePath !== null && $newDatabasePath !== null && $oldDatabasePath !== null) {
                DB::purge();
                if (is_file($databasePath)) {
                    if (! rename($databasePath, $oldDatabasePath)) {
                        throw new RuntimeException('The current database could not be moved safely.');
                    }
                    $databaseMoved = true;
                }
                if (! rename($newDatabasePath, $databasePath)) {
                    throw new RuntimeException('The restored database could not be installed.');
                }
                $databaseInstalled = true;
            }

            if (is_dir($publicPath)) {
                if (! rename($publicPath, $oldPublicPath)) {
                    throw new RuntimeException('Current uploaded files could not be moved safely.');
                }
                $publicMoved = true;
            }
            if (! rename($newPublicPath, $publicPath)) {
                throw new RuntimeException('Restored uploaded files could not be installed.');
            }
            $publicInstalled = true;

            if (! $sqlite) {
                $databaseImportAttempted = true;
                DB::purge();
                $this->importMysqlDatabase($databaseStage, $workingDirectory);
                $databaseInstalled = true;
            }

            DB::purge();
            DB::connection()->getPdo();
            $this->mergeBackupHistory($backupHistory, $preRestoreAttributes);
            $this->recordRestoreAudit($actor, $manifest);
            $restoredPreBackup = Backup::query()->where('path', $preRestoreAttributes['path'])->firstOrFail();
            $committed = true;

            return $restoredPreBackup;
        } catch (Throwable $exception) {
            DB::purge();

            if (! $sqlite && $databaseImportAttempted) {
                try {
                    $this->restoreMysqlDatabaseFromBackup($preRestoreBackup, $workingDirectory);
                } catch (Throwable $rollbackException) {
                    $keepMaintenanceMode = true;
                    Log::critical('MySQL restore rollback failed; maintenance mode remains enabled.', [
                        'backup_id' => $preRestoreBackup->getKey(),
                        'exception' => $rollbackException,
                    ]);
                }
            }

            if ($publicInstalled && is_dir($publicPath)) {
                $this->removeDirectory($publicPath);
            }
            if ($publicMoved && is_dir($oldPublicPath) && ! @rename($oldPublicPath, $publicPath)) {
                $keepMaintenanceMode = true;
            }

            if ($sqlite && $databaseInstalled && $databasePath !== null && is_file($databasePath)) {
                if (! $databaseMoved) {
                    $keepMaintenanceMode = true;
                }
                if (! @unlink($databasePath)) {
                    $keepMaintenanceMode = true;
                }
            }
            if ($sqlite && $databaseMoved && $oldDatabasePath !== null && $databasePath !== null
                && is_file($oldDatabasePath) && ! @rename($oldDatabasePath, $databasePath)) {
                $keepMaintenanceMode = true;
            }

            $this->keepMaintenanceModeAfterRestoreFailure = $keepMaintenanceMode;

            DB::purge();
            throw new RuntimeException('Restore failed. The previous database and uploaded files were returned where possible.', 0, $exception);
        } finally {
            DB::purge();
            if ($newDatabasePath !== null && is_file($newDatabasePath)) {
                @unlink($newDatabasePath);
            }
            $this->removeDirectory($newPublicPath);
            if ($committed) {
                if ($oldDatabasePath !== null && is_file($oldDatabasePath)) {
                    @unlink($oldDatabasePath);
                }
                $this->removeDirectory($oldPublicPath);
            }
        }
    }

    private function restoreMysqlDatabaseFromBackup(Backup $backup, string $workingDirectory): void
    {
        if (! $backup->path || $backup->disk !== $this->privateDiskName()) {
            throw new RuntimeException('The pre-restore backup is unavailable for rollback.');
        }

        $disk = Storage::disk($backup->disk);
        $archivePath = $disk->path($backup->path);
        $checksum = is_file($archivePath) ? hash_file('sha256', $archivePath) : false;
        if (! is_string($checksum) || ! $backup->checksum || ! hash_equals($backup->checksum, $checksum)) {
            throw new RuntimeException('The pre-restore backup failed its checksum verification.');
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('The pre-restore backup could not be opened for rollback.');
        }

        $rollbackDump = $workingDirectory.'/pre-restore.sql';
        try {
            $this->setArchivePassword($zip);
            $this->copyArchiveEntry($zip, self::MYSQL_DATABASE_ENTRY, $rollbackDump);
        } finally {
            $zip->close();
        }

        DB::purge();
        $this->importMysqlDatabase($rollbackDump, $workingDirectory);
        @unlink($rollbackDump);
    }

    /** @param list<array<string, mixed>> $backupHistory
     *  @param array<string, mixed> $preRestoreAttributes
     */
    private function mergeBackupHistory(array $backupHistory, array $preRestoreAttributes): void
    {
        $disk = Storage::disk($this->privateDiskName());
        $records = [...$backupHistory, $preRestoreAttributes];

        foreach ($records as $attributes) {
            if (! ($attributes['path'] ?? null) || ($attributes['disk'] ?? null) !== $this->privateDiskName()) {
                continue;
            }

            try {
                $this->assertManagedBackupPath($attributes['path']);
            } catch (RuntimeException) {
                continue;
            }

            if (! $disk->exists($attributes['path'])) {
                continue;
            }

            if (Backup::query()->where('path', $attributes['path'])->exists()) {
                continue;
            }

            Backup::withoutEvents(function () use ($attributes): void {
                $attributes['id'] = null;
                Backup::query()->create($attributes);
            });
        }
    }

    /** @param array<string, mixed> $manifest */
    private function recordRestoreAudit(User $actor, array $manifest): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        $userId = DB::table('users')->where('id', $actor->getKey())->exists() ? $actor->getKey() : null;
        DB::table('activity_logs')->insert([
            'actor_id' => $userId,
            'actor_name' => $actor->name,
            'actor_user_code' => $actor->user_code,
            'module' => 'backups',
            'action' => 'restored',
            'description' => 'Restored POS data from backup created '.(string) ($manifest['created_at'] ?? 'unknown date'),
            'subject_type' => null,
            'subject_id' => null,
            'changes' => json_encode(['backup_created_at' => $manifest['created_at'] ?? null], JSON_UNESCAPED_SLASHES),
            'route_name' => request()->route()?->getName(),
            'http_method' => request()->method(),
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'created_at' => now(),
        ]);
    }

    private function sqliteHasTable(string $databasePath, string $table): bool
    {
        $pdo = new PDO('sqlite:'.$databasePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $statement = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?");
        $statement->execute([$table]);
        $exists = (bool) $statement->fetchColumn();
        $pdo = null;

        return $exists;
    }

    private function sqliteSchemaFingerprint(string $databasePath): string
    {
        $pdo = new PDO('sqlite:'.$databasePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $schema = $pdo->query("SELECT type, name, tbl_name, sql FROM sqlite_master WHERE sql IS NOT NULL ORDER BY type, name, tbl_name")
            ->fetchAll(PDO::FETCH_ASSOC);
        $pdo = null;

        return hash('sha256', json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function sqliteDatabasePath(): string
    {
        $path = trim((string) config('database.connections.sqlite.database', ''));
        if ($path === '' || $path === ':memory:' || str_starts_with($path, 'file:')) {
            throw new RuntimeException('Backups require a file-based SQLite database.');
        }

        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && preg_match('/^[A-Za-z]:[\\\\\/]/', $path) !== 1) {
            $path = base_path($path);
        }

        return $path;
    }

    private function privateDiskName(): string
    {
        $diskName = (string) config('backup.disk', 'local');
        $diskConfig = config('filesystems.disks.'.$diskName, []);
        if ($diskName !== 'local' || ($diskConfig['driver'] ?? null) !== 'local') {
            throw new RuntimeException('POS backups must use the private local filesystem disk.');
        }

        $root = realpath((string) ($diskConfig['root'] ?? storage_path('app/private')))
            ?: (string) ($diskConfig['root'] ?? storage_path('app/private'));
        $publicRoot = realpath(public_path()) ?: public_path();
        $root = rtrim($root, DIRECTORY_SEPARATOR);
        $publicRoot = rtrim($publicRoot, DIRECTORY_SEPARATOR);
        if ($root === $publicRoot || str_starts_with($root.DIRECTORY_SEPARATOR, $publicRoot.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('POS backup storage cannot be inside the public web directory.');
        }

        return $diskName;
    }

    private function assertManagedBackupPath(string $path): void
    {
        $directory = trim((string) config('backup.directory', 'backups'), '/');
        if ($path === '' || str_contains($path, '\\') || str_starts_with($path, '/')
            || in_array('..', explode('/', $path), true)
            || ! str_starts_with($path, $directory.'/')) {
            throw new RuntimeException('The backup record points outside the managed backup directory.');
        }
    }

    /** @return array{string, string} */
    private function backupDirectory($disk): array
    {
        $directory = trim((string) config('backup.directory', 'backups'), '/');
        if ($directory === '' || str_contains($directory, '\\') || in_array('..', explode('/', $directory), true)) {
            throw new RuntimeException('The configured backup directory must stay inside private storage.');
        }

        $disk->makeDirectory($directory);
        $path = $disk->path($directory);
        $this->ensureDirectory($path);
        $root = realpath((string) config('filesystems.disks.local.root', storage_path('app/private')));
        $resolvedPath = realpath($path);
        $publicRoot = realpath(public_path()) ?: public_path();

        if ($root === false || $resolvedPath === false || ! str_starts_with($resolvedPath.DIRECTORY_SEPARATOR, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('The configured backup directory is outside private storage.');
        }
        if ($resolvedPath === rtrim($publicRoot, DIRECTORY_SEPARATOR) || str_starts_with($resolvedPath.DIRECTORY_SEPARATOR, rtrim($publicRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('The configured backup directory cannot be inside the public web directory.');
        }

        @chmod($resolvedPath, 0700);

        return [$directory, $resolvedPath];
    }

    private function assertRuntime(): void
    {
        if (! class_exists(ZipArchive::class)
            || ! method_exists(ZipArchive::class, 'setEncryptionName')
            || ! defined(ZipArchive::class.'::EM_AES_256')) {
            throw new RuntimeException('Encrypted backups require the PHP zip extension with AES-256 support.');
        }

        $this->archivePassword();
        $this->databaseDriver();
    }

    /** @return resource */
    private function acquireLock(string $backupRoot)
    {
        $lock = fopen($backupRoot.'/.backup.lock', 'c');
        if (! is_resource($lock) || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new RuntimeException('Another backup or restore is already running.');
        }

        return $lock;
    }

    /** @param resource $lock */
    private function releaseLock($lock): void
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException('Private backup storage could not be created.');
        }
    }

    private function copyFile(string $source, string $destination): void
    {
        $this->ensureDirectory(dirname($destination));
        if (! copy($source, $destination)) {
            throw new RuntimeException('A temporary database copy could not be created.');
        }
        @chmod($destination, 0600);
    }

    private function copyDirectory(string $source, string $destination): void
    {
        $this->ensureDirectory($destination);
        if (! is_dir($source)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $entry) {
            if ($entry->isLink()) {
                throw new RuntimeException('Symbolic links cannot be restored from a backup.');
            }

            $relativePath = substr($entry->getPathname(), strlen(rtrim($source, DIRECTORY_SEPARATOR)) + 1);
            $target = $destination.DIRECTORY_SEPARATOR.$relativePath;

            if ($entry->isDir()) {
                $this->ensureDirectory($target);
            } else {
                $this->copyFile($entry->getPathname(), $target);
            }
        }
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path) || is_link($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $entry) {
            if ($entry->isDir() && ! $entry->isLink()) {
                @rmdir($entry->getPathname());
            } else {
                @unlink($entry->getPathname());
            }
        }

        @rmdir($path);
    }
}
