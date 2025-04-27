/**
 * Main entry point for the staff portal
 */
import { setupStatusChangeHandlers } from './staff-orders.js';
import { initializeSSEConnection } from './staff-events.js';
import { setupFilterFormHandlers } from './staff-filters.js';

/**
 * Initialize the staff portal when the DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    // Setup handlers for status change
    setupStatusChangeHandlers();

    // Initialize Server-Sent Events connection
    initializeSSEConnection();

    // Setup filter form handlers
    setupFilterFormHandlers();
});