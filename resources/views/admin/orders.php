<?php
$pageTitle = "Orders";
include __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><?php echo $pageTitle; ?></h1>
    </div>

    <!-- Filters -->
    <div class="products-controls">
        <form method="GET" class="search-form" style="width: 100%; max-width: 800px;">
            <input type="text" name="search" placeholder="Search orders..."
                value="<?php echo htmlspecialchars($search ?? ''); ?>" style="flex:1;">
            <select name="status">
                <option value="">All Status</option>
                <option value="Pending" <?php echo ($status_filter ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending
                </option>
                <option value="Processing" <?php echo ($status_filter ?? '') === 'Processing' ? 'selected' : ''; ?>>
                    Processing</option>
                <option value="Shipped" <?php echo ($status_filter ?? '') === 'Shipped' ? 'selected' : ''; ?>>Shipped
                </option>
                <option value="Delivered" <?php echo ($status_filter ?? '') === 'Delivered' ? 'selected' : ''; ?>>
                    Delivered</option>
                <option value="Cancelled" <?php echo ($status_filter ?? '') === 'Cancelled' ? 'selected' : ''; ?>>
                    Cancelled</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            <a href="/admin/orders" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders) && count($orders) > 0): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order->order_number); ?></td>
                            <td>
                                <div style="font-weight:600; color:var(--color-text-main);">
                                    <?php echo htmlspecialchars($order->customer_name); ?></div>
                                <div style="font-size:0.85rem; color:var(--color-text-sub);">
                                    <?php echo htmlspecialchars($order->customer_email); ?></div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order->created_at)); ?></td>
                            <td><?php echo $order->items_count ?? '0'; ?></td>
                            <td style="font-weight:600;"><?php echo formatPrice($order->total_amount); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($order->payment_status); ?>">
                                    <?php echo ucfirst($order->payment_status); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($order->status); ?>">
                                    <?php echo ucfirst($order->status); ?>
                                </span>
                            </td>
                            <td>
                                <a href="/admin/orders/<?php echo $order->id; ?>" class="btn btn-sm btn-info"
                                    title="View Details">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 2rem; color: var(--color-text-sub);">
                            <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                            No orders found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>