<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

// Admin Auth routes (login/logout)
Route::get('/admin/login', [AdminController::class, 'login'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'loginPost'])->name('admin.login.post');
Route::get('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

// Admin Download CSV Template
Route::get('/admin/download-template', function () {
    $header = [
        'name', 'sku', 'category_slug', 'subcategory_slug', 'description', 'short_description',
        'price', 'sale_price', 'cost_price', 'stock_quantity', 'low_stock_threshold',
        'length', 'width', 'height', 'actual_weight', 'is_active', 'is_featured'
    ];
    $sampleData = [
        'Premium Wireless Mouse',
        'MOUSE-001',
        'electronics',
        'computer-accessories',
        'High-quality wireless mouse with ergonomic design',
        'Comfortable wireless mouse',
        '29.99',
        '24.99',
        '15.00',
        '100',
        '10',
        '10.5',
        '6.2',
        '3.8',
        '0.15',
        '1',
        '0'
    ];
    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, $header);
    fputcsv($handle, $sampleData);
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);
    return Response::make($csv, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="product_template.csv"',
    ]);
})->name('admin.download-template');

Route::get('/', function () {
    return redirect('/shop');
});

// Group all admin routes under AdminMiddleware
Route::middleware(['web', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/products', [AdminController::class, 'products'])->name('admin.products');
    Route::get('/admin/products/add', [AdminController::class, 'addProduct'])->name('admin.products.add');
    Route::get('/admin/products/edit/{id}', [AdminController::class, 'editProduct'])->name('admin.products.edit');
    Route::get('/admin/categories', [AdminController::class, 'categories'])->name('admin.categories');
    Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/admin/order-detail/{id}', [AdminController::class, 'orderDetail'])->name('admin.order-detail');
    Route::get('/admin/billing', [AdminController::class, 'billing'])->name('admin.billing');
    Route::get('/admin/shipping', [AdminController::class, 'shipping'])->name('admin.shipping');
    Route::get('/admin/products/bulk-upload', [AdminController::class, 'bulkUpload'])->name('admin.products.bulk');
    Route::post('/admin/products/bulk-upload', [AdminController::class, 'bulkUploadPost'])->name('admin.products.bulk.post');
    // Add more admin routes as needed
});
