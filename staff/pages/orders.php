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
    
    <!-- Loader that will show during initial page load -->
    <div id="orders-loader" class="text-center my-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading orders...</span>
        </div>
        <p class="mt-2">Loading orders...</p>
    </div>
    
    <!-- Orders Table - Initially hidden -->
    <div class="card" id="orders-container" style="display: none;">
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php else: ?>
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
                            <!-- Table will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize orders table with data from API instead of directly from PHP
    refreshOrdersTable();
});

// Override the refreshOrdersTable function to show/hide loader
function refreshOrdersTable() {
    const ordersLoader = document.getElementById('orders-loader');
    const ordersContainer = document.getElementById('orders-container');
    
    // Show loader, hide table
    if (ordersLoader) ordersLoader.style.display = 'block';
    if (ordersContainer) ordersContainer.style.display = 'none';
    
    // Get current filter state from the form
    const statusFilter = document.getElementById('status-filter')?.value || 'active';
    const dateFilter = document.getElementById('date-filter')?.value || '';
    const searchFilter = document.getElementById('search-filter')?.value || '';
    
    // Build query parameters for the API call
    let queryParams = new URLSearchParams();
    if (statusFilter) queryParams.append('status', statusFilter);
    if (dateFilter) queryParams.append('date', dateFilter);
    if (searchFilter) queryParams.append('search', searchFilter);
    
    // Make the API request with filters
    fetch(`api/get_orders_list.php?${queryParams.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateOrdersTable(data.orders);
                
                // Hide loader, show table
                if (ordersLoader) ordersLoader.style.display = 'none';
                if (ordersContainer) ordersContainer.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error fetching updated orders:', error);
            
            // Hide loader, show table (even on error)
            if (ordersLoader) ordersLoader.style.display = 'none';
            if (ordersContainer) ordersContainer.style.display = 'block';
        });
}
</script>