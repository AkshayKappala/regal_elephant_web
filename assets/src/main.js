/**
 * Main application entry point
 */
import { initNavigation, loadContent } from './navigation.js';
import { updateCartBadge } from './ui.js';
import { updateMenuQuantities, attachQuantityWidgetListeners } from './cart-ui.js';
import { scrollToTop } from './utils.js';

// Initialize cart items
window.cartItems = window.cartItems || {};

// Expose key functions globally to be used by other scripts
window.loadContent = loadContent;
window.updateCartBadge = updateCartBadge;
window.updateMenuQuantities = function() {
    const contentDiv = document.getElementById("content");
    updateMenuQuantities(contentDiv, window.cartItems);
};
window.scrollToTop = scrollToTop;

// Initialize the application
document.addEventListener("DOMContentLoaded", () => {
    initNavigation();
});
