<?php
$pageTitle = "Order Confirmation";

$orderNumber = Session::get('last_order_number');

if (!$orderNumber) {
    redirect('?page=home');
}

// Get order details
$order = $db->fetchOne(
    "SELECT * FROM orders WHERE order_number = ?",
    [$orderNumber]
);

// Get order items
$orderItems = $db->fetchAll(
    "SELECT * FROM order_items WHERE order_id = ?",
    [$order['id']]
);

// Clear the session variable
Session::remove('last_order_number');
?>

<div class="checkout-success-page">
    <div class="container">
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your purchase. Your order has been received and is being processed.</p>
        </div>

        <div class="order-details-card">
            <h2>Order Details</h2>
            <div class="order-info-grid">
                <div class="info-item">
                    <strong>Order Number:</strong>
                    <span><?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Order Date:</strong>
                    <span><?php echo date('F d, Y', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <strong>Email:</strong>
                    <span><?php echo htmlspecialchars($order['customer_email']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Payment Status:</strong>
                    <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </div>
            </div>

            <h3>Order Items</h3>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatPrice($item['price']); ?></td>
                            <td><?php echo formatPrice($item['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span><?php echo formatPrice($order['subtotal']); ?></span>
                </div>
                <div class="total-row">
                    <span>Shipping:</span>
                    <span><?php echo formatPrice($order['shipping_cost']); ?></span>
                </div>
                <div class="total-row">
                    <span>Tax:</span>
                    <span><?php echo formatPrice($order['tax_amount']); ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="total-row discount">
                        <span>Discount:</span>
                        <span>-<?php echo formatPrice($order['discount_amount']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span><?php echo formatPrice($order['total_amount']); ?></span>
                </div>
            </div>

            <div class="addresses-section">
                <div class="address-box">
                    <h4>Billing Address</h4>
                    <p>
                        <?php echo nl2br(htmlspecialchars($order['billing_address'])); ?><br>
                        <?php echo htmlspecialchars($order['billing_city']); ?>, 
                        <?php echo htmlspecialchars($order['billing_state']); ?> 
                        <?php echo htmlspecialchars($order['billing_zip']); ?><br>
                        <?php echo htmlspecialchars($order['billing_country']); ?>
                    </p>
                </div>
                <div class="address-box">
                    <h4>Shipping Address</h4>
                    <p>
                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
                        <?php echo htmlspecialchars($order['shipping_city']); ?>, 
                        <?php echo htmlspecialchars($order['shipping_state']); ?> 
                        <?php echo htmlspecialchars($order['shipping_zip']); ?><br>
                        <?php echo htmlspecialchars($order['shipping_country']); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="success-actions">
            <a href="?page=products" class="btn btn-primary">Continue Shopping</a>
            <a href="?page=home" class="btn btn-secondary">Back to Home</a>
        </div>

        <div class="success-note">
            <p><strong>What's Next?</strong></p>
            <p>We've sent a confirmation email to <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong> with your order details.</p>
            <p>You'll receive another email with tracking information once your order ships.</p>
        </div>
    </div>
</div>
