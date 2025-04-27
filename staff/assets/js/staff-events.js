/**
 * Server-Sent Events handling for the staff portal
 */
import { updateOrderStats } from './staff-ui.js';
import { updateDashboardOrders, refreshDashboardOrders, updateOrdersTableWithNewOrders, refreshOrdersTable } from './staff-orders.js';
import { formatStatus } from './staff-utils.js';

// Track processed events to prevent duplicate processing
const processedEvents = new Set();
// Track orders we've already seen - using localStorage for persistence
const processedOrders = new Set(
    JSON.parse(localStorage.getItem('processedOrders') || '[]')
);

// Function to save processed orders to localStorage for persistence
function saveProcessedOrders() {
    // Convert Set to Array for storage
    const ordersArray = Array.from(processedOrders);
    // Limit to last 500 entries to prevent excessive storage
    const limitedArray = ordersArray.slice(-500);
    localStorage.setItem('processedOrders', JSON.stringify(limitedArray));
}

/**
 * Initialize Server-Sent Events for real-time updates
 */
export function initializeSSEConnection() {
    // Check if browser supports EventSource
    if (typeof EventSource === 'undefined') {
        console.log('This browser does not support Server-Sent Events. Using fallback polling.');
        return;
    }
    
    // Important: Add the client=staff parameter to identify this as a staff connection
    const sseEndpoint = '../api/order_events.php?client=staff';
    const evtSource = new EventSource(sseEndpoint);
    
    evtSource.addEventListener('connection', function(event) {
        const data = JSON.parse(event.data);
        console.log('SSE connection established:', data);
    });
    
    evtSource.addEventListener('orders_update', function(event) {
        const data = JSON.parse(event.data);
        console.log('Orders updated:', data);
        
        // Process new orders
        if (data.orders && data.orders.length > 0) {
            // Update dashboard stats
            if (document.getElementById('dashboard-stats')) {
                updateOrderStats(true);
            }
            
            // Handle new preparing orders (new orders)
            const newOrders = data.orders.filter(order => order.status === 'preparing');
            if (newOrders.length > 0) {
                // Identify truly unprocessed orders (those we haven't seen before)
                const unprocessedOrders = newOrders.filter(order => {
                    const orderUniqueId = `new_order_${order.order_id}`;
                    if (processedOrders.has(orderUniqueId)) {
                        return false; // Skip if we've already processed this order
                    }
                    
                    // Mark this order as processed and save to localStorage
                    processedOrders.add(orderUniqueId);
                    saveProcessedOrders();
                    return true; // This is a new order we should process
                });
                
                if (unprocessedOrders.length > 0) {
                    // Update dashboard recent orders if on dashboard page
                    if (document.querySelector('.orders-table')) {
                        updateDashboardOrders(unprocessedOrders);
                    }
                    
                    // Update orders table if on orders page
                    if (document.getElementById('orders-table')) {
                        updateOrdersTableWithNewOrders(unprocessedOrders);
                    }
                }
            }
            
            // Also handle any other status changes that might be in the batch
            const otherOrders = data.orders.filter(order => order.status !== 'preparing');
            if (otherOrders.length > 0) {
                // Update dashboard if we're on that page
                if (document.querySelector('.orders-table')) {
                    updateDashboardOrders(otherOrders);
                }
                
                // Update orders table if on orders page
                if (document.getElementById('orders-table')) {
                    updateOrdersTableWithNewOrders(otherOrders);
                }
            }
        }
    });
    
    evtSource.addEventListener('events_update', function(event) {
        const data = JSON.parse(event.data);
        console.log('Events update received:', data);
        
        if (data.events && data.events.length > 0) {
            // Process each event
            data.events.forEach(event => {
                // Create a unique ID for this event to track if we've processed it before
                const eventUniqueId = `${event.event_type}_${event.event_id}`;
                
                // Skip if we've already processed this event
                if (processedEvents.has(eventUniqueId)) {
                    console.log(`Skipping already processed event: ${eventUniqueId}`);
                    return;
                }
                
                // Mark this event as processed
                processedEvents.add(eventUniqueId);
                
                // Limit the size of the processed events set to prevent memory issues
                if (processedEvents.size > 200) {
                    // Convert to array, remove oldest entries, and convert back to Set
                    const eventsArray = Array.from(processedEvents);
                    processedEvents.clear();
                    eventsArray.slice(-100).forEach(id => processedEvents.add(id));
                }
                
                const eventData = JSON.parse(event.event_data);
                
                // If this is a new order event, refresh the dashboard
                if (event.event_type === 'new_order') {
                    if (document.getElementById('dashboard-stats')) {
                        updateOrderStats(true);
                    }
                    
                    if (document.querySelector('.orders-table')) {
                        // Fetch the latest orders to update the dashboard
                        fetch('api/get_orders_list.php?limit=5')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    refreshDashboardOrders(data.orders);
                                }
                            })
                            .catch(error => console.error('Error fetching orders:', error));
                    }
                    
                    if (document.getElementById('orders-table')) {
                        refreshOrdersTable();
                        
                        // Mark this order as processed without showing notification
                        const orderUniqueId = `new_order_${eventData.order_id}`;
                        if (!processedOrders.has(orderUniqueId)) {
                            processedOrders.add(orderUniqueId);
                            saveProcessedOrders();
                        }
                    }
                }
                
                // If this is a status change, update the UI as needed
                if (event.event_type === 'status_change') {
                    handleStatusChangeEvent(eventData);
                }
            });
        }
    });
    
    evtSource.addEventListener('order_update', function(event) {
        const data = JSON.parse(event.data);
        console.log('Order updated:', data);
        
        if (data.order) {
            handleOrderUpdateEvent(data.order);
        }
    });
    
    evtSource.addEventListener('ping', function(event) {
        // Keep-alive event - nothing to do here
    });
    
    evtSource.onerror = function(error) {
        console.error('SSE connection error:', error);
        
        // Reconnect after a delay
        setTimeout(function() {
            initializeSSEConnection();
        }, 5000);
    };
}

