<?php
$pageTitle = "Order Details";
include __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>Order #<?php echo htmlspecialchars($order->order_number); ?></h1>
        <a href="/admin/orders" class="btn btn-secondary">Back to Orders</a>
    </div>

    <div class="order-details-grid">
        <!-- Order Info -->
        <div class="order-info-card">
            <h3>Order Information</h3>
            <table class="info-table">
                <tr>
                    <td><strong>Order Date:</strong></td>
                    <td><?php echo date('F d, Y H:i', strtotime($order->created_at)); ?></td>
                </tr>
                <tr>
                    <td><strong>Payment Status:</strong></td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($order->payment_status); ?>">
                            <?php echo ucfirst($order->payment_status); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Payment Method:</strong></td>
                    <td><?php echo ucfirst($order->payment_method ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td><strong>Order Status:</strong></td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($order->status); ?>">
                            <?php echo ucfirst($order->status); ?>
                        </span>
                    </td>
                </tr>
            </table>

            <h4>Update Order Status</h4>
            <form method="POST" action="/admin/orders/update-status">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                <select name="status" class="form-control" required>
                    <option value="Pending" <?php echo $order->status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo $order->status === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="Shipped" <?php echo $order->status === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="Delivered" <?php echo $order->status === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="Cancelled" <?php echo $order->status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Update Status</button>
            </form>
        </div>

        <!-- Customer Info -->
        <div class="order-info-card">
            <h3>Customer Information</h3>
            <table class="info-table">
                <tr>
                    <td><strong>Name:</strong></td>
                    <td><?php echo htmlspecialchars($order->customer_name); ?></td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td><?php echo htmlspecialchars($order->customer_email); ?></td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td><?php echo htmlspecialchars($order->customer_phone ?? 'N/A'); ?></td>
                </tr>
            </table>

            <h4>Shipping Address</h4>
            <p>
                <?php echo htmlspecialchars($order->shipping_address ?? 'N/A'); ?><br>
                <?php echo htmlspecialchars($order->shipping_city ?? ''); ?>, <?php echo htmlspecialchars($order->shipping_state ?? ''); ?> <?php echo htmlspecialchars($order->shipping_zip ?? ''); ?><br>
                <?php echo htmlspecialchars($order->shipping_country ?? ''); ?>
            </p>

            <?php if (!empty($order->notes)): ?>
            <h4>Order Notes</h4>
            <p><?php echo nl2br(htmlspecialchars($order->notes)); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Items -->
    <div class="order-items-section">
        <h3>Order Items</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orderItems) && count($orderItems) > 0): ?>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item->image)): ?>
                                    <img src="/<?php echo htmlspecialchars($item->image); ?>" alt="" style="width:50px;height:50px;object-fit:cover;margin-right:10px;vertical-align:middle;">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item->product_name ?? 'N/A'); ?>
                            </td>
                            <td><?php echo htmlspecialchars($item->product_sku ?? 'N/A'); ?></td>
                            <td><?php echo formatPrice($item->price); ?></td>
                            <td><?php echo $item->quantity; ?></td>
                            <td><?php echo formatPrice($item->subtotal ?? ($item->price * $item->quantity)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No items found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Subtotal:</strong></td>
                    <td><?php echo formatPrice($order->subtotal ?? 0); ?></td>
                </tr>
                <?php if (isset($order->tax_amount) && $order->tax_amount > 0): ?>
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Tax:</strong></td>
                    <td><?php echo formatPrice($order->tax_amount); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Shipping:</strong></td>
                    <td><?php echo formatPrice($order->shipping_cost ?? 0); ?></td>
                </tr>
                <?php if (isset($order->discount_amount) && $order->discount_amount > 0): ?>
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Discount:</strong></td>
                    <td>-<?php echo formatPrice($order->discount_amount); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Total:</strong></td>
                    <td><strong><?php echo formatPrice($order->total_amount); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
.order-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.order-info-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.order-info-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #2c3e50;
}

.order-info-card h4 {
    margin-top: 20px;
    margin-bottom: 10px;
    color: #2c3e50;
    font-size: 16px;
}

.info-table {
    width: 100%;
    margin-bottom: 15px;
}

.info-table td {
    padding: 8px 0;
}

.order-items-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.order-items-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #2c3e50;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
