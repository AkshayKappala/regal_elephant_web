<?php
require_once __DIR__ . '/../../config/database.php';
$mysqli = Database::getConnection();

// We fetch all orders in a single query with no filtering
try {
    // Get all orders in a single query with SUM(quantity)
    $query = "SELECT o.*, 
              (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) as item_count 
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
    
    <!-- Active Orders Table - Initially hidden -->
    <div class="card mb-4" id="active-orders-container" style="display: none;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Active Orders</h5>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover orders-table" id="active-orders-table">
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
                            <!-- Active orders will be populated by JavaScript -->
                            <tr>
                                <td colspan="8" class="text-center">No active orders.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Archived Orders Table - Initially hidden -->
    <div class="card" id="archived-orders-container" style="display: none;">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Archived Orders</h5>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover orders-table" id="archived-orders-table">
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
                            <!-- Archived orders will be populated by JavaScript -->
                            <tr>
                                <td colspan="8" class="text-center">No archived orders.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Global variable to store all orders
let allOrders = [];

document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    loadAllOrders();
    
    // Setup auto-refresh every 30 seconds
    setInterval(loadAllOrders, 30000);
    
    // Setup SSE for real-time updates
    setupOrderEventListeners();
});

// Function to load all orders from the database
function loadAllOrders() {
    const ordersLoader = document.getElementById('orders-loader');
    const activeOrdersContainer = document.getElementById('active-orders-container');
    const archivedOrdersContainer = document.getElementById('archived-orders-container');
    
    // Show loader immediately
    if (ordersLoader) ordersLoader.style.display = 'block';
    
    // Show containers with loading indicators in their tables
    if (activeOrdersContainer) {
        activeOrdersContainer.style.display = 'block';
        const activeTableBody = document.querySelector('#active-orders-table tbody');
        if (activeTableBody) {
            activeTableBody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Loading active orders...</td></tr>';
        }
    }
    
    if (archivedOrdersContainer) {
        archivedOrdersContainer.style.display = 'block';
        const archivedTableBody = document.querySelector('#archived-orders-table tbody');
        if (archivedTableBody) {
            archivedTableBody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Loading archived orders...</td></tr>';
        }
    }
    
    // Fetch all orders from the database with cache busting
    fetch(`api/get_orders_list.php?limit=500&_nocache=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Store orders globally
                allOrders = data.orders;
                
                // Update the tables
                updateOrdersTables();
                
                // Hide loader
                if (ordersLoader) ordersLoader.style.display = 'none';
            } else {
                throw new Error(data.error || 'Failed to load orders');
            }
        })
        .catch(error => {
            console.error('Error fetching orders:', error);
            
            // Show error message in tables
            if (activeOrdersContainer) {
                const activeTableBody = document.querySelector('#active-orders-table tbody');
                if (activeTableBody) {
                    activeTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load orders. Please try refreshing the page.</td></tr>';
                }
            }
            
            if (archivedOrdersContainer) {
                const archivedTableBody = document.querySelector('#archived-orders-table tbody');
                if (archivedTableBody) {
                    archivedTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load orders. Please try refreshing the page.</td></tr>';
                }
            }
            
            // Hide loader
            if (ordersLoader) ordersLoader.style.display = 'none';
        });
}

// Function to update both orders tables
function updateOrdersTables() {
    // Separate active and archived orders
    const activeOrders = allOrders.filter(order => order.status !== 'archived');
    const archivedOrders = allOrders.filter(order => order.status === 'archived');
    
    // Update active orders table
    updateTableRows('#active-orders-table tbody', activeOrders);
    
    // Update archived orders table
    updateTableRows('#archived-orders-table tbody', archivedOrders);
    
    // Re-attach event listeners for the new buttons
    if (typeof setupStatusChangeHandlers === 'function') {
        setupStatusChangeHandlers();
    }
}

// Function to update rows in a specific table
function updateTableRows(tableSelector, orders) {
    const tableBody = document.querySelector(tableSelector);
    if (!tableBody) return;
    
    // Clear existing rows
    tableBody.innerHTML = '';
    
    if (orders.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = '<td colspan="8" class="text-center">No orders found.</td>';
        tableBody.appendChild(emptyRow);
    } else {
        // Create and append all rows at once with complete HTML
        const rowsHTML = orders.map(order => {
            let formattedDate;
            if (typeof formatDate === 'function') {
                formattedDate = formatDate(order.order_placed_time);
            } else {
                const date = new Date(order.order_placed_time);
                formattedDate = `${date.toLocaleString('default', { month: 'short' })} ${date.getDate()}, ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
            }
            
            // Set contact info to a dash initially, and only replace it if we have actual data
            let contactInfo = '-';
            if (order.customer_phone || order.customer_email) {
                if (typeof formatContactInfo === 'function') {
                    contactInfo = formatContactInfo(order.customer_phone, order.customer_email);
                } else {
                    contactInfo = order.customer_email ? 
                        `${order.customer_phone || ''}<br><small>${order.customer_email}</small>` : 
                        (order.customer_phone || '');
                }
            }
            
            let btnGroupHTML;
            if (typeof generateActionButtonsHTML === 'function') {
                btnGroupHTML = generateActionButtonsHTML(order.order_id, order.status);
            } else {
                btnGroupHTML = `<a href="?page=order-details&id=${order.order_id}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>`;
            }
            
            let statusHTML;
            if (typeof formatStatus === 'function') {
                const status = formatStatus(order.status);
                statusHTML = `<span class="badge ${status.badgeClass}">${status.text}</span>`;
            } else {
                statusHTML = `<span class="badge badge-${order.status.replace(' ', '-')}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>`;
            }
            
            const itemCount = parseInt(order.item_count) || 0;
            
            const rowClass = order.status === 'archived' ? 'class="table-secondary"' : '';
            
            return `
                <tr data-order-id="${order.order_id}" ${rowClass}>
                    <td>${order.order_number || ''}</td>
                    <td>${order.customer_name || ''}</td>
                    <td>${contactInfo}</td>
                    <td>${itemCount}</td>
                    <td>₹${parseFloat(order.order_total || 0).toFixed(2)}</td>
                    <td>${formattedDate}</td>
                    <td>${statusHTML}</td>
                    <td>
                        <div class="btn-group">
                            ${btnGroupHTML}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        
        tableBody.innerHTML = rowsHTML;
    }
}

// Update local orders data with new orders or updates
function updateLocalOrdersData(updatedOrders) {
    if (!updatedOrders || updatedOrders.length === 0) return;
    
    updatedOrders.forEach(updatedOrder => {
        // Find if we already have this order
        const existingOrderIndex = allOrders.findIndex(o => o.order_id == updatedOrder.order_id);
        
        if (existingOrderIndex >= 0) {
            // For existing orders, we only need to update the status
            // and preserve the rest of the data, especially the item count and contact info
            const existingOrder = allOrders[existingOrderIndex];
            
            // Update only the status field
            existingOrder.status = updatedOrder.status;
            
            // Don't update item_count as we want to preserve the SUM(quantity) value
            // Don't update contact info either
        } else {
            // For new orders, add them to the beginning of the array
            allOrders.unshift(updatedOrder);
        }
    });
}

// Setup Server-Sent Events for real-time updates
function setupOrderEventListeners() {
    if (typeof EventSource === 'undefined') {
        console.log('This browser does not support Server-Sent Events. Using polling instead.');
        return;
    }
    
    const evtSource = new EventSource('../api/order_events.php?client=staff');
    
    evtSource.addEventListener('connection', function(e) {
        // Connection established
    });
    
    evtSource.addEventListener('orders_update', function(e) {
        const data = JSON.parse(e.data);
        if (data.orders && data.orders.length > 0) {
            // Update our local orders data
            updateLocalOrdersData(data.orders);
            
            // Redraw the tables
            updateOrdersTables();
        }
    });
    
    evtSource.addEventListener('order_update', function(e) {
        const data = JSON.parse(e.data);
        if (data.order) {
            // Update our local orders data
            updateLocalOrdersData([data.order]);
            
            // Redraw the tables
            updateOrdersTables();
        }
    });
    
    evtSource.addEventListener('error', function(e) {
        console.error('SSE connection error');
        
        // Try to reconnect after a delay
        setTimeout(function() {
            setupOrderEventListeners();
        }, 5000);
    });
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        evtSource.close();
    });
}

// Alias functions for compatibility with existing code
function refreshOrdersTable() {
    loadAllOrders();
}

function updateOrdersTable(orders) {
    if (orders && orders.length > 0) {
        allOrders = orders;
        updateOrdersTables();
    }
}
</script>