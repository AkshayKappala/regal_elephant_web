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
</script>