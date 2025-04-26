// Staff Portal JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    // Automatically update order stats in dashboard
    refreshDashboardStats();

    // Setup handlers for status change
    setupStatusChangeHandlers();
});

// Refresh dashboard stats periodically
function refreshDashboardStats() {
    if (document.getElementById('dashboard-stats')) {
        updateOrderStats();
        // Update stats every 30 seconds
        setInterval(updateOrderStats, 30000);
    }
}

// Update order statistics on the dashboard
function updateOrderStats() {
    fetch('api/get_order_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('preparing-count').textContent = data.stats.preparing || 0;
                document.getElementById('ready-count').textContent = data.stats.ready || 0;
                document.getElementById('picked-up-count').textContent = data.stats.picked_up || 0;
                document.getElementById('total-orders-count').textContent = data.stats.total || 0;
            }
        })
        .catch(error => console.error('Error updating stats:', error));
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

// Update order status via API
function updateOrderStatus(orderId, newStatus) {
    if (!orderId || !newStatus) return;

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
            // If we're on the orders list page, refresh the table
            if (document.getElementById('orders-table')) {
                location.reload();
            } 
            // If we're on the order details page, update the status badge
            else {
                const statusBadge = document.getElementById('status-badge');
                if (statusBadge) {
                    statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    
                    // Remove all badge classes and add the appropriate one
                    statusBadge.classList.remove('badge-preparing', 'badge-ready', 'badge-picked-up', 'badge-cancelled');
                    statusBadge.classList.add(`badge-${newStatus.replace(' ', '-')}`);
                }
            }
            
            // Show success alert
            showAlert('Order status updated successfully!', 'success');
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