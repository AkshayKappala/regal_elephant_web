<?php
// Simple settings page for staff portal
?>

<div class="container-fluid">
    <h1 class="page-title">Settings</h1>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Settings</h5>
                </div>
                <div class="card-body">
                    <form id="password-form">
                        <div class="mb-3">
                            <label for="current-password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current-password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new-password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new-password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm-password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm-password" required>
                        </div>
                        <button type="submit" class="btn btn-custom">Change Password</button>
                    </form>
                </div>
            </div>
            
            <!-- Order Management Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Management</h5>
                </div>
                <div class="card-body">
                    <p class="text-danger mb-3">These actions cannot be undone. Please proceed with caution.</p>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteOrdersModal">
                            <i class="bi bi-trash"></i> Delete All Orders
                        </button>
                        <div class="form-text mt-2">This will permanently delete all orders from the system.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="archive-status" class="form-label">Mark Completed Orders as:</label>
                        <select class="form-select" id="archive-status">
                            <option value="archived">Archived</option>
                            <option value="completed">Completed</option>
                        </select>
                        <div class="form-text">This determines how completed orders appear to customers.</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Notification Settings</h5>
                </div>
                <div class="card-body">
                    <form id="notification-form">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="new-order-notification" checked>
                            <label class="form-check-label" for="new-order-notification">
                                New Order Notifications
                            </label>
                            <div class="form-text">Receive notifications when new orders are placed</div>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="status-change-notification" checked>
                            <label class="form-check-label" for="status-change-notification">
                                Status Change Notifications
                            </label>
                            <div class="form-text">Receive notifications when order statuses are updated</div>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="browser-notification">
                            <label class="form-check-label" for="browser-notification">
                                Browser Notifications
                            </label>
                            <div class="form-text">Enable browser push notifications</div>
                        </div>
                        
                        <button type="submit" class="btn btn-custom">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p><strong>Staff Portal Version:</strong> 1.0.0</p>
                        <p><strong>Last Updated:</strong> April 26, 2025</p>
                        <p><strong>Server Time:</strong> <span id="server-time"><?php echo date('F d, Y h:i:s A'); ?></span></p>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-primary me-2" onclick="checkForUpdates()">
                            <i class="bi bi-arrow-repeat"></i> Check for Updates
                        </button>
                        <span id="update-status" class="text-muted"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Orders Confirmation Modal -->
<div class="modal fade" id="deleteOrdersModal" tabindex="-1" aria-labelledby="deleteOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteOrdersModalLabel">Confirm Delete All Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> Warning: This action cannot be undone.
                </div>
                <p>Are you sure you want to delete <strong>ALL</strong> orders from the system?</p>
                <p>This will:</p>
                <ul>
                    <li>Remove all orders from the database</li>
                    <li>Reset all order statistics</li>
                    <li>Mark orders as "Past Orders" in customer view</li>
                </ul>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmDeleteCheck" required>
                    <label class="form-check-label" for="confirmDeleteCheck">
                        I understand this action cannot be reversed
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteOrdersBtn" disabled>
                    Delete All Orders
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Simple demo function for "Check for Updates" button
function checkForUpdates() {
    const updateStatus = document.getElementById('update-status');
    updateStatus.textContent = 'Checking for updates...';
    
    // Simulate checking for updates
    setTimeout(() => {
        updateStatus.textContent = 'You are using the latest version.';
    }, 1500);
}

// For demonstration purposes - these would be connected to real API endpoints in a production environment
document.getElementById('password-form').addEventListener('submit', function(e) {
    e.preventDefault();
    showAlert('Password updated successfully!', 'success');
});

document.getElementById('notification-form').addEventListener('submit', function(e) {
    e.preventDefault();
    showAlert('Notification settings saved!', 'success');
});

// Update server time every second
setInterval(() => {
    const now = new Date();
    document.getElementById('server-time').textContent = now.toLocaleString();
}, 1000);

// Delete Orders Functionality
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheck = document.getElementById('confirmDeleteCheck');
    const deleteBtn = document.getElementById('confirmDeleteOrdersBtn');
    
    if(confirmCheck && deleteBtn) {
        confirmCheck.addEventListener('change', function() {
            deleteBtn.disabled = !this.checked;
        });
        
        deleteBtn.addEventListener('click', function() {
            // Show loading state
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
            this.disabled = true;
            
            // Call the API to delete all orders
            fetch('api/delete_all_orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Hide the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteOrdersModal'));
                    modal.hide();
                    
                    // Show success message
                    showAlert('All orders have been successfully deleted.', 'success');
                    
                    // Reset checkbox
                    confirmCheck.checked = false;
                    deleteBtn.disabled = true;
                    
                    // Reload dashboard stats to reflect changes
                    if(typeof updateOrderStats === 'function') {
                        updateOrderStats();
                    }
                } else {
                    throw new Error(data.error || 'Failed to delete orders');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset button state
                this.innerHTML = 'Delete All Orders';
                this.disabled = false;
            });
        });
    }
});
</script>