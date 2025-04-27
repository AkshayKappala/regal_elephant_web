document.addEventListener("DOMContentLoaded", () => {
    const contentDiv = document.getElementById("content");
    const navbarDiv = document.getElementById("navbar");

    window.cartItems = {};

    // Initialize active orders count badge
    function initializeOrdersBadge() {
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

    window.updateCartBadge = function() {
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
    };

    window.updateMenuQuantities = function() {
        for (const [itemId, cartItem] of Object.entries(window.cartItems)) {
            const qtySpanId = itemId + '-qty';
            const qtySpan = contentDiv.querySelector(`#${CSS.escape(qtySpanId)}`);
            if (qtySpan) {
                qtySpan.textContent = cartItem.quantity;
                const widget = qtySpan.closest('.quantity-widget');
                if (widget) {
                    const decBtn = widget.querySelector('[data-action="decrement"]');
                    if (decBtn) decBtn.disabled = cartItem.quantity <= 0;
                    const card = widget.closest('.menu-item-card');
                    if (card) card.classList.add('item-in-cart');
                }
            }
        }
        contentDiv.querySelectorAll('.quantity-widget').forEach(widget => {
            const itemId = widget.getAttribute('data-item');
            const card = widget.closest('.menu-item-card');

            if (!window.cartItems[itemId] || window.cartItems[itemId].quantity === 0) {
                const qtySpanId = itemId + '-qty';
                const qtySpan = widget.querySelector(`#${CSS.escape(qtySpanId)}`);
                if (qtySpan && qtySpan.textContent !== '0') {
                     qtySpan.textContent = 0;
                }
                const decBtn = widget.querySelector('[data-action="decrement"]');
                if (decBtn && !decBtn.disabled) {
                    decBtn.disabled = true;
                }
                if (card) card.classList.remove('item-in-cart');
            } else {
                 if (card) card.classList.add('item-in-cart');
            }
        });
        window.updateCartBadge();
    };

    function attachQuantityWidgetListeners() {
        const widgets = contentDiv.querySelectorAll('.quantity-widget');

        widgets.forEach(widget => {
            if (widget.dataset.listenersAttached === 'true') {
                return;
            }
            widget.dataset.listenersAttached = 'true';

            const itemId = widget.getAttribute('data-item');
            const itemName = widget.getAttribute('data-name');
            const itemPrice = parseFloat(widget.getAttribute('data-price'));
            const incBtn = widget.querySelector('[data-action="increment"]');
            const decBtn = widget.querySelector('[data-action="decrement"]');

            if (!incBtn || !decBtn) {
                return;
            }

            incBtn.addEventListener('click', function() {
                if (!window.cartItems[itemId]) {
                    window.cartItems[itemId] = { quantity: 1, name: itemName, price: itemPrice };
                } else {
                    window.cartItems[itemId].quantity += 1;
                }
                window.updateMenuQuantities();
                window.updateCartBadge();
            });

            decBtn.addEventListener('click', function() {
                if (window.cartItems[itemId] && window.cartItems[itemId].quantity > 0) {
                    window.cartItems[itemId].quantity -= 1;
                    if (window.cartItems[itemId].quantity === 0) {
                        delete window.cartItems[itemId];
                    }
                    window.updateMenuQuantities();
                    window.updateCartBadge();
                }
            });
        });
    }

    const scrollThreshold = 200;
    let goToTopBtn = null;

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function handleScrollForGoToTop() {
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

    function addScrollListener() {
        window.addEventListener('scroll', handleScrollForGoToTop);
    }

    function removeScrollListener() {
        window.removeEventListener('scroll', handleScrollForGoToTop);
        if (goToTopBtn) {
            goToTopBtn.classList.remove('show');
        }
    }

    function slugify(text) {
        if (!text) return '';
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^\w-]+/g, '')
            .replace(/--+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    }

    const setActiveNavLink = (page) => {
        document.querySelectorAll("#navbar .nav-link").forEach((navLink) => {
            navLink.classList.remove("active");
        });
        const activeLink = document.querySelector(`#navbar .nav-link[data-page="${page}"]`);
        if (activeLink) {
            activeLink.classList.add("active");
        }
    };

    window.loadContent = (page, anchorTarget = null) => {
        removeScrollListener();
        goToTopBtn = null;
        const goToCartBtn = document.getElementById('goToCartBtn');
        if (goToCartBtn) {
             goToCartBtn.classList.remove('show');
        }

        fetch(`views/${page}.php`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then((html) => {
                contentDiv.innerHTML = html;
                setActiveNavLink(page);

                const scripts = contentDiv.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    script.getAttributeNames().forEach(attr => newScript.setAttribute(attr, script.getAttribute(attr)));
                    if (script.innerHTML) {
                        newScript.appendChild(document.createTextNode(script.innerHTML));
                    }
                    script.parentNode.replaceChild(newScript, script);
                });

                if (page === 'cart') {
                    setTimeout(() => {
                        if (typeof window.renderCart === 'function') {
                            window.renderCart();
                        }
                    }, 0);
                }

                window.updateMenuQuantities();
                attachQuantityWidgetListeners();
                window.updateCartBadge();
                
                // Make sure orders badge is maintained when navigating between pages
                if (page !== 'orders') {
                    // When navigating to non-orders pages, make sure the badge persists
                    setTimeout(() => initializeOrdersBadge(), 100);
                }

                setTimeout(() => {
                    if (page === 'menu') {
                        goToTopBtn = document.getElementById('goToTopBtn');
                        if (goToTopBtn) {
                            addScrollListener();
                        }
                    }
                    window.updateCartBadge();
                }, 50);

                if (anchorTarget) {
                    setTimeout(() => {
                        const element = document.getElementById(anchorTarget);
                        if (element) {
                            element.scrollIntoView({ behavior: "smooth", block: "start" });
                        }
                    }, 150);
                }
            })
            .catch((error) => {
                contentDiv.innerHTML = "<p>Error loading content.</p>";
            });
    };

    fetch("partials/navbar.php")
        .then((response) => response.text())
        .then((html) => {
            navbarDiv.innerHTML = html;
            navbarDiv.addEventListener("click", (event) => {
                const target = event.target;
                if (target.tagName === "A" && target.classList.contains("nav-link") && target.hasAttribute("data-page")) {
                    event.preventDefault();
                    const page = target.getAttribute("data-page");
                    loadContent(page);
                }
            });
            loadContent("home");
            window.updateCartBadge();
            initializeOrdersBadge();
        })
        .catch((error) => {
            loadContent("home");
        });

    document.body.addEventListener("click", (event) => {
        const topBtnTarget = event.target.closest('#goToTopBtn');
        const cartBtnTarget = event.target.closest('#goToCartBtn');

        if (topBtnTarget) {
            event.preventDefault();
            scrollToTop();
            return;
        }

        if (cartBtnTarget) {
            event.preventDefault();
            loadContent('cart');
            return;
        }

        const navLinkTarget = event.target.closest('a[data-page]');
        if (navLinkTarget) {
            const isNavLinkClick = navbarDiv.contains(navLinkTarget) && navLinkTarget.classList.contains('nav-link');

            if (!isNavLinkClick) {
                event.preventDefault();

                const page = navLinkTarget.getAttribute("data-page");
                const categoryTarget = navLinkTarget.getAttribute("data-category-target");
                let anchorId = null;

                if (page === 'menu' && categoryTarget) {
                    anchorId = `category-${slugify(categoryTarget)}`;
                }
                loadContent(page, anchorId);
            }
        }

    });
});
