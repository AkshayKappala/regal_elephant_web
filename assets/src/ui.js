/**
 * UI related functionality
 */

const scrollThreshold = 200;
let goToTopBtn = null;

/**
 * Updates the cart count badge in the navbar
 */
export function updateCartBadge() {
    const badge = document.getElementById('cart-count-badge');
    const goToCartBtn = document.getElementById('goToCartBtn');

    if (!badge) return;

    let totalQuantity = 0;
    for (const itemId in window.cartItems) {
        totalQuantity += window.cartItems[itemId].quantity;
    }

    if (totalQuantity > 0) {
        badge.textContent = totalQuantity;
        badge.style.display = 'inline-block';
        if (goToCartBtn) {
            goToCartBtn.textContent = `Go to Cart (${totalQuantity})`;
            goToCartBtn.classList.add('show');
        }
    } else {
        badge.textContent = '0';
        badge.style.display = 'none';
        if (goToCartBtn) {
            goToCartBtn.textContent = 'Go to Cart';
            goToCartBtn.classList.remove('show');
        }
    }
}

/**
 * Initialize the active orders count badge
 */
export function initializeOrdersBadge() {
    const orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');
    if (orderHistory.length > 0) {
        // Fetch order details to check active orders
        Promise.all(orderHistory.map(orderId => 
            fetch(`api/get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .catch(error => ({ success: false }))
        ))
        .then(results => {
            // Filter successful responses and active orders
            const activeOrders = results
                .filter(data => data.success)
                .map(data => data.order)
                .filter(order => 
                    order.status !== 'archived' && 
                    order.status !== 'cancelled' && 
                    order.status !== 'picked up'
                );
            
            // Update badge if there are active orders
            if (activeOrders.length > 0) {
                const ordersNavLink = document.querySelector('a.nav-link[data-page="orders"]');
                if (ordersNavLink) {
                    let ordersBadge = document.getElementById('orders-count-badge');
                    if (!ordersBadge) {
                        ordersBadge = document.createElement('span');
                        ordersBadge.id = 'orders-count-badge';
                        ordersBadge.className = 'position-absolute top-0 start-50 translate-middle-x badge rounded-pill bg-danger';
                        ordersBadge.innerHTML = activeOrders.length + '<span class="visually-hidden">active orders</span>';
                        ordersNavLink.style.position = 'relative';
                        ordersNavLink.appendChild(ordersBadge);
                    } else {
                        ordersBadge.textContent = activeOrders.length;
                    }
                }
            }
        });
    }
}

/**
 * Handle scroll events for "Go to Top" button visibility
 */
export function handleScrollForGoToTop() {
    if (!goToTopBtn) {
        goToTopBtn = document.getElementById('goToTopBtn');
        if (!goToTopBtn) return;
    }
    if (window.scrollY > scrollThreshold) {
        goToTopBtn.classList.add('show');
    } else {
        goToTopBtn.classList.remove('show');
    }
}

/**
 * Add scroll event listener
 */
export function addScrollListener() {
    window.addEventListener('scroll', handleScrollForGoToTop);
}

/**
 * Remove scroll event listener
 */
export function removeScrollListener() {
    window.removeEventListener('scroll', handleScrollForGoToTop);
    if (goToTopBtn) {
        goToTopBtn.classList.remove('show');
    }
}

/**
 * Set the active navigation link
 * @param {string} page - The page name to set as active
 */
export function setActiveNavLink(page) {
    document.querySelectorAll("#navbar .nav-link").forEach((navLink) => {
        navLink.classList.remove("active");
    });
    const activeLink = document.querySelector(`#navbar .nav-link[data-page="${page}"]`);
    if (activeLink) {
        activeLink.classList.add("active");
    }
}