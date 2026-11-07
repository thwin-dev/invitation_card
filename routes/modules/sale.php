<?php

use App\Modules\Sale\Controllers\OfflineSaleSyncController;
use App\Modules\Sale\Controllers\SaleWebController;

Route::get('sales/offline-catalog', [OfflineSaleSyncController::class, 'catalog'])->name('sales.offline-catalog');
Route::post('sales/sync-offline', [OfflineSaleSyncController::class, 'sync'])->name('sales.sync-offline');

Route::get('discounts', [SaleWebController::class, 'discounts'])->name('discounts.index');

Route::get('sales', [SaleWebController::class, 'index'])->name('sales.index');
Route::get('sales/export', [SaleWebController::class, 'export'])->name('sales.export');
Route::get('sales/create', [SaleWebController::class, 'create'])->name('sales.create');
Route::post('sales', [SaleWebController::class, 'store'])->name('sales.store');
Route::get('sales/{sale}/edit', [SaleWebController::class, 'edit'])->name('sales.edit');
Route::put('sales/{sale}', [SaleWebController::class, 'update'])->name('sales.update');
Route::get('sales/{sale}', [SaleWebController::class, 'show'])->name('sales.show');
Route::put('sales/{sale}/cancel', [SaleWebController::class, 'cancel'])->name('sales.cancel');
Route::get('sales/{sale}/print', [SaleWebController::class, 'print'])->name('sales.print');
