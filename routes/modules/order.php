<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Order\Controllers\PosOrderWebController;

Route::get('orders', [PosOrderWebController::class, 'index'])->name('orders.index');
Route::get('orders/export', [PosOrderWebController::class, 'export'])->name('orders.export');
Route::get('orders/create', [PosOrderWebController::class, 'create'])->name('orders.create');
Route::post('orders', [PosOrderWebController::class, 'store'])->name('orders.store');
Route::get('orders/{order}/edit', [PosOrderWebController::class, 'edit'])->name('orders.edit');
Route::put('orders/{order}', [PosOrderWebController::class, 'update'])->name('orders.update');
Route::delete('orders/{order}', [PosOrderWebController::class, 'destroy'])->name('orders.destroy');
Route::get('orders/{order}', [PosOrderWebController::class, 'show'])->name('orders.show');
Route::get('orders/{order}/print', [PosOrderWebController::class, 'print'])->name('orders.print');
Route::put('orders/{order}/status', [PosOrderWebController::class, 'updateStatus'])->name('orders.status');
