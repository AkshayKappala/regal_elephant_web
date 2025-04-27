import { updateOrderStats } from './staff-ui.js';
import { updateDashboardOrders, refreshDashboardOrders, updateOrdersTableWithNewOrders, refreshOrdersTable } from './staff-orders.js';
import { formatStatus } from './staff-utils.js';

const processedEvents = new Set();
const processedOrders = new Set(
    JSON.parse(localStorage.getItem('processedOrders') || '[]')
);

let evtSource = null;
const MAX_RECONNECT_ATTEMPTS = 5;
let reconnectAttempts = 0;
let reconnectTimeout = null;

function saveProcessedOrders() {
    const ordersArray = Array.from(processedOrders);
    const limitedArray = ordersArray.slice(-500);
    localStorage.setItem('processedOrders', JSON.stringify(limitedArray));
}

export function initializeSSEConnection() {
    if (typeof EventSource === 'undefined') {
        console.log('This browser does not support Server-Sent Events. Using polling instead.');
        // Fallback to polling
        setInterval(() => {
            refreshOrdersTable();
            if (document.getElementById('dashboard-stats')) {
                updateOrderStats(true);
            }
        }, 30000);
        return;
    }
    
    // Close existing connection if any
    if (evtSource) {
        evtSource.close();
    }
    
    try {
        console.log('Establishing SSE connection for staff interface...');
        const sseEndpoint = `../api/order_events.php?client=staff&_nocache=${Date.now()}`;
        evtSource = new EventSource(sseEndpoint);
        
        evtSource.addEventListener('connection', function(event) {
            console.log('SSE connection established for staff interface');
            reconnectAttempts = 0; // Reset reconnect attempts
            
            // Refresh data on connection to ensure we have the latest
            if (document.getElementById('orders-table')) {
                refreshOrdersTable();
            }
            
            if (document.getElementById('dashboard-stats')) {
                updateOrderStats(true);
            }
        });
        
        evtSource.addEventListener('orders_update', function(event) {
            console.log('Received orders_update event:', event.data);
            const data = JSON.parse(event.data);
            
            if (data.orders && data.orders.length > 0) {
                if (document.getElementById('dashboard-stats')) {
                    updateOrderStats(true);
                }
                
                const newOrders = data.orders.filter(order => order.status === 'preparing');
                if (newOrders.length > 0) {
                    const unprocessedOrders = newOrders.filter(order => {
                        const orderUniqueId = `new_order_${order.order_id}`;
                        if (processedOrders.has(orderUniqueId)) {
                            return false;
                        }
                        
                        processedOrders.add(orderUniqueId);
                        saveProcessedOrders();
                        return true;
                    });
                    
                    if (unprocessedOrders.length > 0) {
                        // Play notification sound for new orders
                        playNotificationSound();
                        
                        if (document.querySelector('.orders-table')) {
                            console.log('Updating dashboard with new orders:', unprocessedOrders);
                            updateDashboardOrders(unprocessedOrders);
                        }
                        
                        if (document.getElementById('orders-table')) {
                            console.log('Updating orders table with new orders:', unprocessedOrders);
                            updateOrdersTableWithNewOrders(unprocessedOrders);
                        }
                    }
                }
                
                const otherOrders = data.orders.filter(order => order.status !== 'preparing');
                if (otherOrders.length > 0) {
                    if (document.querySelector('.orders-table')) {
                        console.log('Updating dashboard with other orders:', otherOrders);
                        updateDashboardOrders(otherOrders);
                    }
                    
                    if (document.getElementById('orders-table')) {
                        console.log('Updating orders table with other orders:', otherOrders);
                        updateOrdersTableWithNewOrders(otherOrders);
                    }
                }
            }
        });
        
        evtSource.addEventListener('events_update', function(event) {
            console.log('Received events_update event:', event.data);
            const data = JSON.parse(event.data);
            
            if (data.events && data.events.length > 0) {
                data.events.forEach(event => {
                    const eventUniqueId = `${event.event_type}_${event.event_id}`;
                    
                    if (processedEvents.has(eventUniqueId)) {
                        return;
                    }
                    
                    processedEvents.add(eventUniqueId);
                    
                    if (processedEvents.size > 200) {
                        const eventsArray = Array.from(processedEvents);
                        processedEvents.clear();
                        eventsArray.slice(-100).forEach(id => processedEvents.add(id));
                    }
                    
                    const eventData = JSON.parse(event.event_data);
                    
                    if (event.event_type === 'new_order') {
                        // Play notification sound for new orders
                        playNotificationSound();
                        
                        if (document.getElementById('dashboard-stats')) {
                            updateOrderStats(true);
                        }
                        
                        if (document.querySelector('.orders-table')) {
                            console.log('Updating dashboard due to new_order event');
                            fetch('api/get_orders_list.php?limit=5&_nocache=' + Date.now())
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        refreshDashboardOrders(data.orders);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error fetching dashboard orders:', error);
                                });
                        }
                        
                        if (document.getElementById('orders-table')) {
                            console.log('Refreshing orders table due to new_order event');
                            refreshOrdersTable();
                            
                            const orderUniqueId = `new_order_${eventData.order_id}`;
                            if (!processedOrders.has(orderUniqueId)) {
                                processedOrders.add(orderUniqueId);
                                saveProcessedOrders();
                            }
                        }
                    }
                    
                    if (event.event_type === 'status_change') {
                        console.log('Handling status change event:', eventData);
                        handleStatusChangeEvent(eventData);
                        
                        // Force refresh the orders table and dashboard
                        if (document.getElementById('orders-table')) {
                            refreshOrdersTable();
                        }
                        
                        if (document.querySelector('.orders-table')) {
                            fetch('api/get_orders_list.php?limit=5&_nocache=' + Date.now())
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        refreshDashboardOrders(data.orders);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error fetching dashboard orders:', error);
                                });
                        }
                    }
                });
            }
        });
        
        evtSource.addEventListener('order_update', function(event) {
            console.log('Received order_update event:', event.data);
            const data = JSON.parse(event.data);
            
            if (data.order) {
                // Always update the order stats if we're on the dashboard
                if (document.getElementById('dashboard-stats')) {
                    updateOrderStats(true);
                    
                    // Also update the dashboard orders table if it exists
                    if (document.querySelector('.orders-table')) {
                        // Refresh the dashboard with the latest orders
                        fetch('api/get_orders_list.php?limit=5&_nocache=' + Date.now())
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    console.log('Refreshing dashboard orders with:', result.orders);
                                    refreshDashboardOrders(result.orders);
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching updated dashboard orders:', error);
                            });
                    }
                }
                
                // Update the order in the orders table
                handleOrderUpdateEvent(data.order);
                
                // Force refresh the orders table for good measure
                if (document.getElementById('orders-table')) {
                    refreshOrdersTable();
                }
            }
        });
        
        evtSource.addEventListener('ping', function(event) {
            // Keep-alive event
            console.log('Ping received, connection active');
        });
        
        evtSource.onerror = function(error) {
            console.error('SSE connection error:', error);
            
            // Close current connection
            if (evtSource) {
                evtSource.close();
                evtSource = null;
            }
            
            // Try to reconnect with exponential backoff
            if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
                console.log(`Attempting to reconnect in ${delay/1000} seconds... (Attempt ${reconnectAttempts + 1}/${MAX_RECONNECT_ATTEMPTS})`);
                
                clearTimeout(reconnectTimeout);
                reconnectTimeout = setTimeout(() => {
                    reconnectAttempts++;
                    initializeSSEConnection();
                }, delay);
            } else {
                console.error('Maximum SSE reconnection attempts reached. Falling back to polling.');
                // Fall back to polling
                setInterval(() => {
                    refreshOrdersTable();
                    if (document.getElementById('dashboard-stats')) {
                        updateOrderStats(true);
                    }
                }, 30000);
            }
        };
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // Reconnect if the connection was closed when the page was hidden
                if (!evtSource || evtSource.readyState === EventSource.CLOSED) {
                    console.log('Page became visible, reconnecting SSE...');
                    reconnectAttempts = 0; // Reset reconnect attempts
                    initializeSSEConnection();
                }
            }
        });
        
        // Clean up on page unload
        window.addEventListener('beforeunload', function() {
            if (evtSource) {
                evtSource.close();
                evtSource = null;
            }
            clearTimeout(reconnectTimeout);
        });
        
    } catch (err) {
        console.error('Failed to initialize SSE connection:', err);
        // Fall back to polling
        setInterval(() => {
            refreshOrdersTable();
            if (document.getElementById('dashboard-stats')) {
                updateOrderStats(true);
            }
        }, 30000);
    }
}

