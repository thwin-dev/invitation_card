<?php

return [
    'enabled' => env('BACKUP_ENABLED', true),
    'disk' => env('BACKUP_DISK', 'local'),
    'directory' => env('BACKUP_DIRECTORY', 'backups'),
    'schedule_time' => env('BACKUP_SCHEDULE_TIME', '02:00'),
    'retention_time' => env('BACKUP_RETENTION_TIME', '03:00'),
    'retention_days' => max(1, (int) env('BACKUP_RETENTION_DAYS', 30)),
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY') ?: env('APP_KEY'),
    'max_restore_upload_kb' => max(1, (int) env('BACKUP_MAX_RESTORE_UPLOAD_KB', 512000)),
    'max_restore_uncompressed_kb' => max(1, (int) env('BACKUP_MAX_RESTORE_UNCOMPRESSED_KB', 1048576)),
    'max_restore_files' => max(1, (int) env('BACKUP_MAX_RESTORE_FILES', 25000)),
    'mysql_dump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
    'mysql_client_binary' => env('MYSQL_CLIENT_BINARY', 'mysql'),
];
