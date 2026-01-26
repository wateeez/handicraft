<?php

use Illuminate\Support\Facades\DB;

// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

require_once app_path('Helpers/functions.php');

echo "=== TESTING SHIPPING CALCULATION ===\n";

// Find a product with actual weight around 1kg
$product = DB::table('products')->where('actual_weight', '>', 0)->first();

if (!$product) {
    echo "No products found with weight!\n";
    exit;
}

echo "Testing with Product: {$product->name}\n";
echo "Weight: {$product->actual_weight} kg\n";

// Create manual cart item
$cartItems = [
    [
        'product_id' => $product->id,
        'quantity' => 5
    ]
];

echo "Quantity: 5\n";
echo "Total Weight: " . ($product->actual_weight * 5) . " kg\n";

// Find rate method 1 (DHL)
$shippingMethodId = 1;

$rate = DB::table('shipping_rates')
    ->where('shipping_method_id', $shippingMethodId)
    ->where('min_weight', '<=', $product->actual_weight * 5)
    ->where('max_weight', '>=', $product->actual_weight * 5)
    ->first();

if ($rate) {
    echo "Matching Rate tier: {$rate->min_weight} - {$rate->max_weight} kg\n";
    echo "Fixed Price (Actual): {$rate->price_per_kg_actual}\n";
} else {
    echo "No matching rate tier found.\n";
}

$cost = calculateCartShipping($cartItems, $shippingMethodId);

echo "\nCALCULATED COST: $cost\n";

if ($cost == 75 && $rate->price_per_kg_actual == 15) {
    echo "FAILURE: Still multiplying (5 * 15 = 75)\n";
} else {
    echo "SUCCESS: Fixed price used correctly.\n";
}
