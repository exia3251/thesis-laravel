<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\ReportController;

// Admin login
Route::get('/admin/login', [AuthController::class, 'adminLoginPage'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

// Admin routes (protected)
Route::middleware('admin')->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    Route::get('/products', [ProductController::class, 'index'])->name('admin.products');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory');
    Route::get('/sales', [SalesController::class, 'index'])->name('admin.sales');
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('admin.suppliers');
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
});

// Customer routes (in separate file)
require __DIR__.'/customer.php';