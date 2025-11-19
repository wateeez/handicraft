<?php
$pageTitle = "Dashboard";
include __DIR__ . '/includes/header.php';

// Get statistics
$stats = [];
$stats['total_products'] = $db->fetchOne("SELECT COUNT(*) as count FROM products")['count'];
$stats['total_orders'] = $db->fetchOne("SELECT COUNT(*) as count FROM orders")['count'];
$stats['pending_orders'] = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'];
$stats['total_revenue'] = $db->fetchOne("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'")['total'] ?? 0;
$stats['low_stock'] = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= low_stock_threshold")['count'];

// Recent orders
$recentOrders = $db->fetchAll(
    "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10"
);

// Low stock products
$lowStockProducts = $db->fetchAll(
    "SELECT * FROM products WHERE stock_quantity <= low_stock_threshold ORDER BY stock_quantity ASC LIMIT 10"
);
?>

<div class="dashboard">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['total_products']); ?></h3>
                <p>Total Products</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['total_orders']); ?></h3>
                <p>Total Orders</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['pending_orders']); ?></h3>
                <p>Pending Orders</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo formatPrice($stats['total_revenue']); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['low_stock']); ?></h3>
                <p>Low Stock Items</p>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="dashboard-section">
        <h2>Recent Orders</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentOrders): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo formatPrice($order['total_amount']); ?></td>
                                <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td>
                                    <a href="/admin/orders/<?php echo $order['id']; ?>" class="btn btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No orders yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="/admin/orders" class="btn btn-secondary">View All Orders</a>
    </div>

    <!-- Low Stock Products -->
    <?php if ($lowStockProducts): ?>
        <div class="dashboard-section">
            <h2>Low Stock Alert</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Current Stock</th>
                            <th>Threshold</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td><span class="badge badge-danger"><?php echo $product['stock_quantity']; ?></span></td>
                                <td><?php echo $product['low_stock_threshold']; ?></td>
                                <td>
                                    <a href="/admin/products/edit/<?php echo $product['id']; ?>" class="btn btn-sm">Update Stock</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
