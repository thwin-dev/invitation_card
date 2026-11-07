<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Expense\Controllers\PosExpenseWebController;

Route::get('expenses', [PosExpenseWebController::class, 'index'])->name('expenses.index');
Route::post('expenses', [PosExpenseWebController::class, 'store'])->name('expenses.store');
