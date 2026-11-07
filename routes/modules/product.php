<?php

use App\Modules\Inventory\Controllers\ProductUnitWebController;
use App\Modules\Product\Controllers\ProductWebController;
use App\Modules\ProductCategory\Controllers\ProductCategoryWebController;
use App\Modules\ProductCategory\Controllers\ProductSubcategoryWebController;
use Illuminate\Support\Facades\Route;

Route::get('product-categories', [ProductCategoryWebController::class, 'index'])->name('product-categories.index');
Route::post('product-categories', [ProductCategoryWebController::class, 'store'])->name('product-categories.store');
Route::put('product-categories/{category}', [ProductCategoryWebController::class, 'update'])->name('product-categories.update');
Route::put('product-categories/{category}/toggle', [ProductCategoryWebController::class, 'toggle'])->name('product-categories.toggle');
Route::delete('product-categories/{category}', [ProductCategoryWebController::class, 'destroy'])->name('product-categories.destroy');
Route::get('product-subcategories', [ProductSubcategoryWebController::class, 'index'])->name('product-subcategories.index');
Route::post('product-subcategories', [ProductSubcategoryWebController::class, 'store'])->name('product-subcategories.store');
Route::put('product-subcategories/{subcategory}', [ProductSubcategoryWebController::class, 'update'])->name('product-subcategories.update');
Route::put('product-subcategories/{subcategory}/toggle', [ProductSubcategoryWebController::class, 'toggle'])->name('product-subcategories.toggle');
Route::delete('product-subcategories/{subcategory}', [ProductSubcategoryWebController::class, 'destroy'])->name('product-subcategories.destroy');
Route::get('products', [ProductWebController::class, 'index'])->name('products.index');
Route::post('products', [ProductWebController::class, 'store'])->name('products.store');
Route::put('products/{product}', [ProductWebController::class, 'update'])->name('products.update');
Route::delete('products/{product}', [ProductWebController::class, 'destroy'])->name('products.destroy');
Route::get('products/{product}/photo', [ProductWebController::class, 'photo'])->name('products.photo');
Route::post('products/{product}/photo', [ProductWebController::class, 'updatePhoto'])->name('products.photo.update');
Route::delete('products/{product}/photo', [ProductWebController::class, 'deletePhoto'])->name('products.photo.delete');
Route::get('product-units', [ProductUnitWebController::class, 'index'])->name('product-units.index');
Route::post('product-units', [ProductUnitWebController::class, 'store'])->name('product-units.store');
Route::put('product-units/{unit}', [ProductUnitWebController::class, 'update'])->name('product-units.update');
Route::delete('product-units/{unit}', [ProductUnitWebController::class, 'destroy'])->name('product-units.destroy');
Route::put('product-units/{unit}/code', [ProductUnitWebController::class, 'updateCode'])->name('product-units.code.update');
Route::put('product-units/{unit}/original-code', [ProductUnitWebController::class, 'updateOriginalCode'])->name('product-units.original-code.update');

Route::get('brands', [ProductWebController::class, 'brands'])->name('brands.index');

Route::get('product-units/{unit}/barcode', [ProductUnitWebController::class, 'barcode'])->name('product-units.barcode');