function handleStatusChangeEvent(eventData) {
    const orderId = eventData.order_id;
    const newStatus = eventData.status;
    
    console.log(`Handling status change for order ${orderId} to ${newStatus}`);
    
    const dashboardRow = document.querySelector(`.orders-table tr[data-order-id="${orderId}"]`);
    if (dashboardRow) {
        const statusBadge = dashboardRow.querySelector('.badge');
        if (statusBadge) {
            const status = formatStatus(newStatus);
            statusBadge.textContent = status.text;
            statusBadge.className = `badge ${status.badgeClass}`;
        }
    }
    
    const ordersRow = document.querySelector(`#orders-table tr[data-order-id="${orderId}"]`);
    if (ordersRow) {
        const statusBadge = ordersRow.querySelector('.badge');
        if (statusBadge) {
            const status = formatStatus(newStatus);
            statusBadge.textContent = status.text;
            statusBadge.className = `badge ${status.badgeClass}`;
        }
    }
    
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

function handleOrderUpdateEvent(order) {
    console.log(`Handling order update for order ${order.order_id}`);
    
    const statusSelect = document.getElementById('order-status-select');
    if (statusSelect && parseInt(statusSelect.getAttribute('data-order-id')) === order.order_id) {
        statusSelect.value = order.status;
        
        const statusBadge = document.getElementById('status-badge');
        if (statusBadge) {
            const status = formatStatus(order.status);
            statusBadge.textContent = status.text;
            
            statusBadge.className = 'badge';
            statusBadge.classList.add(status.badgeClass);
        }
    }
    
    if (document.getElementById('orders-table')) {
        const row = document.querySelector(`tr[data-order-id="${order.order_id}"]`);
        if (row) {
            if (window.currentFilterState) {
                const filterState = window.currentFilterState.getValues();
                
                const statusFiltered = filterState.status && order.status !== filterState.status;
                const archivedFiltered = !filterState.showArchived && order.status === 'archived';
                
                if (statusFiltered || archivedFiltered) {
                    refreshOrdersTable();
                }
            }
        }
    }
}

function playNotificationSound() {
    try {
        const soundEnabled = localStorage.getItem('notificationSound') !== 'disabled';
        if (soundEnabled) {
            const audio = new Audio('../assets/audio/notification.mp3');
            audio.volume = 0.5;
            audio.play().catch(err => {
                console.log('Could not play notification sound:', err);
            });
        }
    } catch (e) {
        console.log('Error playing notification sound:', e);
    }
}

export function getProcessedEvents() {
    return processedEvents;
}