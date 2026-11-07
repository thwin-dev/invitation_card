<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Report\Controllers\PosDashboardWebController;

Route::get('reports/financial', [PosDashboardWebController::class, 'report'])->name('reports.financial');
Route::get('reports/sales', [PosDashboardWebController::class, 'salesReport'])->name('reports.sales');
Route::get('reports/purchases', [PosDashboardWebController::class, 'purchasesReport'])->name('reports.purchases');
Route::get('reports/inventory-valuation', [PosDashboardWebController::class, 'inventoryValuationReport'])->name('reports.inventory-valuation');

