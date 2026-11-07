<?php

use App\Http\Controllers\EventDetailController;
use Illuminate\Support\Facades\Route;

Route::get('events', [EventDetailController::class, 'index'])->name('events.index');
Route::put('events', [EventDetailController::class, 'update'])->name('events.update');
