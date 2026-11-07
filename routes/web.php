<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ProductCategory\Controllers\ProductCategoryWebController;
use App\Modules\ProductCategory\Controllers\ProductSubcategoryWebController;
use App\Modules\Product\Controllers\ProductWebController;
use App\Modules\Inventory\Controllers\ProductUnitWebController;
use App\Modules\Inventory\Controllers\ProductUnitRelationWebController;
use App\Modules\Customer\Controllers\PosCustomerWebController;
use App\Modules\Purchase\Controllers\PurchaseWebController;
use App\Modules\Sale\Controllers\SaleWebController;
use App\Modules\Order\Controllers\PosOrderWebController;
use App\Modules\Expense\Controllers\PosExpenseWebController;
use App\Modules\Inventory\Controllers\StockWebController;
use App\Modules\Inventory\Controllers\StockMovementWebController;
use App\Modules\Report\Controllers\PosDashboardWebController;
use App\Http\Controllers\PosWebAuthController;
use App\Http\Controllers\PosUserController;
use App\Modules\Product\Models\Product;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Sale\Models\Sale;

Route::get('/', function () {
    return to_route('login');
});
Route::get('localization/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'my'], true), 404);
    session(['locale' => $locale]);
    return back();
})->name('localization');

Route::get('login', [PosWebAuthController::class, 'showLogin'])->name('login');
Route::post('login', [PosWebAuthController::class, 'login'])->name('login.submit');
Route::post('logout', [PosWebAuthController::class, 'logout'])->middleware('auth:web')->name('logout');

Route::middleware('auth:web')->group(function (): void {
Route::get('change-password', [PosWebAuthController::class, 'showChangePassword'])->name('management.password.edit');
Route::put('change-password', [PosWebAuthController::class, 'updatePassword'])->name('management.password.update');

Route::middleware('management.permission')->group(function (): void {
Route::get('management', [PosDashboardWebController::class, 'index'])->name('management.dashboard');

Route::prefix('management')->name('management.')->group(function (): void {
    require __DIR__.'/modules/product.php';
    require __DIR__.'/modules/sale.php';
    require __DIR__.'/modules/order.php';
    require __DIR__.'/modules/customer.php';
    require __DIR__.'/modules/purchase.php';
    require __DIR__.'/modules/inventory.php';
    require __DIR__.'/modules/expense.php';
    require __DIR__.'/modules/report.php';
    require __DIR__.'/modules/user.php';
    require __DIR__.'/modules/security.php';
});
});

});
