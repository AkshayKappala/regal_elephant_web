<?php
require_once __DIR__ . '/../../config/database.php';
$mysqli = Database::getConnection();

// Get order statistics for the dashboard
$stats = [
    'preparing' => 0,
    'ready' => 0,
    'picked_up' => 0,
    'total' => 0
];

try {
    // Count orders by status
    $query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $result = $mysqli->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = str_replace(' ', '_', $row['status']);
            $stats[$status] = $row['count'];
            $stats['total'] += $row['count'];
        }
    }
    
    // Get recent orders for quick view
    $recentOrdersQuery = "SELECT o.*, 
                        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
                        FROM orders o 
                        ORDER BY order_placed_time DESC LIMIT 5";
    $recentOrdersResult = $mysqli->query($recentOrdersQuery);
    
} catch (Exception $e) {
    $error = "Error retrieving dashboard data: " . $e->getMessage();
}
?>

<div class="container-fluid">
    <h1 class="page-title">Dashboard</h1>
    
    <!-- Order Statistics -->
    <div class="row" id="dashboard-stats">
        <div class="col-md-6 col-lg-3">
            <div class="stat-box stat-preparing">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <h3 class="stat-number" id="preparing-count"><?php echo $stats['preparing']; ?></h3>
                <p class="stat-label">Preparing</p>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="stat-box stat-ready">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="stat-number" id="ready-count"><?php echo $stats['ready']; ?></h3>
                <p class="stat-label">Ready for Pickup</p>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="stat-box stat-picked-up">
                <div class="stat-icon">
                    <i class="bi bi-bag-check"></i>
                </div>
                <h3 class="stat-number" id="picked-up-count"><?php echo $stats['picked_up']; ?></h3>
                <p class="stat-label">Picked Up</p>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="stat-box stat-total">
                <div class="stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h3 class="stat-number" id="total-orders-count"><?php echo $stats['total']; ?></h3>
                <p class="stat-label">Total Orders</p>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="?page=orders" class="btn btn-sm btn-custom">View All Orders</a>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php else: ?>
                        <?php if ($recentOrdersResult && $recentOrdersResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover orders-table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = $recentOrdersResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo $order['item_count']; ?></td>
                                                <td>₹<?php echo number_format($order['order_total'], 2); ?></td>
                                                <td><?php echo date('M d, H:i', strtotime($order['order_placed_time'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo str_replace(' ', '-', $order['status']); ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="?page=order-details&id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted my-4">No recent orders found.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>