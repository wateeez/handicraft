<?php
$pageTitle = "Order Details";
include __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>Order #<?php echo htmlspecialchars($order->order_number); ?></h1>
        <a href="/admin/orders" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
    </div>

    <!-- Order & Customer Grid -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; margin-bottom: 2rem;">

        <!-- Order Info -->
        <div
            style="background: white; padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--color-border);">
            <h3
                style="margin-top:0; border-bottom:1px solid var(--color-border); padding-bottom:1rem; margin-bottom:1rem; color:var(--color-primary-dark);">
                <i class="fas fa-file-invoice"></i> Order Information
            </h3>
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                <tr>
                    <td style="color:var(--color-text-sub);">Order Date:</td>
                    <td style="font-weight:600; text-align:right;">
                        <?php echo date('F d, Y H:i', strtotime($order->created_at)); ?>
                    </td>
                </tr>
                <tr>
                    <td style="color:var(--color-text-sub);">Payment Method:</td>
                    <td style="font-weight:600; text-align:right;">
                        <?php echo ucfirst($order->payment_method ?? 'N/A'); ?>
                    </td>
                </tr>
                <tr>
                    <td style="color:var(--color-text-sub);">Payment Status:</td>
                    <td style="text-align:right;">
                        <span class="badge badge-<?php echo strtolower($order->payment_status); ?>">
                            <?php echo ucfirst($order->payment_status); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="color:var(--color-text-sub);">Order Status:</td>
                    <td style="text-align:right;">
                        <span class="badge badge-<?php echo strtolower($order->status); ?>">
                            <?php echo ucfirst($order->status); ?>
                        </span>
                    </td>
                </tr>
            </table>

            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
                <h4 style="margin:0 0 1rem 0; font-size:1rem;">Update Status</h4>
                <form method="POST" action="/admin/orders/update-status" style="display:flex; gap:0.5rem;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                    <select name="status"
                        style="flex:1; padding:0.5rem; border-radius:var(--radius-sm); border:1px solid var(--color-border);">
                        <option value="Pending" <?php echo $order->status === 'Pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="Processing" <?php echo $order->status === 'Processing' ? 'selected' : ''; ?>>
                            Processing</option>
                        <option value="Shipped" <?php echo $order->status === 'Shipped' ? 'selected' : ''; ?>>Shipped
                        </option>
                        <option value="Delivered" <?php echo $order->status === 'Delivered' ? 'selected' : ''; ?>>
                            Delivered</option>
                        <option value="Cancelled" <?php echo $order->status === 'Cancelled' ? 'selected' : ''; ?>>
                            Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </form>
            </div>

            <!-- Payment Status Update -->
            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
                <h4 style="margin:0 0 1rem 0; font-size:1rem;"><i class="fas fa-credit-card"></i> Payment Status</h4>
                <form method="POST" action="/admin/orders/update-payment-status" style="display:flex; gap:0.5rem;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                    <select name="payment_status"
                        style="flex:1; padding:0.5rem; border-radius:var(--radius-sm); border:1px solid var(--color-border);">
                        <option value="Unpaid" <?php echo ($order->payment_status ?? 'Unpaid') === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="Paid" <?php echo ($order->payment_status ?? '') === 'Paid' ? 'selected' : ''; ?>>
                            Paid</option>
                        <option value="Refunded" <?php echo ($order->payment_status ?? '') === 'Refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Update</button>
                </form>
            </div>
        </div>

        <!-- Customer Info -->
        <div
            style="background: white; padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--color-border);">
            <h3
                style="margin-top:0; border-bottom:1px solid var(--color-border); padding-bottom:1rem; margin-bottom:1rem; color:var(--color-primary-dark);">
                <i class="fas fa-user-circle"></i> Customer Details
            </h3>
            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem;">
                <div
                    style="width:50px; height:50px; background:var(--color-bg-alt); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--color-primary);">
                    <i class="fas fa-user fa-lg"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:1.1rem;">
                        <?php echo htmlspecialchars($order->customer_name); ?>
                    </div>
                    <div style="color:var(--color-text-sub);"><?php echo htmlspecialchars($order->customer_email); ?>
                    </div>
                    <div style="color:var(--color-text-sub);">
                        <?php echo htmlspecialchars($order->customer_phone ?? ''); ?>
                    </div>
                </div>
            </div>

            <h4 style="font-size:1rem; margin-bottom:0.5rem;">Shipping Address</h4>
            <div
                style="background:var(--color-bg); padding:1rem; border-radius:var(--radius-sm); font-size:0.95rem; line-height:1.6;">
                <?php echo htmlspecialchars($order->shipping_address ?? 'N/A'); ?><br>
                <?php echo htmlspecialchars($order->shipping_city ?? ''); ?>,
                <?php echo htmlspecialchars($order->shipping_state ?? ''); ?>
                <?php echo htmlspecialchars($order->shipping_zip ?? ''); ?><br>
                <?php echo htmlspecialchars($order->shipping_country ?? ''); ?>
            </div>

            <?php if (!empty($order->notes)): ?>
                <div style="margin-top:1.5rem;">
                    <h4 style="font-size:1rem; margin-bottom:0.5rem;">Notes</h4>
                    <div
                        style="background:var(--color-bg-alt); padding:0.8rem; border-radius:var(--radius-sm); font-style:italic; font-size:0.9rem;">
                        "<?php echo nl2br(htmlspecialchars($order->notes)); ?>"
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Items -->
    <div
        style="background: white; padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--color-border);">
        <h3
            style="margin-top:0; border-bottom:1px solid var(--color-border); padding-bottom:1rem; margin-bottom:1rem; color:var(--color-primary-dark);">
            <i class="fas fa-box"></i> Items Ordered
        </h3>
        <div class="table-responsive">
            <table class="data-table">
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
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php if (!empty($item->image)): ?>
                                            <img src="/<?php echo htmlspecialchars($item->image); ?>" class="table-image">
                                        <?php else: ?>
                                            <div class="image-placeholder-small"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                        <span
                                            style="font-weight:600;"><?php echo htmlspecialchars($item->product_name ?? 'N/A'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($item->product_sku ?? 'N/A'); ?></td>
                                <td><?php echo formatPrice($item->price); ?></td>
                                <td><?php echo $item->quantity; ?></td>
                                <td style="font-weight:600;">
                                    <?php echo formatPrice($item->subtotal ?? ($item->price * $item->quantity)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background-color: var(--color-bg);">
                        <td colspan="4" style="text-align:right; font-weight:600;">Subtotal:</td>
                        <td><?php echo formatPrice($order->subtotal ?? 0); ?></td>
                    </tr>
                    <?php if (isset($order->tax_amount) && $order->tax_amount > 0): ?>
                        <tr style="background-color: var(--color-bg);">
                            <td colspan="4" style="text-align:right; font-weight:600;">Tax:</td>
                            <td><?php echo formatPrice($order->tax_amount); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr style="background-color: var(--color-bg);">
                        <td colspan="4" style="text-align:right; font-weight:600;">Shipping:</td>
                        <td><?php echo formatPrice($order->shipping_cost ?? 0); ?></td>
                    </tr>
                    <?php if (isset($order->discount_amount) && $order->discount_amount > 0): ?>
                        <tr style="background-color: var(--color-bg);">
                            <td colspan="4" style="text-align:right; font-weight:600; color:var(--color-sale);">Discount:
                            </td>
                            <td style="color:var(--color-sale);">-<?php echo formatPrice($order->discount_amount); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr style="background-color: var(--color-bg-alt); font-size:1.1rem;">
                        <td colspan="4" style="text-align:right; font-weight:700;">Total:</td>
                        <td style="font-weight:700; color:var(--color-primary-dark);">
                            <?php echo formatPrice($order->total_amount); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>