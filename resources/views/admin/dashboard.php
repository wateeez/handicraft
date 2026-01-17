<?php
$pageTitle = "Dashboard";
include __DIR__ . '/includes/header.php';
?>

<div class="dashboard">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format(\Illuminate\Support\Facades\DB::table('products')->count()); ?></h3>
                <p>Total Products</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_orders ?? 0); ?></h3>
                <p>Total Orders</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($pending_orders ?? 0); ?></h3>
                <p>Pending Orders</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo formatPrice($total_revenue ?? 0); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format(count($low_stock_products ?? [])); ?></h3>
                <p>Low Stock Items</p>
            </div>
        </div>
    </div>

    <!-- Order Status Quick Filters -->
    <div class="dashboard-section">
        <h2><i class="fas fa-filter"></i> Orders by Status</h2>
        <?php
        // Get order counts by status
        $order_counts = [
            'all' => \Illuminate\Support\Facades\DB::table('orders')->count(),
            'pending' => \Illuminate\Support\Facades\DB::table('orders')->where('status', 'Pending')->count(),
            'processing' => \Illuminate\Support\Facades\DB::table('orders')->where('status', 'Processing')->count(),
            'shipped' => \Illuminate\Support\Facades\DB::table('orders')->where('status', 'Shipped')->count(),
            'delivered' => \Illuminate\Support\Facades\DB::table('orders')->where('status', 'Delivered')->count(),
            'cancelled' => \Illuminate\Support\Facades\DB::table('orders')->where('status', 'Cancelled')->count(),
        ];
        ?>
        <div class="status-tabs">
            <a href="/admin/orders" class="status-tab all">
                <div class="status-tab-icon"><i class="fas fa-list"></i></div>
                <div class="status-tab-info">
                    <span class="status-tab-count"><?php echo $order_counts['all']; ?></span>
                    <span class="status-tab-label">All Orders</span>
                </div>
            </a>
            <a href="/admin/orders?status=Pending" class="status-tab pending">
                <div class="status-tab-icon"><i class="fas fa-clock"></i></div>
                <div class="status-tab-info">
                    <span class="status-tab-count"><?php echo $order_counts['pending']; ?></span>
                    <span class="status-tab-label">Pending</span>
                </div>
            </a>
            <a href="/admin/orders?status=Processing" class="status-tab processing">
                <div class="status-tab-icon"><i class="fas fa-cog"></i></div>
                <div class="status-tab-info">
                    <span class="status-tab-count"><?php echo $order_counts['processing']; ?></span>
                    <span class="status-tab-label">Processing</span>
                </div>
            </a>
            <a href="/admin/orders?status=Shipped" class="status-tab shipped">
                <div class="status-tab-icon"><i class="fas fa-truck"></i></div>
                <div class="status-tab-info">
                    <span class="status-tab-count"><?php echo $order_counts['shipped']; ?></span>
                    <span class="status-tab-label">Shipped</span>
                </div>
            </a>
            <a href="/admin/orders?status=Delivered" class="status-tab delivered">
                <div class="status-tab-icon"><i class="fas fa-check-circle"></i></div>
                <div class="status-tab-info">
                    <span class="status-tab-count"><?php echo $order_counts['delivered']; ?></span>
                    <span class="status-tab-label">Delivered</span>
                </div>
            </a>
            <a href="/admin/orders?status=Cancelled" class="status-tab cancelled">
                <div class="status-tab-icon"><i class="fas fa-times-circle"></i></div>
                <div class="status-tab-info">
                    <span class="status-tab-count"><?php echo $order_counts['cancelled']; ?></span>
                    <span class="status-tab-label">Cancelled</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="dashboard-section">
        <h2><i class="fas fa-shopping-bag"></i> Recent Orders</h2>
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
                    <?php if (!empty($recent_orders) && count($recent_orders) > 0): ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order->order_number); ?></td>
                                <td><?php echo htmlspecialchars($order->customer_name); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order->created_at)); ?></td>
                                <td style="font-weight:600;"><?php echo formatPrice($order->total_amount); ?></td>
                                <td><span
                                        class="badge badge-<?php echo strtolower($order->status); ?>"><?php echo ucfirst($order->status); ?></span>
                                </td>
                                <td>
                                    <a href="/admin/orders/<?php echo $order->id; ?>" class="btn btn-sm btn-info"><i
                                            class="fas fa-eye"></i> View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding:2rem; color:var(--color-text-sub);">
                                <i class="fas fa-inbox" style="font-size:2rem; margin-bottom:0.5rem; display:block;"></i>
                                No orders yet
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="/admin/orders" class="btn btn-secondary"><i class="fas fa-list"></i> View All Orders</a>
    </div>

    <!-- Low Stock Products -->
    <?php if (!empty($low_stock_products) && count($low_stock_products) > 0): ?>
        <div class="dashboard-section">
            <h2>Low Stock Alert</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Current Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product->name); ?></td>
                                <td><?php echo htmlspecialchars($product->sku ?? 'N/A'); ?></td>
                                <td><span class="badge badge-danger"><?php echo $product->stock_quantity; ?></span></td>
                                <td>
                                    <a href="/admin/products/edit/<?php echo $product->id; ?>" class="btn btn-sm">Update
                                        Stock</a>
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