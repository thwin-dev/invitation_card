<?php

use App\Http\Controllers\RoleSecurityController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

Route::get('security/roles', [RoleSecurityController::class, 'index'])->name('security.roles.index');
Route::post('security/roles', [RoleSecurityController::class, 'store'])->name('security.roles.store');
Route::put('security/roles/{role}', [RoleSecurityController::class, 'update'])->name('security.roles.update');
Route::delete('security/roles/{role}', [RoleSecurityController::class, 'destroy'])->name('security.roles.destroy');
Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
Route::get('activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
Route::post('backups/{backup}/restore', [BackupController::class, 'restoreStored'])->name('backups.restore-backup');
Route::post('backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');
