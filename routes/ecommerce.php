<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| E-Commerce Routes
|--------------------------------------------------------------------------
*/

// Frontend Routes
Route::prefix('shop')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('shop.home');
    Route::get('/products', [ShopController::class, 'products'])->name('shop.products');
    Route::get('/product/{slug}', [ShopController::class, 'productDetail'])->name('shop.product');
    Route::get('/cart', [ShopController::class, 'cart'])->name('shop.cart');
    Route::post('/cart/update', [ShopController::class, 'updateCart'])->name('shop.cart.update');
    Route::post('/cart/remove', [ShopController::class, 'removeFromCart'])->name('shop.cart.remove');
    Route::get('/checkout', [ShopController::class, 'checkout'])->name('shop.checkout');
    Route::post('/checkout', [ShopController::class, 'processCheckout'])->name('shop.checkout.post');
    Route::get('/checkout/success', [ShopController::class, 'checkoutSuccess'])->name('shop.checkout.success');
    Route::get('/blog', [ShopController::class, 'blog'])->name('shop.blog');
    Route::get('/blog/{slug}', [ShopController::class, 'blogDetail'])->name('shop.blog.detail');
    Route::get('/faq', [ShopController::class, 'faq'])->name('shop.faq');
    Route::get('/contact', [ShopController::class, 'contact'])->name('shop.contact');
    Route::get('/about', [ShopController::class, 'about'])->name('shop.about');

    // API Routes
    Route::post('/api/cart/add', [ShopController::class, 'addToCart'])->name('api.cart.add');
    Route::get('/api/cart/count', [ShopController::class, 'cartCount'])->name('api.cart.count');
    Route::post('/api/coupon/apply', [ShopController::class, 'applyCoupon'])->name('api.coupon.apply');
});

// Admin Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('admin.login');
    Route::post('/login', [AdminController::class, 'loginPost'])->name('admin.login.post');
    Route::get('/logout', [AdminController::class, 'logout'])->name('admin.logout');

    Route::middleware(['admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

        // Products
        Route::get('/products', [AdminController::class, 'products'])->name('admin.products');
        Route::get('/products/add', [AdminController::class, 'addProduct'])->name('admin.products.add');
        Route::post('/products/add', [AdminController::class, 'addProductPost'])->name('admin.products.add.post');
        Route::get('/products/edit/{id}', [AdminController::class, 'editProduct'])->name('admin.products.edit');
        Route::post('/products/edit', [AdminController::class, 'editProductPost'])->name('admin.products.edit.post');
        Route::post('/products/delete', [AdminController::class, 'deleteProduct'])->name('admin.products.delete');
        Route::get('/products/export', [AdminController::class, 'exportProducts'])->name('admin.products.export');

        // Bulk Upload
        Route::get('/products/bulk-upload', [AdminController::class, 'bulkUpload'])->name('admin.products.bulk');
        Route::post('/products/bulk-upload', [AdminController::class, 'bulkUploadPost'])->name('admin.products.bulk.post');
        Route::get('/products/download-template', [AdminController::class, 'downloadTemplate'])->name('admin.products.template');

        // Categories
        Route::get('/categories', [AdminController::class, 'categories'])->name('admin.categories');
        Route::post('/categories', [AdminController::class, 'addCategory'])->name('admin.categories.add');
        Route::post('/categories/delete', [AdminController::class, 'deleteCategory'])->name('admin.categories.delete');
        Route::post('/subcategories', [AdminController::class, 'addSubcategory'])->name('admin.subcategories.add');
        Route::post('/subcategories/delete', [AdminController::class, 'deleteSubcategory'])->name('admin.subcategories.delete');
        Route::get('/get-subcategories', [AdminController::class, 'getSubcategories'])->name('admin.get.subcategories');

        // Orders
        Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
        Route::get('/orders/{id}', [AdminController::class, 'orderDetail'])->name('admin.orders.detail');
        Route::post('/orders/update-status', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.update');
        Route::post('/orders/update-payment-status', [AdminController::class, 'updatePaymentStatus'])->name('admin.orders.update.payment');

        // Billing
        Route::get('/billing', [AdminController::class, 'billing'])->name('admin.billing');
        Route::get('/billing/export', [AdminController::class, 'exportBilling'])->name('admin.billing.export');

        // Shipping
        Route::get('/shipping', [AdminController::class, 'shipping'])->name('admin.shipping');
        Route::post('/shipping', [AdminController::class, 'addShippingZone'])->name('admin.shipping.add');
        Route::post('/shipping/rate', [AdminController::class, 'addShippingRate'])->name('admin.shipping.rate.add');
        Route::post('/shipping/delete', [AdminController::class, 'deleteShippingZone'])->name('admin.shipping.delete');
        Route::post('/shipping/rate/delete', [AdminController::class, 'deleteShippingRate'])->name('admin.shipping.rate.delete');
    });
});
