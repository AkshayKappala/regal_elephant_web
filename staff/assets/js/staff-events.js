import { updateOrderStats } from './staff-ui.js';
import { updateDashboardOrders, refreshDashboardOrders, updateOrdersTableWithNewOrders, refreshOrdersTable } from './staff-orders.js';
import { formatStatus } from './staff-utils.js';

const processedEvents = new Set();
const processedOrders = new Set(
    JSON.parse(localStorage.getItem('processedOrders') || '[]')
);

function saveProcessedOrders() {
    const ordersArray = Array.from(processedOrders);
    const limitedArray = ordersArray.slice(-500);
    localStorage.setItem('processedOrders', JSON.stringify(limitedArray));
}

export function initializeSSEConnection() {
    if (typeof EventSource === 'undefined') {
        return;
    }
    
    const sseEndpoint = '../api/order_events.php?client=staff';
    const evtSource = new EventSource(sseEndpoint);
    
    evtSource.addEventListener('connection', function(event) {
        const data = JSON.parse(event.data);
    });
    
    evtSource.addEventListener('orders_update', function(event) {
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
                    if (document.querySelector('.orders-table')) {
                        updateDashboardOrders(unprocessedOrders);
                    }
                    
                    if (document.getElementById('orders-table')) {
                        updateOrdersTableWithNewOrders(unprocessedOrders);
                    }
                }
            }
            
            const otherOrders = data.orders.filter(order => order.status !== 'preparing');
            if (otherOrders.length > 0) {
                if (document.querySelector('.orders-table')) {
                    updateDashboardOrders(otherOrders);
                }
                
                if (document.getElementById('orders-table')) {
                    updateOrdersTableWithNewOrders(otherOrders);
                }
            }
        }
    });
    
    evtSource.addEventListener('events_update', function(event) {
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
                    if (document.getElementById('dashboard-stats')) {
                        updateOrderStats(true);
                    }
                    
                    if (document.querySelector('.orders-table')) {
                        fetch('api/get_orders_list.php?limit=5')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    refreshDashboardOrders(data.orders);
                                }
                            })
                            .catch(error => {});
                    }
                    
                    if (document.getElementById('orders-table')) {
                        refreshOrdersTable();
                        
                        const orderUniqueId = `new_order_${eventData.order_id}`;
                        if (!processedOrders.has(orderUniqueId)) {
                            processedOrders.add(orderUniqueId);
                            saveProcessedOrders();
                        }
                    }
                }
                
                if (event.event_type === 'status_change') {
                    handleStatusChangeEvent(eventData);
                }
            });
        }
    });
    
    evtSource.addEventListener('order_update', function(event) {
        const data = JSON.parse(event.data);
        
        if (data.order) {
            handleOrderUpdateEvent(data.order);
        }
    });
    
    evtSource.addEventListener('ping', function(event) {
        // Keep-alive event
    });
    
    evtSource.onerror = function(error) {
        setTimeout(function() {
            initializeSSEConnection();
        }, 5000);
    };
}

function handleStatusChangeEvent(eventData) {
    const orderId = eventData.order_id;
    const newStatus = eventData.status;
    
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

export function getProcessedEvents() {
    return processedEvents;
}