<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\ChatbotAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\ChatbotController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Frontend Views
|--------------------------------------------------------------------------
*/

// Redirect root to shop
Route::get('/', function () {
    return redirect('/shop');
});

// Admin authentication
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', function () {
        return view('admin.login');
    })->name('admin.login');
});

Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login.submit');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Back office
|--------------------------------------------------------------------------
| The admin middleware lets any active staff member in. The permission
| middleware decides which of them may see each page.
*/
Route::middleware(['admin', 'active_session'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:full_dashboard')->name('admin.dashboard');

    Route::get('/products', [ProductController::class, 'index'])
        ->middleware('permission:manage_products')->name('admin.products');

    Route::get('/inventory', [InventoryController::class, 'index'])
        ->middleware('permission:manage_inventory')->name('admin.inventory');

    Route::get('/sales', [SalesController::class, 'index'])
        ->middleware('permission:view_sales')->name('admin.sales');

    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->middleware('permission:full_dashboard')->name('admin.analytics');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:view_reports')->name('admin.reports');

    Route::get('/users', [UserManagementController::class, 'index'])
        ->middleware('permission:manage_users')->name('admin.users');

    Route::get('/backup', [BackupController::class, 'index'])
        ->middleware('permission:backup_database')->name('admin.backup');

    Route::get('/assistant', [ChatbotAdminController::class, 'index'])
        ->middleware('permission:manage_chatbot')->name('admin.assistant');

    // No permission gate: every staff role manages its own account, which is
    // the whole point of it existing.
    Route::get('/account', [AccountController::class, 'index'])->name('admin.account');
});

// Session-backed admin endpoints
Route::middleware(['admin', 'active_session'])->prefix('admin-api')->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])
        ->middleware('permission:full_dashboard');

    Route::get('/analytics', [AnalyticsController::class, 'data'])
        ->middleware('permission:full_dashboard');

    Route::middleware('permission:manage_products')->group(function () {
        Route::get('/products', [ProductController::class, 'getProducts']);
        Route::get('/products/{id}', [ProductController::class, 'getProduct']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/products/import', [ProductController::class, 'importCatalog']);
        Route::post('/products/{id}', [ProductController::class, 'update']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::post('/products/{id}/restore', [ProductController::class, 'restore']);
    });

    Route::middleware('permission:manage_inventory')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'getInventory']);
        Route::post('/inventory/stock-in', [InventoryController::class, 'stockIn']);
        Route::post('/inventory/stock-out', [InventoryController::class, 'stockOut']);
        Route::get('/inventory/transactions/{productId}', [InventoryController::class, 'getTransactions']);
    });

    // Reading sales history is open to every staff role.
    Route::middleware('permission:view_sales')->group(function () {
        Route::get('/sales', [SalesController::class, 'getSales']);
        Route::get('/sales/{id}', [SalesController::class, 'getSale']);
        Route::get('/sales/products/list', [SalesController::class, 'getProducts']);
    });

    // Moving money or delivery state is not.
    Route::middleware('permission:create_sales')->group(function () {
        Route::post('/sales', [SalesController::class, 'store']);
        Route::put('/sales/{id}/status', [SalesController::class, 'updateStatus']);
        Route::post('/sales/{id}/cancel', [SalesController::class, 'cancelSale']);
        Route::put('/sales/{id}/refund', [SalesController::class, 'recordRefund']);
        Route::post('/sales/{id}/delivery-proof', [SalesController::class, 'uploadDeliveryProof']);
        Route::put('/payment-requests/{id}/approve', [SalesController::class, 'approvePaymentRequest']);
        Route::put('/payment-requests/{id}/reject', [SalesController::class, 'rejectPaymentRequest']);
    });

    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/reports/sales', [ReportController::class, 'salesReport']);
        Route::get('/reports/inventory', [ReportController::class, 'inventoryReport']);
        Route::get('/reports/sales/export', [ReportController::class, 'exportSalesCsv']);
        Route::get('/reports/inventory/export', [ReportController::class, 'exportInventoryCsv']);
    });

    Route::middleware('permission:manage_users')->group(function () {
        Route::get('/users', [UserManagementController::class, 'getUsers']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::put('/users/{id}', [UserManagementController::class, 'update']);
        Route::delete('/users/{id}', [UserManagementController::class, 'destroy']);
        Route::post('/users/{id}/restore', [UserManagementController::class, 'restore']);
    });

    Route::get('/account', [AccountController::class, 'show']);
    Route::post('/account', [AccountController::class, 'update']);
    Route::delete('/account/avatar', [AccountController::class, 'removeAvatar']);
    Route::post('/account/password', [AccountController::class, 'password']);

    Route::get('/logs', [UserManagementController::class, 'getLogs'])
        ->middleware('permission:view_logs');

    Route::middleware('permission:manage_chatbot')->group(function () {
        Route::get('/assistant/intents', [ChatbotAdminController::class, 'intents']);
        Route::put('/assistant/intents/{id}', [ChatbotAdminController::class, 'update']);
        Route::get('/assistant/unanswered', [ChatbotAdminController::class, 'unanswered']);
        Route::get('/assistant/vehicles', [ChatbotAdminController::class, 'vehicles']);
        Route::put('/assistant/vehicles/{id}', [ChatbotAdminController::class, 'updateVehicle']);
    });

    Route::middleware('permission:backup_database')->group(function () {
        Route::get('/backups', [BackupController::class, 'list']);
        Route::post('/backups', [BackupController::class, 'store']);
        Route::get('/backups/{filename}/download', [BackupController::class, 'download']);
        Route::delete('/backups/{filename}', [BackupController::class, 'destroy']);
    });
});

