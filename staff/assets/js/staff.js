// Staff Portal JavaScript Functions

// Track processed events to prevent duplicate notifications
window.processedEvents = new Set();

document.addEventListener('DOMContentLoaded', function() {
    // Automatically update order stats in dashboard
    refreshDashboardStats();

    // Setup handlers for status change
    setupStatusChangeHandlers();
    
    // Initialize Server-Sent Events connection
    initializeSSEConnection();
    
    // Setup filter form handlers
    setupFilterFormHandlers();
});

// Initialize Server-Sent Events for real-time updates
function initializeSSEConnection() {
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
                // Process each new order, tracking which ones we've seen
                newOrders.forEach(order => {
                    // Create a unique ID for this order
                    const orderUniqueId = `new_order_${order.order_id}`;
                    
                    // Skip if we've already processed this order
                    if (window.processedEvents.has(orderUniqueId)) {
                        console.log(`Skipping already processed order: ${orderUniqueId}`);
                        return;
                    }
                    
                    // Mark this order as processed
                    window.processedEvents.add(orderUniqueId);
                });
                
                // Only show notification and update UI if we have unprocessed orders
                const unprocessedOrders = newOrders.filter(order => {
                    return window.processedEvents.has(`new_order_${order.order_id}`);
                });
                
                if (unprocessedOrders.length > 0) {
                    // Show notification
                    showAlert(`${unprocessedOrders.length} new order(s) received!`, 'info');
                    
                    // Update dashboard recent orders if on dashboard page
                    if (document.querySelector('.orders-table')) {
                        updateDashboardOrders(newOrders);
                    }
                    
                    // Update orders table if on orders page
                    if (document.getElementById('orders-table')) {
                        updateOrdersTableWithNewOrders(newOrders);
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
                if (window.processedEvents.has(eventUniqueId)) {
                    console.log(`Skipping already processed event: ${eventUniqueId}`);
                    return;
                }
                
                // Mark this event as processed
                window.processedEvents.add(eventUniqueId);
                
                // Limit the size of the processed events set to prevent memory issues
                if (window.processedEvents.size > 200) {
                    // Convert to array, remove oldest entries, and convert back to Set
                    const eventsArray = Array.from(window.processedEvents);
                    window.processedEvents = new Set(eventsArray.slice(-100));
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
                    }
                    
                    // Show a notification
                    showAlert('New order received!', 'info');
                }
                
                // If this is a status change, update the UI as needed
                if (event.event_type === 'status_change') {
                    const orderId = eventData.order_id;
                    const newStatus = eventData.status;
                    
                    // Update status in dashboard table if it exists
                    const dashboardRow = document.querySelector(`.orders-table tr[data-order-id="${orderId}"]`);
                    if (dashboardRow) {
                        const statusBadge = dashboardRow.querySelector('.badge');
                        if (statusBadge) {
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                            statusBadge.className = `badge badge-${newStatus.replace(' ', '-')}`;
                        }
                    }
                    
                    // Update status in orders table if it exists
                    const ordersRow = document.querySelector(`#orders-table tr[data-order-id="${orderId}"]`);
                    if (ordersRow) {
                        const statusBadge = ordersRow.querySelector('.badge');
                        if (statusBadge) {
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                            statusBadge.className = `badge badge-${newStatus.replace(' ', '-')}`;
                        }
                    }
                    
                    // Update order details page if we're viewing this order
                    const statusSelect = document.getElementById('order-status-select');
                    if (statusSelect && statusSelect.getAttribute('data-order-id') == orderId) {
                        statusSelect.value = newStatus;
                        
                        const statusBadge = document.getElementById('status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                            statusBadge.className = `badge badge-${newStatus.replace(' ', '-')}`;
                        }
                    }
                }
            });
        }
    });
    
    evtSource.addEventListener('order_update', function(event) {
        const data = JSON.parse(event.data);
        console.log('Order updated:', data);
        
        if (data.order) {
            // If on the order details page, update UI elements
            const statusSelect = document.getElementById('order-status-select');
            if (statusSelect && parseInt(statusSelect.getAttribute('data-order-id')) === data.order.order_id) {
                statusSelect.value = data.order.status;
                
                const statusBadge = document.getElementById('status-badge');
                if (statusBadge) {
                    statusBadge.textContent = data.order.status.charAt(0).toUpperCase() + data.order.status.slice(1);
                    
                    // Remove all badge classes and add the appropriate one
                    statusBadge.className = '';
                    statusBadge.classList.add('badge', `badge-${data.order.status.replace(' ', '-')}`);
                }
                
                // Show a notification
                showAlert(`Order status updated to: ${data.order.status}`, 'info');
            }
            
            // If on orders page, check if we need to update the table
            if (document.getElementById('orders-table')) {
                const row = document.querySelector(`tr[data-order-id="${data.order.order_id}"]`);
                if (row) {
                    // The order is currently displayed in the table
                    // If the status changed in a way that would affect filters, refresh the whole table
                    if (window.currentFilterState) {
                        const filterState = window.currentFilterState.getValues();
                        
                        // Check if the status change affects the filter (e.g., moving to/from archived)
                        const statusFiltered = filterState.status && data.order.status !== filterState.status;
                        const archivedFiltered = !filterState.showArchived && data.order.status === 'archived';
                        
                        if (statusFiltered || archivedFiltered) {
                            // Order would no longer be visible with current filter, refresh table
                            refreshOrdersTable();
                        }
                    }
                }
            }
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

// Add a new helper function to update the orders table with new orders
function updateOrdersTableWithNewOrders(newOrders) {
    const ordersTableBody = document.querySelector('#orders-table tbody');
    if (!ordersTableBody) return;
    
    // For each new order, check if it already exists and apply filter rules
    newOrders.forEach(order => {
        // Check if this order is already in the table
        const existingOrderRow = ordersTableBody.querySelector(`tr[data-order-id="${order.order_id}"]`);
        if (existingOrderRow) return; // Skip if already exists
        
        // Check if the order should be visible based on current filters
        if (window.currentFilterState) {
            const filterState = window.currentFilterState.getValues();
            
            // Check if this order matches the current filter
            if (filterState.status && filterState.status !== 'all') {
                if (filterState.status === 'active') {
                    // "Active" means not archived
                    if (order.status === 'archived') return;
                } else if (filterState.status !== order.status) {
                    return; // Skip if status doesn't match filter
                }
            }
            
            // Skip archived orders if "showArchived" is false
            if (!filterState.showArchived && order.status === 'archived') {
                return;
            }
            
            // Date filter and search filter would require more complex logic
            // For simplicity, we're skipping those checks and assuming they pass
        }
        
        // If we got here, the order should be added to the table
        const row = document.createElement('tr');
        row.setAttribute('data-order-id', order.order_id);
        
        if (order.status === 'archived') {
            row.className = 'table-secondary';
        }
        
        // Format the date
        const orderDate = new Date(order.order_placed_time);
        const formattedDate = `${orderDate.toLocaleString('default', { month: 'short' })} ${orderDate.getDate()}, ${orderDate.getHours()}:${String(orderDate.getMinutes()).padStart(2, '0')}`;
        
        // Create customer contact info with email if available
        const contactInfo = order.customer_email 
            ? `${order.customer_phone}<br><small>${order.customer_email}</small>`
            : order.customer_phone;

        // Generate status-dependent dropdown menu
        let dropdownMenu = '';
        
        // Only show action dropdown for non-archived orders
        if (order.status !== 'archived') {
            let menuItems = '';
            
            // Show "Mark as Ready" only for preparing orders
            if (order.status === 'preparing') {
                menuItems += `
                    <li>
                        <button class="dropdown-item quick-status-change" data-order-id="${order.order_id}" data-status="ready">
                            <i class="bi bi-check-circle text-success"></i> Mark as Ready
                        </button>
                    </li>`;
            }
            
            // Show "Mark as Picked Up" only for ready orders
            if (order.status === 'ready') {
                menuItems += `
                    <li>
                        <button class="dropdown-item quick-status-change" data-order-id="${order.order_id}" data-status="picked up">
                            <i class="bi bi-bag-check text-secondary"></i> Mark as Picked Up
                        </button>
                    </li>`;
            }
            
            // Show "Cancel Order" for active orders only (not cancelled, picked up, or archived)
            if (order.status !== 'cancelled' && order.status !== 'picked up' && order.status !== 'archived') {
                menuItems += `
                    <li>
                        <button class="dropdown-item quick-status-change" data-order-id="${order.order_id}" data-status="cancelled">
                            <i class="bi bi-x-circle text-danger"></i> Cancel Order
                        </button>
                    </li>`;
            }
            
            // Only show archive option for non-archived orders
            if (menuItems) {
                menuItems += `
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item quick-status-change" data-order-id="${order.order_id}" data-status="archived">
                            <i class="bi bi-archive text-warning"></i> Archive Order
                        </button>
                    </li>`;
            }
            
            // Only create dropdown if there are menu items
            if (menuItems) {
                dropdownMenu = `
                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        ${menuItems}
                    </ul>`;
            }
        }

        row.innerHTML = `
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${contactInfo}</td>
            <td>${order.item_count}</td>
            <td>₹${parseFloat(order.order_total).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>
                <span class="badge badge-${order.status.replace(' ', '-')}">
                    ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                </span>
            </td>
            <td>
                <div class="btn-group">
                    <a href="?page=order-details&id=${order.order_id}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View
                    </a>
                    ${dropdownMenu}
                </div>
            </td>
        `;

        // Insert at the beginning of the table
        if (ordersTableBody.firstChild) {
            ordersTableBody.insertBefore(row, ordersTableBody.firstChild);
        } else {
            ordersTableBody.appendChild(row);
        }
        
        // If there was a "No orders found" message, remove it
        const noOrdersRow = ordersTableBody.querySelector('tr td[colspan="8"].text-center');
        if (noOrdersRow) {
            noOrdersRow.closest('tr').remove();
        }
    });
    
    // Re-attach event listeners for the new buttons
    setupStatusChangeHandlers();
}

// Helper function to update the dashboard recent orders
function updateDashboardOrders(newOrders) {
    const recentOrdersTable = document.querySelector('.orders-table tbody');
    if (!recentOrdersTable) return;

    // For each new order, add it to the dashboard
    newOrders.forEach(order => {
        // Check if this order is already in the table
        const existingOrderRow = Array.from(recentOrdersTable.querySelectorAll('tr')).find(row => {
            const orderLink = row.querySelector('td:last-child a');
            if (orderLink) {
                const href = orderLink.getAttribute('href');
                const existingOrderId = href.match(/id=(\d+)/)?.[1];
                return existingOrderId == order.order_id;
            }
            return false;
        });
        
        if (existingOrderRow) {
            // Order already exists in the table, don't add it again
            return;
        }
        
        // Create a new row for this order
        const row = document.createElement('tr');
        row.setAttribute('data-order-id', order.order_id);
        
        // Format the date
        const orderDate = new Date(order.order_placed_time);
        const formattedDate = `${orderDate.toLocaleString('default', { month: 'short' })} ${orderDate.getDate()}, ${orderDate.getHours()}:${String(orderDate.getMinutes()).padStart(2, '0')}`;
        
        row.innerHTML = `
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${order.item_count}</td>
            <td>₹${parseFloat(order.order_total).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>
                <span class="badge badge-${order.status.replace(' ', '-')}">
                    ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                </span>
            </td>
            <td>
                <a href="?page=order-details&id=${order.order_id}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View
                </a>
            </td>
        `;
        
        // Insert at the beginning of the table
        if (recentOrdersTable.firstChild) {
            recentOrdersTable.insertBefore(row, recentOrdersTable.firstChild);
        } else {
            recentOrdersTable.appendChild(row);
        }
        
        // Remove the last row if we now have more than 5 rows
        const allRows = recentOrdersTable.querySelectorAll('tr');
        if (allRows.length > 5) {
            recentOrdersTable.removeChild(allRows[allRows.length - 1]);
        }
    });
}

// Helper function to refresh the orders table with current filters
function refreshOrdersTable() {
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
            }
        })
        .catch(error => console.error('Error fetching updated orders:', error));
}

