<?php

use App\Http\Controllers\PosWebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth('web')->check()
        ? to_route('management.exhibitors.index')
        : to_route('login');
});

Route::get('localization/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'my'], true), 404);
    session(['locale' => $locale]);
    return back();
})->name('localization');

Route::get('login', [PosWebAuthController::class, 'showLogin'])->name('login');
Route::post('login', [PosWebAuthController::class, 'login'])->name('login.submit');
Route::post('logout', [PosWebAuthController::class, 'logout'])->middleware('auth:web')->name('logout');

// Public invitation card link
Route::get('invitation/{customer}', [App\Modules\Customer\Controllers\PosCustomerWebController::class, 'invitationCard'])->name('invitation.card');

Route::middleware('auth:web')->group(function (): void {
    Route::get('change-password', [PosWebAuthController::class, 'showChangePassword'])->name('management.password.edit');
    Route::put('change-password', [PosWebAuthController::class, 'updatePassword'])->name('management.password.update');

    Route::middleware('management.permission')->group(function (): void {
        Route::get('management', fn () => to_route('management.exhibitors.index'))->name('management.dashboard');

        Route::prefix('management')->name('management.')->group(function (): void {
            require __DIR__.'/modules/customer.php';
        });
    });
});
