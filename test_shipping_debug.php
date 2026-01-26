<?php
require_once __DIR__ . '/includes/init.php';

echo "=== SHIPPING RATES IN DATABASE ===\n\n";

$db = Database::getInstance();
$rates = $db->fetchAll("SELECT sr.*, sm.name as method_name 
                        FROM shipping_rates sr 
                        JOIN shipping_methods sm ON sr.shipping_method_id = sm.id 
                        ORDER BY sm.id, sr.min_weight");

foreach ($rates as $rate) {
    echo "Method: {$rate['method_name']}\n";
    echo "  Weight Range: {$rate['min_weight']} - {$rate['max_weight']} kg\n";
    echo "  Price (Actual): \${$rate['price_per_kg_actual']}\n";
    echo "  Price (Volumetric): \${$rate['price_per_kg_volumetric']}\n";
    echo "  ---\n";
}

echo "\n=== TEST CALCULATION ===\n\n";

// Test with a 5kg product
$testProductId = $db->fetchOne("SELECT id FROM products WHERE actual_weight >= 4 AND actual_weight <= 6 LIMIT 1");

if ($testProductId) {
    echo "Testing with product ID: {$testProductId['id']}\n";

    $product = $db->fetchOne("SELECT actual_weight, shipping_length, shipping_width, shipping_height FROM products WHERE id = ?", [$testProductId['id']]);

    echo "Actual Weight: {$product['actual_weight']} kg\n";

    $volumetric = calculateVolumetricWeight($product['shipping_length'], $product['shipping_width'], $product['shipping_height']);
    echo "Volumetric Weight: {$volumetric} kg\n";

    $chargeable = max($product['actual_weight'], $volumetric);
    echo "Chargeable Weight: {$chargeable} kg\n";

    $shippingMethodId = 1; // DHL
    $cost = calculateShippingCost($testProductId['id'], $shippingMethodId);

    echo "\nShipping Cost: \${$cost}\n";
    echo "Expected: Should be \$15 or \$18 (fixed tier price)\n";
    echo "NOT: Should NOT be \$75 or similar (multiplied)\n";
}
