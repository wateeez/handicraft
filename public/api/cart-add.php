<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support both JSON and form POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (is_array($data)) {
        $productId = intval($data['product_id'] ?? 0);
        $quantity = intval($data['quantity'] ?? 1);
        $shippingMethod = isset($data['shipping_method']) ? $data['shipping_method'] : null;
    } else {
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $shippingMethod = isset($_POST['shipping_method']) ? $_POST['shipping_method'] : null;
    }

    if ($productId > 0) {
        // Persist shipping method in session for cart
        if ($shippingMethod) {
            $_SESSION['cart_shipping_method'] = $shippingMethod;
        }
        $result = addToCart($productId, $quantity);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
