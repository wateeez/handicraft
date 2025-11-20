<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$orderId = DB::table('orders')->insertGetId([
    'order_number' => 'ORD-TEST123',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'customer_phone' => '1234567890',
    'billing_address' => '123 Main St',
    'billing_city' => 'New York',
    'billing_state' => 'NY',
    'billing_zip' => '10001',
    'billing_country' => 'USA',
    'shipping_address' => '123 Main St',
    'shipping_city' => 'New York',
    'shipping_state' => 'NY',
    'shipping_zip' => '10001',
    'shipping_country' => 'USA',
    'subtotal' => 100.00,
    'tax_amount' => 10.00,
    'shipping_cost' => 5.00,
    'discount_amount' => 0.00,
    'total_amount' => 115.00,
    'payment_status' => 'Pending',
    'status' => 'Pending',
    'notes' => 'Test order',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Test order created with ID: $orderId\n";

// Get product for order item
$product = DB::table('products')->first();

if ($product) {
    DB::table('order_items')->insert([
        'order_id' => $orderId,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'price' => $product->price,
        'subtotal' => $product->price * 2,
        'created_at' => now(),
    ]);
    echo "Order item added for product: {$product->name}\n";
}

echo "\nOrders count: " . DB::table('orders')->count() . "\n";
