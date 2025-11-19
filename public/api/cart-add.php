<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($productId > 0) {
        $result = addToCart($productId, $quantity);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
