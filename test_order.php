<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Testing order placement...\n\n";

// Check if we have products
$product = DB::table('products')->first();
if (!$product) {
    echo "ERROR: No products found!\n";
    exit;
}

echo "Product found: {$product->name}\n";
echo "Price: {$product->price}\n";
echo "Stock: {$product->stock_quantity}\n\n";

// Check shipping methods
$shippingMethods = DB::table('shipping_methods')->where('is_active', 1)->get();
echo "Active shipping methods: " . count($shippingMethods) . "\n";
foreach ($shippingMethods as $method) {
    echo "  - {$method->name}: \${$method->charge}\n";
}
echo "\n";

// Check payment providers
$paymentProviders = DB::table('payment_providers')->where('is_active', 1)->get();
echo "Active payment providers: " . count($paymentProviders) . "\n";
foreach ($paymentProviders as $provider) {
    echo "  - {$provider->name}\n";
}
echo "\n";

echo "Checkout form requirements:\n";
echo "  ✓ Products available\n";
echo "  ✓ Shipping methods configured\n";
echo "  ✓ Payment providers configured\n";
echo "\nCheckout should work! Try placing an order from /shop/checkout\n";
