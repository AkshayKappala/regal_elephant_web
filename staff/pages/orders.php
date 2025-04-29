<?php
require_once __DIR__ . '/../../config/database.php';
$mysqli = Database::getConnection();

try {
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
    
    <div id="orders-loader" class="text-center my-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading orders...</span>
        </div>
        <p class="mt-2">Loading orders...</p>
    </div>
    
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
                            <tr>
                                <td colspan="8" class="text-center">No active orders.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
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
let allOrders = [];

document.addEventListener('DOMContentLoaded', function() {
    loadAllOrders();
    
    setInterval(loadAllOrders, 30000);
    
    setupOrderEventListeners();
});

function loadAllOrders() {
    const ordersLoader = document.getElementById('orders-loader');
    const activeOrdersContainer = document.getElementById('active-orders-container');
    const archivedOrdersContainer = document.getElementById('archived-orders-container');
    
    if (ordersLoader) ordersLoader.style.display = 'block';
    
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
    
    fetch(`api/get_orders_list.php?limit=500&_nocache=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                allOrders = data.orders;
                
                updateOrdersTables();
                
                if (ordersLoader) ordersLoader.style.display = 'none';
                
                console.log(`Successfully refreshed orders at ${new Date().toLocaleTimeString()}`);
            } else {
                throw new Error(data.error || 'Failed to load orders');
            }
        })
        .catch(error => {
            console.error('Error fetching orders:', error);
            
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
            
            if (ordersLoader) ordersLoader.style.display = 'none';
        });
}

function updateOrdersTables() {
    const activeOrders = allOrders.filter(order => order.status !== 'archived');
    const archivedOrders = allOrders.filter(order => order.status === 'archived');
    
    updateTableRows('#active-orders-table tbody', activeOrders);
    
    updateTableRows('#archived-orders-table tbody', archivedOrders);
    
    if (typeof setupStatusChangeHandlers === 'function') {
        setupStatusChangeHandlers();
    }
}

function updateTableRows(tableSelector, orders) {
    const tableBody = document.querySelector(tableSelector);
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    if (orders.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = '<td colspan="8" class="text-center">No orders found.</td>';
        tableBody.appendChild(emptyRow);
    } else {
        const rowsHTML = orders.map(order => {
            let formattedDate;
            if (typeof formatDate === 'function') {
                formattedDate = formatDate(order.order_placed_time);
            } else {
                const date = new Date(order.order_placed_time);
                formattedDate = `${date.toLocaleString('default', { month: 'short' })} ${date.getDate()}, ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
            }
            
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

function updateLocalOrdersData(updatedOrders) {
    if (!updatedOrders || updatedOrders.length === 0) return;
    
    updatedOrders.forEach(updatedOrder => {
        const existingOrderIndex = allOrders.findIndex(o => o.order_id == updatedOrder.order_id);
        
        if (existingOrderIndex >= 0) {
            const existingOrder = allOrders[existingOrderIndex];
            
            existingOrder.status = updatedOrder.status;
        } else {
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
    
    console.log('Setting up SSE connection at', new Date().toLocaleTimeString());
    const evtSource = new EventSource(`../api/order_events.php?client=staff&_nocache=${Date.now()}`);
    
    evtSource.addEventListener('connection', function(e) {
        console.log('SSE connection established at', new Date().toLocaleTimeString());
    });
    
    evtSource.addEventListener('orders_update', function(e) {
        console.log('Received orders_update event at', new Date().toLocaleTimeString());
        try {
            const data = JSON.parse(e.data);
            if (data.orders && data.orders.length > 0) {
                console.log('Orders update contains', data.orders.length, 'orders');
                updateLocalOrdersData(data.orders);
                
                updateOrdersTables();
            }
        } catch (err) {
            console.error('Error processing orders_update event:', err);
        }
    });
    
    evtSource.addEventListener('order_update', function(e) {
        console.log('Received order_update event at', new Date().toLocaleTimeString());
        try {
            const data = JSON.parse(e.data);
            if (data.order) {
                console.log('Order update received for order ID:', data.order.order_id, 'with status:', data.order.status);
                updateLocalOrdersData([data.order]);
                
                updateOrdersTables();
            }
        } catch (err) {
            console.error('Error processing order_update event:', err);
        }
    });
    
    evtSource.addEventListener('events_update', function(e) {
        console.log('Received events_update event at', new Date().toLocaleTimeString());
        try {
            const data = JSON.parse(e.data);
            if (data.events && data.events.length > 0) {
                console.log('Received', data.events.length, 'events');
                // Force a full refresh for any events update
                loadAllOrders();
            }
        } catch (err) {
            console.error('Error processing events_update event:', err);
        }
    });
    
    evtSource.addEventListener('message', function(e) {
        console.log('Received generic message event at', new Date().toLocaleTimeString());
        try {
            const data = JSON.parse(e.data);
            console.log('Generic message event data:', data);
            loadAllOrders();
        } catch (err) {
            console.error('Error processing generic message event:', err);
        }
    });
    
    evtSource.addEventListener('error', function(e) {
        console.error('SSE connection error at', new Date().toLocaleTimeString(), e);
        
        // Try to reconnect after a delay
        setTimeout(function() {
            console.log('Attempting to reconnect SSE at', new Date().toLocaleTimeString());
            setupOrderEventListeners();
        }, 5000);
    });
    
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            console.log('Page became visible, refreshing orders');
            loadAllOrders();
        }
    });
    
    window.addEventListener('beforeunload', function() {
        console.log('Closing SSE connection due to page unload');
        evtSource.close();
    });
}

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