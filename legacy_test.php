<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';

// Mock Constants
define('CURRENCY_SYMBOL', '$');
define('PACKAGING_BUFFER', 8);
define('DIMENSIONAL_FACTOR', 5000);

$cartItems = [['product_id' => 1, 'quantity' => 1]];
$result1 = calculateCartShipping($cartItems, 1);

$cartItems5 = [['product_id' => 1, 'quantity' => 5]];
$result5 = calculateCartShipping($cartItems5, 1);

echo "LEGACY_QUANTITY_1: $result1\n";
echo "LEGACY_QUANTITY_5: $result5\n";