// Refresh dashboard stats periodically
function refreshDashboardStats() {
    if (document.getElementById('dashboard-stats')) {
        updateOrderStats();
        // Update stats every 30 seconds
        setInterval(updateOrderStats, 30000);
    }
}

// Update order statistics on the dashboard
function updateOrderStats(skipAnimation = false) {
    fetch('api/get_order_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsDisplay('preparing-count', data.stats.preparing || 0, skipAnimation);
                updateStatsDisplay('ready-count', data.stats.ready || 0, skipAnimation);
                updateStatsDisplay('picked-up-count', data.stats.picked_up || 0, skipAnimation);
                updateStatsDisplay('total-orders-count', data.stats.total || 0, skipAnimation);
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}

// Update stats display with optional animation
function updateStatsDisplay(elementId, newValue, skipAnimation = false) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const currentValue = parseInt(element.textContent) || 0;
    
    if (currentValue === newValue) return;
    
    if (skipAnimation) {
        element.textContent = newValue;
        return;
    }
    
    // Simple animation for number change
    let start = currentValue;
    const end = newValue;
    const duration = 1000; // 1 second animation
    const startTime = new Date().getTime();
    
    // Highlight the box if value increases
    if (newValue > currentValue) {
        const statBox = element.closest('.stat-box');
        if (statBox) {
            statBox.style.transition = 'box-shadow 0.5s';
            statBox.style.boxShadow = '0 0 10px rgba(0, 123, 255, 0.5)';
            setTimeout(() => {
                statBox.style.boxShadow = '';
            }, 1000);
        }
    }
    
    const timer = setInterval(function() {
        const timeElapsed = new Date().getTime() - startTime;
        const progress = timeElapsed / duration;
        
        if (progress >= 1) {
            clearInterval(timer);
            element.textContent = end;
            return;
        }
        
        const currentNumber = Math.round(start + (end - start) * progress);
        element.textContent = currentNumber;
    }, 16);
}

