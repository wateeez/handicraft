<?php
// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

require_once app_path('Helpers/functions.php');

$cartItems = [['product_id' => 1, 'quantity' => 1]];
$result1 = calculateCartShipping($cartItems, 1);

$cartItems5 = [['product_id' => 1, 'quantity' => 5]];
$result5 = calculateCartShipping($cartItems5, 1);

echo "QUANTITY_1: $result1\n";
echo "QUANTITY_5: $result5\n";
