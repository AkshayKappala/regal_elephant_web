import { initNavigation, loadContent } from './navigation.js';
import { updateCartBadge, initializeOrdersBadge } from './ui.js';
import { updateMenuQuantities, attachQuantityWidgetListeners } from './cart-ui.js';
import { scrollToTop } from './utils.js';
import { initializeOrderEvents } from './events.js';

window.cartItems = window.cartItems || {};

window.loadContent = loadContent;
window.updateCartBadge = updateCartBadge;
window.initializeOrdersBadge = initializeOrdersBadge;
window.updateMenuQuantities = function() {
    const contentDiv = document.getElementById("content");
    updateMenuQuantities(contentDiv, window.cartItems);
};
window.scrollToTop = scrollToTop;

document.addEventListener("DOMContentLoaded", () => {
    initNavigation();
    
    initializeOrdersBadge();
    
    initializeOrderEvents();
});
