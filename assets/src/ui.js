const scrollThreshold = 200;
let goToTopBtn = null;

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

export function initializeOrdersBadge() {
    const orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');
    if (orderHistory.length === 0) {
        updateOrdersBadgeUI(0);
        return;
    }
    
    Promise.all(orderHistory.map(orderId => 
        fetch(`api/get_order_details.php?order_id=${orderId}`)
            .then(response => response.json())
            .catch(error => {
                return { success: false };
            })
    ))
    .then(results => {
        const activeOrders = results
            .filter(data => data.success)
            .map(data => data.order)
            .filter(order => 
                order.status !== 'archived' && 
                order.status !== 'cancelled' && 
                order.status !== 'picked up'
            );
        
        updateOrdersBadgeUI(activeOrders.length);
    })
    .catch(error => {
    });
}

export function updateOrderStatus(orderId, newStatus) {
    const orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');
    
    if (!orderHistory.includes(orderId)) {
        orderHistory.push(orderId);
        localStorage.setItem('order_history', JSON.stringify(orderHistory));
    }
    
    if (newStatus === 'picked up' || newStatus === 'cancelled' || newStatus === 'archived') {
        setTimeout(() => {
            initializeOrdersBadge();
        }, 300);
    }
    
    return true;
}

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

export function addScrollListener() {
    window.addEventListener('scroll', handleScrollForGoToTop);
}

export function removeScrollListener() {
    window.removeEventListener('scroll', handleScrollForGoToTop);
    if (goToTopBtn) {
        goToTopBtn.classList.remove('show');
    }
}

export function setActiveNavLink(page) {
    document.querySelectorAll("#navbar .nav-link").forEach((navLink) => {
        navLink.classList.remove("active");
    });
    const activeLink = document.querySelector(`#navbar .nav-link[data-page="${page}"]`);
    if (activeLink) {
        activeLink.classList.add("active");
    }
}