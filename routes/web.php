<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to shop
Route::get('/', function () {
    return redirect('/shop');
});

// Auth Routes
Route::get('/admin/login', [AuthController::class, 'adminLoginPage'])->name('admin.login');
Route::get('/shop/login', [AuthController::class, 'customerLoginPage'])->name('customer.login');

// Admin Routes (Protected by admin middleware)
Route::middleware(['admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('admin.products');
    Route::get('/products/create', [ProductController::class, 'create'])->name('admin.products.create');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    
    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory');
    Route::get('/inventory/stock-in', [InventoryController::class, 'stockInForm'])->name('admin.inventory.stock-in');
    Route::get('/inventory/stock-out', [InventoryController::class, 'stockOutForm'])->name('admin.inventory.stock-out');
    
    // Sales
    Route::get('/sales', [SalesController::class, 'index'])->name('admin.sales');
    Route::get('/sales/create', [SalesController::class, 'create'])->name('admin.sales.create');
    Route::get('/sales/{id}', [SalesController::class, 'show'])->name('admin.sales.show');
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('admin.reports.sales');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('admin.reports.inventory');
});
