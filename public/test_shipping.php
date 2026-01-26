<?php
// PHP Script to diagnose shipping calculation in browser context
require_once __DIR__ . '/../includes/init.php';

echo "<h1>Shipping Diagnostics</h1>";

$func = new ReflectionFunction('calculateCartShipping');
echo "<p>Function <b>calculateCartShipping</b> defined in: " . $func->getFileName() . " at line " . $func->getStartLine() . "</p>";

// Dump actual code of the function
$start_line = $func->getStartLine() - 1;
$end_line = $func->getEndLine();
$length = $end_line - $start_line;
$source = file($func->getFileName());
$code = implode("", array_slice($source, $start_line, $length));

echo "<h2>Function Source:</h2>";
echo "<pre>" . htmlspecialchars($code) . "</pre>";

// Test calculation
$db = Database::getInstance();
$product = $db->fetchOne("SELECT id, actual_weight, name FROM products WHERE actual_weight > 0 LIMIT 1");

if ($product) {
    echo "<h3>Test with Product: " . $product['name'] . " (" . $product['actual_weight'] . " kg)</h3>";

    // Manual cart item with quantity 5
    $cartItems = [
        [
            'product_id' => $product['id'],
            'quantity' => 1 // If weight is 5, and it returns 75, it's multiplying 5*15
        ]
    ];

    // Let's try to simulate 5kg product
    echo "<p>Simulating 5kg product calculation...</p>";

    // We'll mock the cart item with a 5kg product
    $shippingMethodId = 1;
    $cost = calculateCartShipping($cartItems, $shippingMethodId);

    echo "<p>Result for 1 quantity of " . $product['actual_weight'] . "kg product: <b>$" . $cost . "</b></p>";

    $cartItems5 = [
        [
            'product_id' => $product['id'],
            'quantity' => 5
        ]
    ];
    $cost5 = calculateCartShipping($cartItems5, $shippingMethodId);
    echo "<p>Result for 5 quantity of " . $product['actual_weight'] . "kg product: <b>$" . $cost5 . "</b></p>";
}

echo "<h2>Database Rates (Method 1)</h2>";
$rates = $db->fetchAll("SELECT * FROM shipping_rates WHERE shipping_method_id = 1");
echo "<table border='1'><tr><th>Min</th><th>Max</th><th>Price Actual</th></tr>";
foreach ($rates as $r) {
    echo "<tr><td>{$r['min_weight']}</td><td>{$r['max_weight']}</td><td>{$r['price_per_kg_actual']}</td></tr>";
}
echo "</table>";
