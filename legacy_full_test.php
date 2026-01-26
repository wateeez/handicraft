<?php
// Load everything manually to verify legacy logic
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hmh'); // Assuming this is the DB name
define('CURRENCY_SYMBOL', '$');
define('PACKAGING_BUFFER', 8);
define('DIMENSIONAL_FACTOR', 5000);

require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';

echo "=== LEGACY CALCULATION TEST ===\n";

try {
    $cartItems1 = [['product_id' => 1, 'quantity' => 1]];
    $res1 = calculateCartShipping($cartItems1, 1);

    $cartItems5 = [['product_id' => 1, 'quantity' => 5]];
    $res5 = calculateCartShipping($cartItems5, 1);

    echo "RESULT_QTY_1: $res1\n";
    echo "RESULT_QTY_5: $res5\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
