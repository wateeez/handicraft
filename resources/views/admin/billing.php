<?php
$pageTitle = "Billing";
include __DIR__ . '/includes/header.php';

// Get billing statistics
$paid_total = \Illuminate\Support\Facades\DB::table('orders')
    ->where('payment_status', 'Paid')
    ->sum('total_amount');

$unpaid_total = \Illuminate\Support\Facades\DB::table('orders')
    ->where('payment_status', '!=', 'Paid')
    ->sum('total_amount');

$refunded_total = \Illuminate\Support\Facades\DB::table('orders')
    ->where('payment_status', 'Refunded')
    ->sum('total_amount');

// Get all orders with payment breakdown
$orders = \Illuminate\Support\Facades\DB::table('orders')
    ->orderBy('created_at', 'DESC')
    ->get();
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-file-invoice-dollar"></i> Billing & Revenue</h1>
        <div>
            <a href="/admin/billing/export" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export to Spreadsheet
            </a>
        </div>
    </div>

    <!-- Billing Summary Cards -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3>
                    <?php echo formatPrice($paid_total); ?>
                </h3>
                <p>Total Paid</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3>
                    <?php echo formatPrice($unpaid_total); ?>
                </h3>
                <p>Pending Payment</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-undo"></i>
            </div>
            <div class="stat-info">
                <h3>
                    <?php echo formatPrice($refunded_total); ?>
                </h3>
                <p>Refunded</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stat-info">
                <h3>
                    <?php echo formatPrice($paid_total - $refunded_total); ?>
                </h3>
                <p>Net Revenue</p>
            </div>
        </div>
    </div>

    <!-- Payment Status Tabs -->
    <div class="dashboard-section" style="margin-bottom: 2rem;">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="/admin/billing"
                class="btn <?php echo !isset($_GET['filter']) ? 'btn-primary' : 'btn-secondary'; ?>">
                All Orders
            </a>
            <a href="/admin/billing?filter=paid"
                class="btn <?php echo ($_GET['filter'] ?? '') === 'paid' ? 'btn-success' : 'btn-secondary'; ?>">
                <i class="fas fa-check"></i> Paid
            </a>
            <a href="/admin/billing?filter=unpaid"
                class="btn <?php echo ($_GET['filter'] ?? '') === 'unpaid' ? 'btn-warning' : 'btn-secondary'; ?>">
                <i class="fas fa-clock"></i> Unpaid
            </a>
            <a href="/admin/billing?filter=refunded"
                class="btn <?php echo ($_GET['filter'] ?? '') === 'refunded' ? 'btn-danger' : 'btn-secondary'; ?>">
                <i class="fas fa-undo"></i> Refunded
            </a>
        </div>
    </div>

    <!-- Orders Billing Table -->
    <div class="dashboard-section">
        <h2><i class="fas fa-list"></i> Order Details with Price Breakdown</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Order Status</th>
                        <th>Payment Status</th>
                        <th style="text-align:right;">Subtotal</th>
                        <th style="text-align:right;">Tax</th>
                        <th style="text-align:right;">Shipping</th>
                        <th style="text-align:right;">Discount</th>
                        <th style="text-align:right;"><strong>Total</strong></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $filter = $_GET['filter'] ?? '';
                    $filteredOrders = $orders->filter(function ($order) use ($filter) {
                        if (!$filter)
                            return true;
                        if ($filter === 'paid')
                            return $order->payment_status === 'Paid';
                        if ($filter === 'unpaid')
                            return $order->payment_status !== 'Paid' && $order->payment_status !== 'Refunded';
                        if ($filter === 'refunded')
                            return $order->payment_status === 'Refunded';
                        return true;
                    });

                    if ($filteredOrders->count() > 0):
                        ?>
                        <?php foreach ($filteredOrders as $order): ?>
                            <tr>
                                <td><strong>#
                                        <?php echo htmlspecialchars($order->order_number); ?>
                                    </strong></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($order->created_at)); ?>
                                </td>
                                <td>
                                    <div style="font-weight:600;">
                                        <?php echo htmlspecialchars($order->customer_name); ?>
                                    </div>
                                    <div style="font-size:0.8rem; color:var(--color-text-sub);">
                                        <?php echo htmlspecialchars($order->customer_email); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($order->status); ?>">
                                        <?php echo ucfirst($order->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($order->payment_status ?? 'unpaid'); ?>">
                                        <?php echo ucfirst($order->payment_status ?? 'Unpaid'); ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <?php echo formatPrice($order->subtotal ?? 0); ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php echo formatPrice($order->tax_amount ?? 0); ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php echo formatPrice($order->shipping_cost ?? 0); ?>
                                </td>
                                <td style="text-align:right; color: var(--color-sale);">
                                    <?php echo ($order->discount_amount ?? 0) > 0 ? '-' . formatPrice($order->discount_amount) : '-'; ?>
                                </td>
                                <td style="text-align:right;"><strong>
                                        <?php echo formatPrice($order->total_amount); ?>
                                    </strong></td>
                                <td>
                                    <a href="/admin/orders/<?php echo $order->id; ?>" class="btn btn-sm btn-info"
                                        title="View Order">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Totals Row -->
                        <tr style="background-color: var(--color-bg-alt); font-weight: 700;">
                            <td colspan="5" style="text-align:right;">TOTALS:</td>
                            <td style="text-align:right;">
                                <?php echo formatPrice($filteredOrders->sum('subtotal')); ?>
                            </td>
                            <td style="text-align:right;">
                                <?php echo formatPrice($filteredOrders->sum('tax_amount')); ?>
                            </td>
                            <td style="text-align:right;">
                                <?php echo formatPrice($filteredOrders->sum('shipping_cost')); ?>
                            </td>
                            <td style="text-align:right; color: var(--color-sale);">
                                -
                                <?php echo formatPrice($filteredOrders->sum('discount_amount')); ?>
                            </td>
                            <td style="text-align:right; font-size: 1.1rem; color: var(--color-primary-dark);">
                                <?php echo formatPrice($filteredOrders->sum('total_amount')); ?>
                            </td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center" style="padding: 2rem; color: var(--color-text-sub);">
                                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                No orders found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>