// Setup handlers for order status changes
function setupStatusChangeHandlers() {
    // For order details page
    const statusSelect = document.getElementById('order-status-select');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = this.value;
            updateOrderStatus(orderId, newStatus);
        });
    }

    // For quick status changes on orders page
    document.querySelectorAll('.quick-status-change').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = this.getAttribute('data-status');
            updateOrderStatus(orderId, newStatus);
        });
    });
}

// Setup filter form handlers for orders page
function setupFilterFormHandlers() {
    // Check if we're on the orders page
    const filterForm = document.querySelector('form[action=""]');
    if (filterForm && document.getElementById('orders-table')) {
        // Restore filter state from session storage if it exists
        const savedState = sessionStorage.getItem('ordersFilterState');
        if (savedState) {
            try {
                const filterState = JSON.parse(savedState);
                
                // Apply saved filter state to form elements
                const statusFilter = document.getElementById('status-filter');
                if (statusFilter && filterState.status) {
                    statusFilter.value = filterState.status;
                }
                
                const dateFilter = document.getElementById('date-filter');
                if (dateFilter && filterState.date) {
                    dateFilter.value = filterState.date;
                }
                
                const searchFilter = document.getElementById('search-filter');
                if (searchFilter && filterState.search) {
                    searchFilter.value = filterState.search;
                }
                
                const showArchivedCheckbox = document.getElementById('show-archived');
                if (showArchivedCheckbox) {
                    showArchivedCheckbox.checked = filterState.showArchived;
                }
            } catch (e) {
                console.error('Error restoring filter state:', e);
                // Clear invalid state
                sessionStorage.removeItem('ordersFilterState');
            }
        }
        
        // Handle status filter changes to enable/disable show archived checkbox as needed
        const statusFilter = document.getElementById('status-filter');
        const showArchivedCheckbox = document.getElementById('show-archived');
        
        if (statusFilter && showArchivedCheckbox) {
            statusFilter.addEventListener('change', function() {
                // Disable "Show Archived" checkbox when "All Orders" or "Archived" is selected
                if (this.value === 'all' || this.value === 'archived') {
                    showArchivedCheckbox.disabled = true;
                    
                    // If we're selecting "all", implicitly we're showing archived
                    // If we're selecting "archived", we're explicitly filtering for archived only
                    showArchivedCheckbox.checked = (this.value === 'all');
                } else {
                    showArchivedCheckbox.disabled = false;
                }
            });
        }
        
        // Handle the "show archived" checkbox change
        if (showArchivedCheckbox) {
            showArchivedCheckbox.addEventListener('change', function() {
                // Save current filter state before submitting
                saveFilterState();
                filterForm.submit();
            });
        }
        
        // Handle the reset button - make sure it clears all filters
        const resetButton = filterForm.querySelector('a.btn-outline-secondary');
        if (resetButton) {
            resetButton.addEventListener('click', function(e) {
                e.preventDefault();
                // Clear all form inputs
                const statusFilter = document.getElementById('status-filter');
                if (statusFilter) statusFilter.value = 'active';
                
                const dateFilter = document.getElementById('date-filter');
                if (dateFilter) dateFilter.value = '';
                
                const searchFilter = document.getElementById('search-filter');
                if (searchFilter) searchFilter.value = '';
                
                // Clear saved filter state
                sessionStorage.removeItem('ordersFilterState');
                
                // Submit the form with reset values
                window.location.href = '?page=orders';
            });
        }
        
        // Store the current filter state in browser storage
        const saveFilterState = function() {
            const filterState = {
                status: document.getElementById('status-filter')?.value || '',
                date: document.getElementById('date-filter')?.value || '',
                search: document.getElementById('search-filter')?.value || '',
                showArchived: document.getElementById('show-archived')?.checked || false
            };
            sessionStorage.setItem('ordersFilterState', JSON.stringify(filterState));
        };
        
        // Store filter state when filter button is clicked
        const filterButton = filterForm.querySelector('button[type="submit"]');
        if (filterButton) {
            filterButton.addEventListener('click', function() {
                saveFilterState();
            });
        }
        
        // Store filter state in a variable accessible outside the function
        window.currentFilterState = {
            getValues: function() {
                return {
                    status: document.getElementById('status-filter')?.value || '',
                    date: document.getElementById('date-filter')?.value || '',
                    search: document.getElementById('search-filter')?.value || '',
                    showArchived: document.getElementById('show-archived')?.checked || false
                };
            }
        };
    }
}

