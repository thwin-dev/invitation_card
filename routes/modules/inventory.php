<?php

use App\Modules\Inventory\Controllers\ProductUnitRelationWebController;
use App\Modules\Inventory\Controllers\StockMovementWebController;
use App\Modules\Inventory\Controllers\StockWebController;
use Illuminate\Support\Facades\Route;

Route::get('product-unit-relations', [ProductUnitRelationWebController::class, 'index'])->name('product-unit-relations.index');
Route::post('product-unit-relations', [ProductUnitRelationWebController::class, 'storeRelation'])->name('product-unit-relations.store');
Route::post('product-unit-relations/convert', [ProductUnitRelationWebController::class, 'convert'])->name('product-unit-relations.convert');
Route::delete('product-unit-relations/{relation}', [ProductUnitRelationWebController::class, 'destroy'])->name('product-unit-relations.destroy');
Route::get('stock', [StockWebController::class, 'index'])->name('stock.index');
Route::get('stock/movements', [StockMovementWebController::class, 'index'])->name('stock.movements');
Route::put('stock/{unit}', [StockWebController::class, 'update'])->name('stock.update');

Route::get('inventory-operations', [StockWebController::class, 'operations'])->name('inventory.operations');
Route::get('branches/prices', [StockWebController::class, 'branchPrices'])->name('branches.prices');
Route::get('branches', [StockWebController::class, 'branches'])->name('branches.index');
