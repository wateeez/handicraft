<?php
// Load Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "<h1>Ultimate Shipping Diagnostic</h1>";

try {
    $func = new ReflectionFunction('calculateCartShipping');
    echo "<p>Function <b>calculateCartShipping</b> defined in: <b>" . $func->getFileName() . "</b> at line " . $func->getStartLine() . "</p>";

    $start = $func->getStartLine() - 1;
    $end = $func->getEndLine();
    $lines = file($func->getFileName());
    $code = implode("", array_slice($lines, $start, $end - $start));

    echo "<h3>Function Code:</h3>";
    echo "<pre style='background:#eee;padding:10px;'>" . htmlspecialchars($code) . "</pre>";

    // Test with a 5kg product
    $product = DB::table('products')->orderBy('id', 'desc')->first();
    if ($product) {
        echo "<h3>Simulation:</h3>";
        echo "Product: " . $product->name . " (Weight: " . $product->actual_weight . "kg)<br>";

        $cartItems = [
            ['product_id' => $product->id, 'quantity' => 5]
        ];

        $shippingMethodId = 1;
        $cost = calculateCartShipping($cartItems, $shippingMethodId);

        echo "Calculated Cost (Quantity 5): <b>$" . $cost . "</b><br>";
        echo "Expected: $15.00 (Fixed)<br>";
        echo "Multiplied would be: " . (5 * 15) . ".00<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
