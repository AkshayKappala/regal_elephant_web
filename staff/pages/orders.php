<?php
require_once __DIR__ . '/../../config/database.php';
$mysqli = Database::getConnection();

// Filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query conditions
$conditions = [];
$params = [];
$types = '';

if ($status) {
    $conditions[] = "o.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($date) {
    $conditions[] = "DATE(o.order_placed_time) = ?";
    $params[] = $date;
    $types .= 's';
}

if ($search) {
    $conditions[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

// Construct the WHERE clause
$whereClause = '';
if (!empty($conditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

// Prepare the query
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
          FROM orders o 
          $whereClause 
          ORDER BY o.order_placed_time DESC";

try {
    $stmt = $mysqli->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
} catch (Exception $e) {
    $error = "Error retrieving orders: " . $e->getMessage();
}
?>

<div class="container-fluid">
    <h1 class="page-title">Orders Management</h1>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <input type="hidden" name="page" value="orders">
                
                <div class="col-md-3">
                    <label for="status-filter" class="form-label">Status</label>
                    <select class="form-select" id="status-filter" name="status">
                        <option value="">All Statuses</option>
                        <option value="preparing" <?php echo $status === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                        <option value="ready" <?php echo $status === 'ready' ? 'selected' : ''; ?>>Ready</option>
                        <option value="picked up" <?php echo $status === 'picked up' ? 'selected' : ''; ?>>Picked Up</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date-filter" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date-filter" name="date" value="<?php echo $date; ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="search-filter" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search-filter" name="search" placeholder="Order #, Name, Phone" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-custom me-2">Filter</button>
                    <a href="?page=orders" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
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
                                    <tr>
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
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted my-4">No orders found matching your criteria.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>