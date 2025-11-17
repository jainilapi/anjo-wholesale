<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes(['register' => false]);
Route::redirect('/', 'login');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', \App\Http\Controllers\UserController::class);
    Route::resource('roles', \App\Http\Controllers\RoleController::class);
    Route::resource('customers', \App\Http\Controllers\CustomerController::class);
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
    Route::resource('locations', \App\Http\Controllers\WarehouseLocationController::class);
    Route::resource('customer-locations', \App\Http\Controllers\LocationController::class);
    Route::resource('warehouses', \App\Http\Controllers\WarehouseController::class);
    Route::resource('categories', \App\Http\Controllers\CategoryController::class);
    Route::resource('products', \App\Http\Controllers\ProductController::class);
    Route::resource('brands', \App\Http\Controllers\BrandController::class);

    Route::any('product-management/{type?}/{step?}/{id?}', [\App\Http\Controllers\ProductController::class, 'steps'])->name('product-management');

    Route::post('state-list', [\App\Helpers\Helper::class, 'getStatesByCountry'])->name('state-list');
    Route::post('city-list', [\App\Helpers\Helper::class, 'getCitiesByState'])->name('city-list');
    Route::post('brand-list', [\App\Helpers\Helper::class, 'getBrands'])->name('brand-list');
    Route::post('product-image-delete', [\App\Http\Controllers\ProductController::class, 'deleteImage'])->name('product-image-delete');

    Route::get('settings', [App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');    
});
