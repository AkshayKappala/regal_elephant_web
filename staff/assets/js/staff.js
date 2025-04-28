import { setupStatusChangeHandlers } from './staff-orders.js';
import { initializeSSEConnection } from './staff-events.js';
import { setupFilterFormHandlers } from './staff-filters.js';

document.addEventListener('DOMContentLoaded', function() {
    setupStatusChangeHandlers();
    initializeSSEConnection();
    setupFilterFormHandlers();
});