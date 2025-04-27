/**
 * Filters functionality for the staff portal
 */

/**
 * Keep track of current filter state
 */
export const filterState = {
    getValues: function() {
        return {
            status: document.getElementById('status-filter')?.value || '',
            date: document.getElementById('date-filter')?.value || '',
            search: document.getElementById('search-filter')?.value || '',
            showArchived: document.getElementById('show-archived')?.checked || false
        };
    }
};

/**
 * Save the current filter state to session storage
 */
export function saveFilterState() {
    const currentState = filterState.getValues();
    sessionStorage.setItem('ordersFilterState', JSON.stringify(currentState));
}

/**
 * Restore filter state from session storage
 */
export function restoreFilterState() {
    const savedState = sessionStorage.getItem('ordersFilterState');
    if (!savedState) return false;
    
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
        
        return true;
    } catch (e) {
        console.error('Error restoring filter state:', e);
        // Clear invalid state
        sessionStorage.removeItem('ordersFilterState');
        return false;
    }
}

/**
 * Set up all filter form handlers for the orders page
 * @param {Function} refreshOrdersTable - Function to refresh the orders table
 */
export function setupFilterFormHandlers(refreshOrdersTable) {
    // Check if we're on the orders page
    const filterForm = document.querySelector('form[action=""]');
    if (!filterForm || !document.getElementById('orders-table')) return;
    
    // Restore filter state from session storage
    restoreFilterState();
    
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
    
    // Store filter state when filter button is clicked
    const filterButton = filterForm.querySelector('button[type="submit"]');
    if (filterButton) {
        filterButton.addEventListener('click', function() {
            saveFilterState();
        });
    }
    
    // Make filter state accessible globally
    window.currentFilterState = filterState;
}