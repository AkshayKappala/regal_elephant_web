/**
 * Main application entry point
 */
import { initNavigation, loadContent } from './navigation.js';
import { updateCartBadge, initializeOrdersBadge } from './ui.js';
import { updateMenuQuantities, attachQuantityWidgetListeners } from './cart-ui.js';
import { scrollToTop } from './utils.js';
import { initializeOrderEvents } from './events.js';

// Initialize cart items
window.cartItems = window.cartItems || {};

// Expose key functions globally to be used by other scripts
window.loadContent = loadContent;
window.updateCartBadge = updateCartBadge;
window.initializeOrdersBadge = initializeOrdersBadge;
window.updateMenuQuantities = function() {
    const contentDiv = document.getElementById("content");
    updateMenuQuantities(contentDiv, window.cartItems);
};
window.scrollToTop = scrollToTop;

// Initialize the application
document.addEventListener("DOMContentLoaded", () => {
    // Initialize navigation
    initNavigation();
    
    // Initialize the orders badge
    initializeOrdersBadge();
    
    // Initialize the event system for real-time updates
    initializeOrderEvents();
});
