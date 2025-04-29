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

export function saveFilterState() {
    const currentState = filterState.getValues();
    sessionStorage.setItem('ordersFilterState', JSON.stringify(currentState));
}

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

export function setupFilterFormHandlers(refreshOrdersTable) {
    const filterForm = document.querySelector('form[action=""]');
    if (!filterForm || !document.getElementById('orders-table')) return;
    
    restoreFilterState();
    
    const statusFilter = document.getElementById('status-filter');
    const showArchivedCheckbox = document.getElementById('show-archived');
    
    if (statusFilter && showArchivedCheckbox) {
        statusFilter.addEventListener('change', function() {
            if (this.value === 'all' || this.value === 'archived') {
                showArchivedCheckbox.disabled = true;
                
                showArchivedCheckbox.checked = (this.value === 'all');
            } else {
                showArchivedCheckbox.disabled = false;
            }
        });
    }

    if (showArchivedCheckbox) {
        showArchivedCheckbox.addEventListener('change', function() {
            saveFilterState();
            filterForm.submit();
        });
    }
    
    const resetButton = filterForm.querySelector('a.btn-outline-secondary');
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            const statusFilter = document.getElementById('status-filter');
            if (statusFilter) statusFilter.value = 'active';
            
            const dateFilter = document.getElementById('date-filter');
            if (dateFilter) dateFilter.value = '';
            
            const searchFilter = document.getElementById('search-filter');
            if (searchFilter) searchFilter.value = '';
            
            sessionStorage.removeItem('ordersFilterState');
            
            window.location.href = '?page=orders';
        });
    }
    
    const filterButton = filterForm.querySelector('button[type="submit"]');
    if (filterButton) {
        filterButton.addEventListener('click', function() {
            saveFilterState();
        });
    }
    
    window.currentFilterState = filterState;
}