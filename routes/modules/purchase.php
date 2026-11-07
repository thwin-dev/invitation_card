<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Purchase\Controllers\PurchaseWebController;
use App\Modules\Purchase\Controllers\SupplierWebController;

Route::get('suppliers', [SupplierWebController::class, 'index'])->name('suppliers.index');
Route::get('suppliers/{supplier}', [SupplierWebController::class, 'show'])->name('suppliers.show');
Route::post('suppliers/{supplier}/payments', [SupplierWebController::class, 'storePayment'])->name('suppliers.payments.store');
Route::delete('suppliers/{supplier}', [SupplierWebController::class, 'destroy'])->name('suppliers.destroy');

Route::get('purchases', [PurchaseWebController::class, 'index'])->name('purchases.index');
Route::get('purchases/export', [PurchaseWebController::class, 'export'])->name('purchases.export');
Route::get('purchases/create', [PurchaseWebController::class, 'create'])->name('purchases.create');
Route::post('purchases', [PurchaseWebController::class, 'store'])->name('purchases.store');
Route::get('purchases/{purchase}/edit', [PurchaseWebController::class, 'edit'])->name('purchases.edit');
Route::put('purchases/{purchase}', [PurchaseWebController::class, 'update'])->name('purchases.update');
Route::delete('purchases/{purchase}', [PurchaseWebController::class, 'destroy'])->name('purchases.destroy');
Route::get('purchases/{purchase}', [PurchaseWebController::class, 'show'])->name('purchases.show');
Route::get('purchases/{purchase}/print', [PurchaseWebController::class, 'print'])->name('purchases.print');
