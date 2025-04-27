/**
 * Orders functionality for the staff portal
 */
import { formatDate, formatStatus, formatContactInfo, isActiveStatus } from './staff-utils.js';

/**
 * Setup handlers for order status changes
 */
export function setupStatusChangeHandlers() {
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

/**
 * Update order status via API
 * @param {string|number} orderId - The ID of the order to update
 * @param {string} newStatus - The new status to set
 */
export function updateOrderStatus(orderId, newStatus) {
    if (!orderId || !newStatus) return;

    // Update UI immediately for better user experience
    updateOrderStatusUI(orderId, newStatus);

    // Send API request to update status on the server
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
            // If we're on the orders list page, trigger a refresh
            if (document.getElementById('orders-table')) {
                // Refresh the table data - needed for any other orders that might have changed
                refreshOrdersTable();
            }
        } else {
            console.error('Error updating order status:', data.error || 'Unknown error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Update the order status in the UI without waiting for API response
 * @param {string|number} orderId - The ID of the order to update
 * @param {string} newStatus - The new status to set
 */
function updateOrderStatusUI(orderId, newStatus) {
    // Find any dropdown buttons that triggered this status change
    const triggerButton = document.querySelector(`.quick-status-change[data-order-id="${orderId}"][data-status="${newStatus}"]`);
    if (triggerButton) {
        // Find the containing row
        const row = triggerButton.closest('tr');
        if (row) {
            // Update the badge in the row
            const statusBadge = row.querySelector('.badge');
            if (statusBadge) {
                const status = formatStatus(newStatus);
                statusBadge.textContent = status.text;
                statusBadge.className = `badge ${status.badgeClass}`;
            }
            
            // Reconstruct the dropdown with the new status
            const actionCell = row.querySelector('td:last-child');
            if (actionCell) {
                const btnGroup = actionCell.querySelector('.btn-group');
                if (btnGroup) {
                    btnGroup.innerHTML = generateActionButtonsHTML(orderId, newStatus);
                    
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
            const status = formatStatus(newStatus);
            statusBadge.textContent = status.text;
            statusBadge.classList.remove('badge-preparing', 'badge-ready', 'badge-picked-up', 'badge-cancelled', 'badge-archived');
            statusBadge.classList.add(status.badgeClass);
        }
        
        // Update action buttons in order details page
        const actionsContainer = document.querySelector('.card .d-grid.gap-2');
        if (actionsContainer) {
            actionsContainer.innerHTML = generateDetailPageButtonsHTML(orderId, newStatus);
            
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
}

/**
 * Generate HTML for action buttons on order list page
 * @param {string|number} orderId - The order ID
 * @param {string} status - The current order status
 * @returns {string} HTML for the action buttons
 */
export function generateActionButtonsHTML(orderId, status) {
    // Base view button that's always shown
    let html = `
        <a href="?page=order-details&id=${orderId}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-eye"></i> View
        </a>`;
    
    // For archived orders, only show view button
    if (status === 'archived') {
        return html;
    }
    
    let menuItems = '';
    
    // Show "Mark as Ready" only for preparing orders
    if (status === 'preparing') {
        menuItems += `
            <li>
                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="ready">
                    <i class="bi bi-check-circle text-success"></i> Mark as Ready
                </button>
            </li>`;
    }
    
    // Show "Mark as Picked Up" only for ready orders
    if (status === 'ready') {
        menuItems += `
            <li>
                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="picked up">
                    <i class="bi bi-bag-check text-secondary"></i> Mark as Picked Up
                </button>
            </li>`;
    }
    
    // Show "Cancel Order" for active orders only (not cancelled, picked up, or archived)
    if (isActiveStatus(status)) {
        menuItems += `
            <li>
                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="cancelled">
                    <i class="bi bi-x-circle text-danger"></i> Cancel Order
                </button>
            </li>`;
    }
    
    // Only show archive option for non-archived orders
    if (status !== 'archived') {
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
    
    // Only add dropdown if there are menu items
    if (menuItems) {
        html += `
            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                ${menuItems}
            </ul>`;
    }
    
    return html;
}

/**
 * Generate HTML for action buttons on the order details page
 * @param {string|number} orderId - The order ID
 * @param {string} status - The current order status
 * @returns {string} HTML for the action buttons
 */
export function generateDetailPageButtonsHTML(orderId, status) {
    let actionButtons = '';
    
    if (status === 'preparing') {
        actionButtons += `
            <button class="btn btn-success quick-status-change" data-order-id="${orderId}" data-status="ready">
                <i class="bi bi-check-circle"></i> Mark as Ready
            </button>`;
    }
    
    if (status === 'ready') {
        actionButtons += `
            <button class="btn btn-secondary quick-status-change" data-order-id="${orderId}" data-status="picked up">
                <i class="bi bi-bag-check"></i> Mark as Picked Up
            </button>`;
    }
    
    if (isActiveStatus(status)) {
        actionButtons += `
            <button class="btn btn-danger quick-status-change" data-order-id="${orderId}" data-status="cancelled">
                <i class="bi bi-x-circle"></i> Cancel Order
            </button>`;
    }
    
    return actionButtons;
}

/**
 * Helper function to refresh the orders table with current filters
 */
export function refreshOrdersTable() {
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

/**
 * Update orders table with data from API
 * @param {Array} orders - Array of order objects
 */
export function updateOrdersTable(orders) {
    const ordersTableBody = document.querySelector('#orders-table tbody');
    if (!ordersTableBody) return;

    console.log("Updating orders table with", orders.length, "orders");

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

        // Format date and contact info
        const formattedDate = formatDate(order.order_placed_time);
        const contactInfo = formatContactInfo(order.customer_phone, order.customer_email);
        
        // Generate status-dependent dropdown menu
        const btnGroupHTML = generateActionButtonsHTML(order.order_id, order.status);
        const status = formatStatus(order.status);

        row.innerHTML = `
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${contactInfo}</td>
            <td>${order.item_count}</td>
            <td>₹${parseFloat(order.order_total).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>
                <span class="badge ${status.badgeClass}">
                    ${status.text}
                </span>
            </td>
            <td>
                <div class="btn-group">
                    ${btnGroupHTML}
                </div>
            </td>
        `;

        ordersTableBody.appendChild(row);
    });
    
    // Re-attach event listeners for the new buttons
    setupStatusChangeHandlers();
}

/**
 * Add a new order to the orders table
 * @param {Array} newOrders - Array of new order objects
 */
export function updateOrdersTableWithNewOrders(newOrders) {
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
        }
        
        // If we got here, the order should be added to the table
        const row = document.createElement('tr');
        row.setAttribute('data-order-id', order.order_id);
        
        if (order.status === 'archived') {
            row.className = 'table-secondary';
        }
        
        // Format date and contact info
        const formattedDate = formatDate(order.order_placed_time);
        const contactInfo = formatContactInfo(order.customer_phone, order.customer_email);
        
        // Generate status-dependent dropdown menu
        const btnGroupHTML = generateActionButtonsHTML(order.order_id, order.status);
        const status = formatStatus(order.status);

        row.innerHTML = `
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${contactInfo}</td>
            <td>${order.item_count}</td>
            <td>₹${parseFloat(order.order_total).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>
                <span class="badge ${status.badgeClass}">
                    ${status.text}
                </span>
            </td>
            <td>
                <div class="btn-group">
                    ${btnGroupHTML}
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

/**
 * Helper function to update the dashboard recent orders
 * @param {Array} newOrders - Array of order objects to add to the dashboard
 */
export function updateDashboardOrders(newOrders) {
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
        
        // Format date and status
        const formattedDate = formatDate(order.order_placed_time);
        const status = formatStatus(order.status);
        
        row.innerHTML = `
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${order.item_count}</td>
            <td>₹${parseFloat(order.order_total).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>
                <span class="badge ${status.badgeClass}">
                    ${status.text}
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

/**
 * Refresh dashboard orders with latest data
 * @param {Array} orders - Array of order objects
 */
export function refreshDashboardOrders(orders) {
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

        // Format date and status
        const formattedDate = formatDate(order.order_placed_time);
        const status = formatStatus(order.status);

        row.innerHTML = `
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${order.item_count}</td>
            <td>₹${parseFloat(order.order_total).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>
                <span class="badge ${status.badgeClass}">
                    ${status.text}
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