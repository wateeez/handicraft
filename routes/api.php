<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ShippingCalculatorController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Shipping Calculator API Routes
Route::prefix('shipping')->group(function () {
    // Calculate shipping for a single product
    Route::post('/calculate', [ShippingCalculatorController::class, 'calculate'])
        ->name('api.shipping.calculate');

    // Calculate shipping for multiple items (cart)
    Route::post('/calculate-cart', [ShippingCalculatorController::class, 'calculateCart'])
        ->name('api.shipping.calculate-cart');

    // Get available shipping methods with rates
    Route::get('/methods', [ShippingCalculatorController::class, 'getMethods'])
        ->name('api.shipping.methods');

    // Quick quote (lightweight calculation)
    Route::get('/quick-quote', [ShippingCalculatorController::class, 'quickQuote'])
        ->name('api.shipping.quick-quote');
});
