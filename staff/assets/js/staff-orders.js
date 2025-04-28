import { formatDate, formatStatus, formatContactInfo, isActiveStatus } from './staff-utils.js';

export function setupStatusChangeHandlers() {
    const statusSelect = document.getElementById('order-status-select');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = this.value;
            updateOrderStatus(orderId, newStatus);
        });
    }

    document.querySelectorAll('.quick-status-change').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = this.getAttribute('data-status');
            updateOrderStatus(orderId, newStatus);
        });
    });
}

export function updateOrderStatus(orderId, newStatus) {
    if (!orderId || !newStatus) return;

    updateOrderStatusUI(orderId, newStatus);

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
            if (document.getElementById('orders-table')) {
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

function updateOrderStatusUI(orderId, newStatus) {
    const triggerButton = document.querySelector(`.quick-status-change[data-order-id="${orderId}"][data-status="${newStatus}"]`);
    if (triggerButton) {
        const row = triggerButton.closest('tr');
        if (row) {
            const statusBadge = row.querySelector('.badge');
            if (statusBadge) {
                const status = formatStatus(newStatus);
                statusBadge.textContent = status.text;
                statusBadge.className = `badge ${status.badgeClass}`;
            }
            
            const actionCell = row.querySelector('td:last-child');
            if (actionCell) {
                const btnGroup = actionCell.querySelector('.btn-group');
                if (btnGroup) {
                    btnGroup.innerHTML = generateActionButtonsHTML(orderId, newStatus);
                    
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
        
        const actionsContainer = document.querySelector('.card .d-grid.gap-2');
        if (actionsContainer) {
            actionsContainer.innerHTML = generateDetailPageButtonsHTML(orderId, newStatus);
            
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

export function generateActionButtonsHTML(orderId, status) {
    let html = `
        <a href="?page=order-details&id=${orderId}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-eye"></i> View
        </a>`;
    
    if (status === 'archived') {
        return html;
    }
    
    let menuItems = '';
    
    if (status === 'preparing') {
        menuItems += `
            <li>
                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="ready">
                    <i class="bi bi-check-circle text-success"></i> Mark as Ready
                </button>
            </li>`;
    }
    
    if (status === 'ready') {
        menuItems += `
            <li>
                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="picked up">
                    <i class="bi bi-bag-check text-secondary"></i> Mark as Picked Up
                </button>
            </li>`;
    }
    
    if (isActiveStatus(status)) {
        menuItems += `
            <li>
                <button class="dropdown-item quick-status-change" data-order-id="${orderId}" data-status="cancelled">
                    <i class="bi bi-x-circle text-danger"></i> Cancel Order
                </button>
            </li>`;
    }
    
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

export function refreshOrdersTable() {
    const statusFilter = document.getElementById('status-filter')?.value || 'active';
    const dateFilter = document.getElementById('date-filter')?.value || '';
    const searchFilter = document.getElementById('search-filter')?.value || '';
    
    let queryParams = new URLSearchParams();
    if (statusFilter) queryParams.append('status', statusFilter);
    if (dateFilter) queryParams.append('date', dateFilter);
    if (searchFilter) queryParams.append('search', searchFilter);
    
    fetch(`api/get_orders_list.php?${queryParams.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateOrdersTable(data.orders);
            }
        })
        .catch(error => console.error('Error fetching updated orders:', error));
}

export function updateOrdersTable(orders) {
    const ordersTableBody = document.querySelector('#orders-table tbody');
    if (!ordersTableBody) return;

    console.log("Updating orders table with", orders.length, "orders");

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

        const formattedDate = formatDate(order.order_placed_time);
        const contactInfo = formatContactInfo(order.customer_phone, order.customer_email);
        
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
    
    setupStatusChangeHandlers();
}

export function updateOrdersTableWithNewOrders(newOrders) {
    const ordersTableBody = document.querySelector('#orders-table tbody');
    if (!ordersTableBody) return;
    
    newOrders.forEach(order => {
        const existingOrderRow = ordersTableBody.querySelector(`tr[data-order-id="${order.order_id}"]`);
        if (existingOrderRow) return;
        
        if (window.currentFilterState) {
            const filterState = window.currentFilterState.getValues();
            
            if (filterState.status && filterState.status !== 'all') {
                if (filterState.status === 'active') {
                    if (order.status === 'archived') return;
                } else if (filterState.status !== order.status) {
                    return;
                }
            }
            
            if (!filterState.showArchived && order.status === 'archived') {
                return;
            }
        }
        
        const row = document.createElement('tr');
        row.setAttribute('data-order-id', order.order_id);
        
        if (order.status === 'archived') {
            row.className = 'table-secondary';
        }
        
        const formattedDate = formatDate(order.order_placed_time);
        const contactInfo = formatContactInfo(order.customer_phone, order.customer_email);
        
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

        if (ordersTableBody.firstChild) {
            ordersTableBody.insertBefore(row, ordersTableBody.firstChild);
        } else {
            ordersTableBody.appendChild(row);
        }
        
        const noOrdersRow = ordersTableBody.querySelector('tr td[colspan="8"].text-center');
        if (noOrdersRow) {
            noOrdersRow.closest('tr').remove();
        }
    });
    
    setupStatusChangeHandlers();
}

export function updateDashboardOrders(newOrders) {
    const recentOrdersTable = document.querySelector('.orders-table tbody');
    if (!recentOrdersTable) return;

    newOrders.forEach(order => {
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
            return;
        }
        
        const row = document.createElement('tr');
        row.setAttribute('data-order-id', order.order_id);
        
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
        
        if (recentOrdersTable.firstChild) {
            recentOrdersTable.insertBefore(row, recentOrdersTable.firstChild);
        } else {
            recentOrdersTable.appendChild(row);
        }
        
        const allRows = recentOrdersTable.querySelectorAll('tr');
        if (allRows.length > 5) {
            recentOrdersTable.removeChild(allRows[allRows.length - 1]);
        }
    });
}

export function refreshDashboardOrders(orders) {
    const recentOrdersTable = document.querySelector('.orders-table tbody');
    if (!recentOrdersTable) return;

    recentOrdersTable.innerHTML = '';

    const activeOrders = orders.filter(order => order.status !== 'archived');
    const recentOrders = activeOrders.slice(0, 5);

    recentOrders.forEach(order => {
        const row = document.createElement('tr');
        row.setAttribute('data-order-id', order.order_id);

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