// Customer authentication
Route::middleware('guest')->group(function () {
    Route::get('/shop/login', function () {
        return view('customer.login');
    })->name('customer.login');
    Route::get('/shop/register', function () {
        return view('customer.register');
    })->name('customer.register');
});

Route::post('/shop/login', [AuthController::class, 'customerLogin'])->name('customer.login.submit');
Route::post('/shop/register', [AuthController::class, 'customerRegister'])->name('customer.register.submit');
Route::post('/shop/logout', [AuthController::class, 'logout'])->name('customer.logout');

/*
| Email verification. The confirmation link carries a signature rather than
| relying on a session, so it still works when the mail is opened on a
| different device from the one that registered.
*/
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::post('/email/verify/resend', [EmailVerificationController::class, 'resend'])
    ->middleware(['customer', 'throttle:5,1'])
    ->name('verification.send');

// Customer pages
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/shop/products/{id}', [ShopController::class, 'show'])->name('shop.products.show');

/*
| The storefront assistant. Open to signed-out visitors, since most of what it
| answers is asked before anyone has an account; questions about a specific
| order are refused inside the responder unless there is a signed-in user.
*/
Route::middleware('throttle:30,1')->prefix('shop-api/chat')->group(function () {
    Route::get('/', [ChatbotController::class, 'open']);
    Route::post('/', [ChatbotController::class, 'send']);
    Route::post('/reset', [ChatbotController::class, 'reset']);
});

// The catalogue is readable without signing in, so these two carry their own
// rate limit rather than relying on an authenticated session to bound them.
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/shop-api/products', [ShopController::class, 'getProducts']);
    Route::get('/shop-api/featured-products', [ShopController::class, 'getFeaturedProducts']);
});

Route::middleware(['customer', 'active_session'])->group(function () {
    Route::get('/cart', function () {
        return view('customer.cart');
    })->name('cart');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
});

// Session-backed customer endpoints
Route::middleware(['customer', 'active_session'])->prefix('shop-api')->group(function () {
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::put('/cart/{id}', [CartController::class, 'updateCart']);
    Route::delete('/cart/{id}', [CartController::class, 'removeFromCart']);
    Route::delete('/cart/clear', [CartController::class, 'clearCart']);

    Route::post('/orders', [OrderController::class, 'placeOrder']);
    Route::get('/orders', [OrderController::class, 'getOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'getOrderDetails']);
    Route::post('/orders/{id}/payment-requests', [OrderController::class, 'submitPaymentRequest']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
    Route::post('/orders/{id}/receipt', [OrderController::class, 'confirmReceipt']);

    Route::get('/profile', [ProfileController::class, 'getProfile']);
    Route::put('/profile', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/password', [ProfileController::class, 'changePassword']);
});