// Update order status via API
function updateOrderStatus(orderId, newStatus) {
    if (!orderId || !newStatus) return;

    // Update UI immediately before the API call completes to provide instant feedback
    // Find any dropdown buttons that triggered this status change
    const triggerButton = document.querySelector(`.quick-status-change[data-order-id="${orderId}"][data-status="${newStatus}"]`);
    if (triggerButton) {
        // Find the containing row
        const row = triggerButton.closest('tr');
        if (row) {
            // Update the badge in the row
            const statusBadge = row.querySelector('.badge');
            if (statusBadge) {
                statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                statusBadge.className = `badge badge-${newStatus.replace(' ', '-')}`;
            }
            
            // Reconstruct the dropdown with the new status - immediately update available actions
            const actionCell = row.querySelector('td:last-child');
            if (actionCell) {
                // Store the button group that contains the View button
                const btnGroup = actionCell.querySelector('.btn-group');
                if (btnGroup) {
                    // Recreate the action buttons based on the new status
                    let menuItems = '';
                    
                    // Show "Mark as Ready" only for preparing orders
                    if (newStatus === 'preparing') {
                        menuItems += `
                            <li>
                                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="ready">
                                    <i class="bi bi-check-circle text-success"></i> Mark as Ready
                                </button>
                            </li>`;
                    }
                    
                    // Show "Mark as Picked Up" only for ready orders
                    if (newStatus === 'ready') {
                        menuItems += `
                            <li>
                                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="picked up">
                                    <i class="bi bi-bag-check text-secondary"></i> Mark as Picked Up
                                </button>
                            </li>`;
                    }
                    
                    // Show "Cancel Order" for active orders only (not cancelled, picked up, or archived)
                    if (newStatus !== 'cancelled' && newStatus !== 'picked up' && newStatus !== 'archived') {
                        menuItems += `
                            <li>
                                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="cancelled">
                                    <i class="bi bi-x-circle text-danger"></i> Cancel Order
                                </button>
                            </li>`;
                    }
                    
                    // Only show archive option for non-archived orders
                    if (newStatus !== 'archived') {
                        if (menuItems) {
                            menuItems += `<li><hr class="dropdown-divider"></li>`;
                        }
                        menuItems += `
                            <li>
                                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="archived">
                                    <i class="bi bi-archive text-warning"></i> Archive Order
                                </button>
                            </li>`;
                    }
                    
                    // Update the dropdown menu with new options
                    if (menuItems) {
                        btnGroup.innerHTML = `
                            <a href="?page=order-details&id=${orderId}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                ${menuItems}
                            </ul>`;
                    } else {
                        // If there are no actions available (e.g., for archived orders), only show View button
                        btnGroup.innerHTML = `
                            <a href="?page=order-details&id=${orderId}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>`;
                    }
                    
                    // Re-attach event listeners to the new buttons
                    btnGroup.querySelectorAll('.quick-status-change').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const btnOrderId = this.getAttribute('data-order-id');
                            const btnNewStatus = this.getAttribute('data-status');
                            updateOrderStatus(btnOrderId, btnNewStatus);
                        });
                    });
                }
            }
        }
    }

    // Also update action buttons on order details page if we're there
    const statusSelect = document.getElementById('order-status-select');
    if (statusSelect && statusSelect.getAttribute('data-order-id') == orderId) {
        statusSelect.value = newStatus;
        
        const statusBadge = document.getElementById('status-badge');
        if (statusBadge) {
            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            statusBadge.classList.remove('badge-preparing', 'badge-ready', 'badge-picked-up', 'badge-cancelled', 'badge-archived');
            statusBadge.classList.add(`badge-${newStatus.replace(' ', '-')}`);
        }
        
        // Update action buttons in order details page
        const actionsContainer = document.querySelector('.card .d-grid.gap-2');
        if (actionsContainer) {
            let actionButtons = '';
            
            if (newStatus === 'preparing') {
                actionButtons += `
                    <button class="btn btn-success quick-status-change" data-order-id="${orderId}" data-status="ready">
                        <i class="bi bi-check-circle"></i> Mark as Ready
                    </button>`;
            }
            
            if (newStatus === 'ready') {
                actionButtons += `
                    <button class="btn btn-secondary quick-status-change" data-order-id="${orderId}" data-status="picked up">
                        <i class="bi bi-bag-check"></i> Mark as Picked Up
                    </button>`;
            }
            
            if (newStatus !== 'cancelled' && newStatus !== 'picked up' && newStatus !== 'archived') {
                actionButtons += `
                    <button class="btn btn-danger quick-status-change" data-order-id="${orderId}" data-status="cancelled">
                        <i class="bi bi-x-circle"></i> Cancel Order
                    </button>`;
            }
            
            actionsContainer.innerHTML = actionButtons;
            
            // Re-attach event listeners
            actionsContainer.querySelectorAll('.quick-status-change').forEach(btn => {
                btn.addEventListener('click', function() {
                    const btnOrderId = this.getAttribute('data-order-id');
                    const btnNewStatus = this.getAttribute('data-status');
                    updateOrderStatus(btnOrderId, btnNewStatus);
                });
            });
        }
    }

    fetch('api/update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // If we're on the orders list page, show success alert and trigger refresh
            if (document.getElementById('orders-table')) {
                showAlert('Order status updated successfully!', 'success');
                
                // Refresh the table data - still needed for any other orders that might have changed
                // but now it won't cause UI issues since we've already updated this specific order
                fetch('api/get_orders_list.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateOrdersTable(data.orders);
                        }
                    })
                    .catch(error => console.error('Error fetching updated orders:', error));
            } 
            // If we're on the order details page, show success alert
            else if (document.getElementById('status-badge')) {
                showAlert('Order status updated successfully!', 'success');
            }
        } else {
            showAlert('Error updating order status: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update order status. Please try again.', 'danger');
    });
}

