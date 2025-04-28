<?php
require_once __DIR__ . '/../../config/database.php';
$mysqli = Database::getConnection();

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    echo '<div class="alert alert-danger">Invalid order ID</div>';
    exit;
}

try {
    $query = "SELECT * FROM orders WHERE order_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        echo '<div class="alert alert-danger">Order not found</div>';
        exit;
    }
    
    $itemsQuery = "SELECT oi.*, fi.name 
                  FROM order_items oi 
                  JOIN food_items fi ON oi.item_id = fi.item_id 
                  WHERE oi.order_id = ?";
    $itemsStmt = $mysqli->prepare($itemsQuery);
    $itemsStmt->bind_param('i', $order_id);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error retrieving order data: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0">Order Details</h1>
        <a href="?page=orders" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Order #<?php echo htmlspecialchars($order['order_number']); ?>
                    </h5>
                    <span class="badge badge-<?php echo str_replace(' ', '-', $order['status']); ?>" id="status-badge">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            <?php if($order['customer_email']): ?>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <?php endif; ?>
                            <p><strong>Order Date:</strong> <?php echo date('F d, Y h:i A', strtotime($order['order_placed_time'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="order-status-select" class="form-label"><strong>Status:</strong></label>
                                <select class="form-select status-select" id="order-status-select" data-order-id="<?php echo $order_id; ?>" <?php echo $order['status'] == 'archived' ? 'disabled' : ''; ?>>
                                    <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                    <option value="picked up" <?php echo $order['status'] == 'picked up' ? 'selected' : ''; ?>>Picked Up</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="archived" <?php echo $order['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                            <?php if($order['pickup_time']): ?>
                                <p><strong>Pickup Time:</strong> <?php echo date('F d, Y h:i A', strtotime($order['pickup_time'])); ?></p>
                            <?php endif; ?>
                            <p class="fs-5 mt-4 text-end"><strong>Total:</strong> ₹<?php echo number_format($order['order_total'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table order-items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                while ($item = $itemsResult->fetch_assoc()): 
                                    $itemTotal = $item['quantity'] * $item['item_price'];
                                    $subtotal += $itemTotal;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">₹<?php echo number_format($item['item_price'], 2); ?></td>
                                        <td class="text-end">₹<?php echo number_format($itemTotal, 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">₹<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <?php 
                                $tax = $subtotal * 0.10;
                                $tip = $order['order_total'] - ($subtotal + $tax);
                                ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tax (10%):</strong></td>
                                    <td class="text-end">₹<?php echo number_format($tax, 2); ?></td>
                                </tr>
                                <?php if ($tip > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tip:</strong></td>
                                    <td class="text-end">₹<?php echo number_format($tip, 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>₹<?php echo number_format($order['order_total'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($order['status'] == 'archived'): ?>
                            <div class="alert alert-secondary">
                                <i class="bi bi-info-circle"></i> This order has been archived and cannot be modified.
                            </div>
                        <?php else: ?>
                            <?php if ($order['status'] == 'preparing'): ?>
                                <button class="btn btn-success quick-status-change" data-order-id="<?php echo $order_id; ?>" data-status="ready">
                                    <i class="bi bi-check-circle"></i> Mark as Ready
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] == 'ready'): ?>
                                <button class="btn btn-secondary quick-status-change" data-order-id="<?php echo $order_id; ?>" data-status="picked up">
                                    <i class="bi bi-bag-check"></i> Mark as Picked Up
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] != 'cancelled' && $order['status'] != 'picked up' && $order['status'] != 'archived'): ?>
                                <button class="btn btn-danger quick-status-change" data-order-id="<?php echo $order_id; ?>" data-status="cancelled">
                                    <i class="bi bi-x-circle"></i> Cancel Order
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>