/**
 * Handle a status change event
 * @param {Object} eventData - Data for the status change event
 */
function handleStatusChangeEvent(eventData) {
    const orderId = eventData.order_id;
    const newStatus = eventData.status;
    
    // Update status in dashboard table if it exists
    const dashboardRow = document.querySelector(`.orders-table tr[data-order-id="${orderId}"]`);
    if (dashboardRow) {
        const statusBadge = dashboardRow.querySelector('.badge');
        if (statusBadge) {
            const status = formatStatus(newStatus);
            statusBadge.textContent = status.text;
            statusBadge.className = `badge ${status.badgeClass}`;
        }
    }
    
    // Update status in orders table if it exists
    const ordersRow = document.querySelector(`#orders-table tr[data-order-id="${orderId}"]`);
    if (ordersRow) {
        const statusBadge = ordersRow.querySelector('.badge');
        if (statusBadge) {
            const status = formatStatus(newStatus);
            statusBadge.textContent = status.text;
            statusBadge.className = `badge ${status.badgeClass}`;
        }
    }
    
    // Update order details page if we're viewing this order
    const statusSelect = document.getElementById('order-status-select');
    if (statusSelect && statusSelect.getAttribute('data-order-id') == orderId) {
        statusSelect.value = newStatus;
        
        const statusBadge = document.getElementById('status-badge');
        if (statusBadge) {
            const status = formatStatus(newStatus);
            statusBadge.textContent = status.text;
            statusBadge.className = `badge ${status.badgeClass}`;
        }
    }
}

/**
 * Handle an order update event
 * @param {Object} order - The updated order data
 */
function handleOrderUpdateEvent(order) {
    // If on the order details page, update UI elements
    const statusSelect = document.getElementById('order-status-select');
    if (statusSelect && parseInt(statusSelect.getAttribute('data-order-id')) === order.order_id) {
        statusSelect.value = order.status;
        
        const statusBadge = document.getElementById('status-badge');
        if (statusBadge) {
            const status = formatStatus(order.status);
            statusBadge.textContent = status.text;
            
            // Remove all badge classes and add the appropriate one
            statusBadge.className = 'badge';
            statusBadge.classList.add(status.badgeClass);
        }
    }
    
    // If on orders page, check if we need to update the table
    if (document.getElementById('orders-table')) {
        const row = document.querySelector(`tr[data-order-id="${order.order_id}"]`);
        if (row) {
            // The order is currently displayed in the table
            // If the status changed in a way that would affect filters, refresh the whole table
            if (window.currentFilterState) {
                const filterState = window.currentFilterState.getValues();
                
                // Check if the status change affects the filter (e.g., moving to/from archived)
                const statusFiltered = filterState.status && order.status !== filterState.status;
                const archivedFiltered = !filterState.showArchived && order.status === 'archived';
                
                if (statusFiltered || archivedFiltered) {
                    // Order would no longer be visible with current filter, refresh table
                    refreshOrdersTable();
                }
            }
        }
    }
}

/**
 * Make processed events accessible to other modules
 */
export function getProcessedEvents() {
    return processedEvents;
}