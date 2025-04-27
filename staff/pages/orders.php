<?php
require_once __DIR__ . '/../../config/database.php';
$mysqli = Database::getConnection();

try {
    // Get all orders in a single query with no filters
    $query = "SELECT o.*, 
              (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
              FROM orders o 
              ORDER BY o.order_placed_time DESC 
              LIMIT 500";
    
    $result = $mysqli->query($query);
    
} catch (Exception $e) {
    $error = "Error retrieving orders: " . $e->getMessage();
}
?>

<div class="container-fluid">
    <h1 class="page-title">Orders Management</h1>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php else: ?>
                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover orders-table" id="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $result->fetch_assoc()): ?>
                                    <tr data-order-id="<?php echo $order['order_id']; ?>" <?php echo $order['status'] === 'archived' ? 'class="table-secondary"' : ''; ?>>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['customer_phone']); ?>
                                            <?php if($order['customer_email']): ?>
                                                <br><small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $order['item_count']; ?></td>
                                        <td>₹<?php echo number_format($order['order_total'], 2); ?></td>
                                        <td><?php echo date('M d, H:i', strtotime($order['order_placed_time'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo str_replace(' ', '-', $order['status']); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?page=order-details&id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                
                                                <?php if ($order['status'] !== 'archived'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                
                                                <ul class="dropdown-menu">
                                                    <?php if ($order['status'] == 'preparing'): ?>
                                                        <li>
                                                            <button class="dropdown-item quick-status-change" data-order-id="<?php echo $order['order_id']; ?>" data-status="ready">
                                                                <i class="bi bi-check-circle text-success"></i> Mark as Ready
                                                            </button>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($order['status'] == 'ready'): ?>
                                                        <li>
                                                            <button class="dropdown-item quick-status-change" data-order-id="<?php echo $order['order_id']; ?>" data-status="picked up">
                                                                <i class="bi bi-bag-check text-secondary"></i> Mark as Picked Up
                                                            </button>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($order['status'] != 'cancelled' && $order['status'] != 'picked up'): ?>
                                                        <li>
                                                            <button class="dropdown-item quick-status-change" data-order-id="<?php echo $order['order_id']; ?>" data-status="cancelled">
                                                                <i class="bi bi-x-circle text-danger"></i> Cancel Order
                                                            </button>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <li><hr class="dropdown-divider"></li>
                                                    
                                                    <li>
                                                        <button class="dropdown-item quick-status-change" data-order-id="<?php echo $order['order_id']; ?>" data-status="archived">
                                                            <i class="bi bi-archive text-warning"></i> Archive Order
                                                        </button>
                                                    </li>
                                                </ul>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted my-4">No orders found.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>