<?php

use App\Http\Controllers\PosUserController;

Route::get('users', [PosUserController::class, 'index'])->name('users.index');
Route::get('users/{user}', [PosUserController::class, 'show'])->name('users.show');
Route::post('users', [PosUserController::class, 'store'])->name('users.store');
Route::put('users/{user}', [PosUserController::class, 'update'])->name('users.update');
Route::put('users/{user}/status', [PosUserController::class, 'toggle'])->name('users.status');
