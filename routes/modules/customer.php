<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Customer\Controllers\PosCustomerWebController;

// Exhibitor Routes (Primary)
Route::get('exhibitors', [PosCustomerWebController::class, 'index'])->name('exhibitors.index');
Route::get('exhibitors/sample-excel', [PosCustomerWebController::class, 'sampleExcel'])->name('exhibitors.sample-excel');
Route::post('exhibitors/import', [PosCustomerWebController::class, 'import'])->name('exhibitors.import');
Route::get('exhibitors/{customer}', [PosCustomerWebController::class, 'show'])->name('exhibitors.show');
Route::get('exhibitors/{customer}/card', [PosCustomerWebController::class, 'invitationCard'])->name('exhibitors.card');
Route::post('exhibitors', [PosCustomerWebController::class, 'store'])->name('exhibitors.store');
Route::put('exhibitors/{customer}', [PosCustomerWebController::class, 'update'])->name('exhibitors.update');
Route::put('exhibitors/{customer}/level', [PosCustomerWebController::class, 'updateLevel'])->name('exhibitors.level.update');
Route::delete('exhibitors/{customer}', [PosCustomerWebController::class, 'destroy'])->name('exhibitors.destroy');

// Customer Routes (Aliases for backward compatibility)
Route::get('customers', [PosCustomerWebController::class, 'index'])->name('customers.index');
Route::get('customers/sample-excel', [PosCustomerWebController::class, 'sampleExcel'])->name('customers.sample-excel');
Route::post('customers/import', [PosCustomerWebController::class, 'import'])->name('customers.import');
Route::get('customers/{customer}', [PosCustomerWebController::class, 'show'])->name('customers.show');
Route::get('customers/{customer}/card', [PosCustomerWebController::class, 'invitationCard'])->name('customers.card');
Route::post('customers', [PosCustomerWebController::class, 'store'])->name('customers.store');
Route::put('customers/{customer}', [PosCustomerWebController::class, 'update'])->name('customers.update');
Route::put('customers/{customer}/level', [PosCustomerWebController::class, 'updateLevel'])->name('customers.level.update');
Route::delete('customers/{customer}', [PosCustomerWebController::class, 'destroy'])->name('customers.destroy');
