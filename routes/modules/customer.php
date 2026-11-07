<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Customer\Controllers\PosCustomerWebController;

Route::get('customers', [PosCustomerWebController::class, 'index'])->name('customers.index');
Route::get('customers/{customer}', [PosCustomerWebController::class, 'show'])->name('customers.show');
Route::post('customers', [PosCustomerWebController::class, 'store'])->name('customers.store');
Route::put('customers/{customer}', [PosCustomerWebController::class, 'update'])->name('customers.update');
Route::put('customers/{customer}/level', [PosCustomerWebController::class, 'updateLevel'])->name('customers.level.update');
Route::put('customer-credits/{credit}/repay', [PosCustomerWebController::class, 'repayCredit'])->name('customer-credits.repay');
Route::post('customers/{customer}/payments', [PosCustomerWebController::class, 'storePayment'])->name('customer-payments.store');
Route::get('customers/{customer}/payments/{payment}/receipt', [PosCustomerWebController::class, 'paymentReceipt'])->name('customer-payments.receipt');
Route::get('customers/{customer}/statement', [PosCustomerWebController::class, 'statement'])->name('customers.statement');
Route::delete('customers/{customer}', [PosCustomerWebController::class, 'destroy'])->name('customers.destroy');
