<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize($_POST['coupon_code'] ?? '');
    $subtotal = getCartTotal();
    
    if ($code) {
        $result = applyCoupon($code, $subtotal);
        if ($result['success']) {
            Session::set('coupon_code', $code);
            Session::set('discount_amount', $result['discount']);
            echo json_encode([
                'success' => true,
                'message' => 'Coupon applied! You save ' . formatPrice($result['discount']),
                'discount' => $result['discount']
            ]);
        } else {
            echo json_encode($result);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