// Display an alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Find the alert container or create one
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.className = 'container-fluid mt-3';
        
        const mainContent = document.querySelector('main');
        if (mainContent) {
            mainContent.prepend(alertContainer);
        }
    }
    
    alertContainer.appendChild(alertDiv);
    
    // Auto-close after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Update orders table dynamically
function updateOrdersTable(orders) {
    const ordersTableBody = document.querySelector('#orders-table tbody');
    if (!ordersTableBody) return;

    // Clear existing rows
    ordersTableBody.innerHTML = '';

    if (orders.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = '<td colspan="8" class="text-center text-muted my-4">No orders found matching your criteria.</td>';
        ordersTableBody.appendChild(emptyRow);
        return;
    }

    orders.forEach(order => {
        const row = document.createElement('tr');
        row.setAttribute('data-order-id', order.order_id);
        
        if (order.status === 'archived') {
            row.className = 'table-secondary';
        }

        // Format the date
        const orderDate = new Date(order.order_placed_time);
        const formattedDate = `${orderDate.toLocaleString('default', { month: 'short' })} ${orderDate.getDate()}, ${orderDate.getHours()}:${String(orderDate.getMinutes()).padStart(2, '0')}`;
        
        // Create customer contact info with email if available
        const contactInfo = order.customer_email 
            ? `${order.customer_phone}<br><small>${order.customer_email}</small>`
            : order.customer_phone;

        // Generate status-dependent dropdown menu
        let dropdownMenu = '';
        
        // Only show action dropdown for non-archived orders
        if (order.status !== 'archived') {
            let menuItems = '';
            
            // Show "Mark as Ready" only for preparing orders
            if (order.status === 'preparing') {
                menuItems += `
                    <li>
                        <button class="dropdown-item quick-status-change" data-order-id="${order.order_id}" data-status="ready">
                            <i class="bi bi-check-circle text-success"></i> Mark as Ready
                        </button>
                    </li>`;
            }
            
            // Show "Mark as Picked Up" only for ready orders
            if (order.status === 'ready') {
                menuItems += `
                    <li>
                        <button class="dropdown-item quick-status-change" data-order-id="${order.order_id}" data-status="picked up">
                            <i class="bi bi-bag-check text-secondary"></i> Mark as Picked Up
                        </button>
                    </li>`;
            }
            
            // Show "Cancel Order" for active orders only (not cancelled, picked up, or archived)
            if (order.status !== 'cancelled' && order.status !== 'picked up' && order.status !== 'archived') {
                menuItems += `
                    <li>
                        <button class="dropdown-item quick-status-change" data-order-id="${order.order_id}" data-status="cancelled">
                            <i class="bi bi-x-circle text-danger"></i> Cancel Order
                        </button>
                    </li>`;
            }
            
            // Only show archive option for non-archived orders
            if (menuItems) {
                menuItems += `
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item quick-status-change" data-order-id="${order.order_id}" data-status="archived">
                            <i class="bi bi-archive text-warning"></i> Archive Order
                        </button>
                    </li>`;
            }
            
            // Only create dropdown if there are menu items
            if (menuItems) {
                dropdownMenu = `
                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        ${menuItems}
                    </ul>`;
            }
        }

        row.innerHTML = `
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${contactInfo}</td>
            <td>${order.item_count}</td>
            <td>₹${parseFloat(order.order_total).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>
                <span class="badge badge-${order.status.replace(' ', '-')}">
                    ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                </span>
            </td>
            <td>
                <div class="btn-group">
                    <a href="?page=order-details&id=${order.order_id}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View
                    </a>
                    ${dropdownMenu}
                </div>
            </td>
        `;

        ordersTableBody.appendChild(row);
    });
    
    // Re-attach event listeners for the new buttons
    setupStatusChangeHandlers();
}

// Refresh dashboard orders dynamically
function refreshDashboardOrders(orders) {
    const recentOrdersTable = document.querySelector('.orders-table tbody');
    if (!recentOrdersTable) return;

    // Clear existing rows
    recentOrdersTable.innerHTML = '';

    // Limit to 5 most recent orders
    const activeOrders = orders.filter(order => order.status !== 'archived');
    const recentOrders = activeOrders.slice(0, 5);

    recentOrders.forEach(order => {
        const row = document.createElement('tr');
        row.setAttribute('data-order-id', order.order_id);

        // Format the date
        const orderDate = new Date(order.order_placed_time);
        const formattedDate = `${orderDate.toLocaleString('default', { month: 'short' })} ${orderDate.getDate()}, ${orderDate.getHours()}:${String(orderDate.getMinutes()).padStart(2, '0')}`;

        row.innerHTML = `
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${order.item_count}</td>
            <td>₹${parseFloat(order.order_total).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>
                <span class="badge badge-${order.status.replace(' ', '-')}">
                    ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                </span>
            </td>
            <td>
                <a href="?page=order-details&id=${order.order_id}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View
                </a>
            </td>
        `;

        recentOrdersTable.appendChild(row);
    });
}