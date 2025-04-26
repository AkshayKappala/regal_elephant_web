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
        </div>
        
        <div class="col-lg-6">
            <!-- Order Management Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Management</h5>
                </div>
                <div class="card-body">
                    <p class="text-danger mb-3">This action cannot be undone. Please proceed with caution.</p>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#archiveOrdersModal">
                            <i class="bi bi-archive"></i> Archive All Orders
                        </button>
                        <div class="form-text mt-2">This will mark all active orders as archived.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Archive Orders Confirmation Modal -->
<div class="modal fade" id="archiveOrdersModal" tabindex="-1" aria-labelledby="archiveOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="archiveOrdersModalLabel">Confirm Archive All Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i> Warning: This action cannot be undone.
                </div>
                <p>Are you sure you want to archive <strong>ALL</strong> active orders?</p>
                <p>This will:</p>
                <ul>
                    <li>Mark all active orders as "Archived" in the system</li>
                    <li>Remove them from active orders list</li>
                    <li>Show them as "Completed" to customers</li>
                </ul>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmArchiveCheck" required>
                    <label class="form-check-label" for="confirmArchiveCheck">
                        I understand this action cannot be reversed
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmArchiveOrdersBtn" disabled>
                    Archive All Orders
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// For demonstration purposes - these would be connected to real API endpoints in a production environment
document.getElementById('password-form').addEventListener('submit', function(e) {
    e.preventDefault();
    showAlert('Password updated successfully!', 'success');
});

// Archive Orders Functionality
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheck = document.getElementById('confirmArchiveCheck');
    const archiveBtn = document.getElementById('confirmArchiveOrdersBtn');
    
    if(confirmCheck && archiveBtn) {
        confirmCheck.addEventListener('change', function() {
            archiveBtn.disabled = !this.checked;
        });
        
        archiveBtn.addEventListener('click', function() {
            // Show loading state
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Archiving...';
            this.disabled = true;
            
            // Call the API to archive all orders
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('archiveOrdersModal'));
                    modal.hide();
                    
                    // Show success message
                    showAlert('All orders have been successfully archived.', 'success');
                    
                    // Reset checkbox
                    confirmCheck.checked = false;
                    archiveBtn.disabled = true;
                    
                    // Reload dashboard stats to reflect changes
                    if(typeof updateOrderStats === 'function') {
                        updateOrderStats();
                    }
                } else {
                    throw new Error(data.error || 'Failed to archive orders');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset button state
                this.innerHTML = 'Archive All Orders';
                this.disabled = false;
            });
        });
    }
});
</script>