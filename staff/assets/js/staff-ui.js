/**
 * UI related functionality for the staff portal
 */

/**
 * Display an alert message
 * @param {string} message - The message to display
 * @param {string} type - The alert type (success, info, warning, danger)
 */
export function showAlert(message, type = 'info') {
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

/**
 * Update stats display with optional animation
 * @param {string} elementId - The ID of the element to update
 * @param {number} newValue - The new value to display
 * @param {boolean} skipAnimation - Whether to skip the animation
 */
export function updateStatsDisplay(elementId, newValue, skipAnimation = false) {
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

/**
 * Update order statistics on the dashboard
 * @param {boolean} skipAnimation - Whether to skip the animation
 */
export function updateOrderStats(skipAnimation = false) {
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

/**
 * Refresh dashboard stats periodically
 */
export function refreshDashboardStats() {
    if (document.getElementById('dashboard-stats')) {
        updateOrderStats();
        // Update stats every 30 seconds
        setInterval(updateOrderStats, 30000);
    }
}