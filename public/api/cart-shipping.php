<?php
// Persist shipping method from cart summary dropdown (AJAX POST)
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingMethod = $_POST['shipping_method'] ?? null;
    if ($shippingMethod) {
        $_SESSION['cart_shipping_method'] = $shippingMethod;
        echo json_encode(['success' => true, 'message' => 'Shipping method updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No shipping method provided']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
