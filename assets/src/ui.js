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
 * Update the orders badge in the navbar
 * @param {number} count - The count of active orders to display
 */
export function updateOrdersBadgeUI(count) {
    const ordersNavLink = document.querySelector('a.nav-link[data-page="orders"]');
    if (!ordersNavLink) return;
    
    let ordersBadge = document.getElementById('orders-count-badge');
    
    if (count > 0) {
        if (!ordersBadge) {
            ordersBadge = document.createElement('span');
            ordersBadge.id = 'orders-count-badge';
            ordersBadge.className = 'position-absolute top-0 start-50 translate-middle-x badge rounded-pill bg-danger';
            ordersBadge.innerHTML = count + '<span class="visually-hidden">active orders</span>';
            ordersNavLink.style.position = 'relative';
            ordersNavLink.appendChild(ordersBadge);
        } else {
            ordersBadge.textContent = count;
            ordersBadge.style.display = 'inline-block';
        }
    } else if (ordersBadge) {
        ordersBadge.style.display = 'none';
    }
}

/**
 * Initialize the active orders count badge
 */
export function initializeOrdersBadge() {
    console.log('Initializing orders badge...');
    const orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');
    if (orderHistory.length === 0) {
        updateOrdersBadgeUI(0);
        return;
    }
    
    console.log(`Found ${orderHistory.length} orders in history:`, orderHistory);
    
    // Fetch order details to check active orders
    Promise.all(orderHistory.map(orderId => 
        fetch(`api/get_order_details.php?order_id=${orderId}`)
            .then(response => response.json())
            .catch(error => {
                console.error(`Error fetching details for order ${orderId}:`, error);
                return { success: false };
            })
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
        
        console.log(`Found ${activeOrders.length} active orders:`, activeOrders);
        
        // Update badge with count of active orders
        updateOrdersBadgeUI(activeOrders.length);
    })
    .catch(error => {
        console.error('Error initializing orders badge:', error);
    });
}

/**
 * Update a specific order in local storage when its status changes
 * @param {number} orderId - The ID of the order to update
 * @param {string} newStatus - The new status of the order
 * @returns {boolean} - Whether the order was found and updated
 */
export function updateOrderStatus(orderId, newStatus) {
    console.log(`Updating order ${orderId} status to ${newStatus}`);
    const orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');
    
    // If the order isn't in history yet, add it
    if (!orderHistory.includes(orderId)) {
        orderHistory.push(orderId);
        localStorage.setItem('order_history', JSON.stringify(orderHistory));
    }
    
    // If order was completed or cancelled, refresh the badge to update the count
    if (newStatus === 'picked up' || newStatus === 'cancelled' || newStatus === 'archived') {
        // Refresh order badge after a brief delay to allow database to update
        setTimeout(() => {
            initializeOrdersBadge();
        }, 300);
    }
    
    return true;
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