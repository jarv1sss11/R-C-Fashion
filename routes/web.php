<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HealthController as AdminHealthController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RecommendationAnalyticsController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductCatalogueController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\StoreController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/password/forgot', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/password/forgot', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');

    Route::get('/account/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('/account/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::patch('/account/addresses/{address}/default', [AddressController::class, 'makeDefault'])->name('addresses.default');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/items/{cartItem}/increase', [CartController::class, 'increase'])->name('cart.increase');
    Route::post('/cart/items/{cartItem}/decrease', [CartController::class, 'decrease'])->name('cart.decrease');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Product Catalogue (Step 8) — customer-facing browsing, distinct from
    // the Vendor Module's management controllers.
    Route::get('/products', [ProductCatalogueController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductCatalogueController::class, 'show'])->name('products.show');
    Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/vendors/{vendor:store_slug}', [VendorController::class, 'show'])->name('vendors.show');

    Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');
    Route::get('/recommendations/click/{product}', [RecommendationController::class, 'click'])->name('recommendations.click');
});

Route::middleware(['auth', 'vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/store', [StoreController::class, 'edit'])->name('store.edit');
    Route::put('/store', [StoreController::class, 'update'])->name('store.update');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::delete('/products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');

    Route::get('/orders', [VendorOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [VendorOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/items/{orderItem}/fulfillment', [VendorOrderController::class, 'updateFulfillment'])->name('orders.fulfillment');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('/categories/{category}/restore', [AdminCategoryController::class, 'restore'])->name('categories.restore');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/activate', [AdminUserController::class, 'activate'])->name('users.activate');
    Route::post('/users/{user}/assign-admin', [AdminUserController::class, 'assignAdmin'])->name('users.assign-admin');

    Route::get('/vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/{vendor}', [AdminVendorController::class, 'show'])->name('vendors.show');
    Route::post('/vendors/{vendor}/approve', [AdminVendorController::class, 'approve'])->name('vendors.approve');
    Route::post('/vendors/{vendor}/reject', [AdminVendorController::class, 'reject'])->name('vendors.reject');
    Route::post('/vendors/{vendor}/suspend', [AdminVendorController::class, 'suspend'])->name('vendors.suspend');
    Route::post('/vendors/{vendor}/restore', [AdminVendorController::class, 'restore'])->name('vendors.restore');

    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::post('/products/bulk-approve', [AdminProductController::class, 'bulkApprove'])->name('products.bulk-approve');
    Route::post('/products/bulk-archive', [AdminProductController::class, 'bulkArchive'])->name('products.bulk-archive');
    Route::post('/products/bulk-delete', [AdminProductController::class, 'bulkDelete'])->name('products.bulk-delete');
    Route::post('/products/{product}/approve', [AdminProductController::class, 'approve'])->name('products.approve');
    Route::post('/products/{product}/reject', [AdminProductController::class, 'reject'])->name('products.reject');
    Route::post('/products/{product}/hide', [AdminProductController::class, 'hide'])->name('products.hide');
    Route::post('/products/{product}/archive', [AdminProductController::class, 'archive'])->name('products.archive');
    Route::post('/products/{product}/restore', [AdminProductController::class, 'restore'])->name('products.restore');

    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');

    Route::get('/recommendation-analytics', [RecommendationAnalyticsController::class, 'index'])->name('recommendation-analytics.index');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');

    Route::get('/health', [AdminHealthController::class, 'index'])->name('health.index');
});

// Destination for nav items that don't have a real page yet — shown to
// authenticated users instead of bouncing them back to /login.
Route::view('/coming-soon', 'auth.coming-soon', [
    'title' => 'This section is on its way',
    'message' => 'Recommendations and personalized discovery are being built in a later phase.',
])->name('coming-soon');
