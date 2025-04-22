document.addEventListener("DOMContentLoaded", () => {
    const contentDiv = document.getElementById("content");
    const navbarDiv = document.getElementById("navbar");

    window.cartItems = {}; 

    window.updateCartBadge = function() {
        const badge = document.getElementById('cart-count-badge');
        if (!badge) return; 

        let totalQuantity = 0;
        for (const itemId in window.cartItems) {
            totalQuantity += window.cartItems[itemId].quantity;
        }

        if (totalQuantity > 0) {
            badge.textContent = totalQuantity;
            badge.style.display = 'inline-block'; 
        } else {
            badge.textContent = '0';
            badge.style.display = 'none'; 
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
                console.error(`Could not find increment/decrement buttons for item ${itemId}`);
                return;
            }

            incBtn.addEventListener('click', function() {
                if (!window.cartItems[itemId]) {
                    window.cartItems[itemId] = { quantity: 1, name: itemName, price: itemPrice };
                } else {
                    window.cartItems[itemId].quantity += 1;
                }
                window.updateMenuQuantities(); 
            });

            decBtn.addEventListener('click', function() {
                if (window.cartItems[itemId] && window.cartItems[itemId].quantity > 0) {
                    window.cartItems[itemId].quantity -= 1;
                    if (window.cartItems[itemId].quantity === 0) {
                        delete window.cartItems[itemId];
                    }
                    window.updateMenuQuantities(); 
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

    const loadContent = (page, anchorTarget = null) => {
        removeScrollListener();
        goToTopBtn = null; 

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

                window.updateMenuQuantities(); 
                attachQuantityWidgetListeners(); 
                window.updateCartBadge(); 

                setTimeout(() => {
                    if (page === 'menu') {
                        goToTopBtn = document.getElementById('goToTopBtn'); 
                        if (goToTopBtn) {
                            addScrollListener(); 
                        } else {
                            console.log("Go to Top button not found after loading menu."); 
                        }
                    }
                }, 50); 

                if (page === 'cart' && typeof renderCart === 'function') {
                    renderCart(); 
                }

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
                console.error("Error loading page:", error);
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
        })
        .catch((error) => {
            console.error("Error loading navbar:", error);
            loadContent("home"); 
        });

    document.body.addEventListener("click", (event) => {
        const topBtnTarget = event.target.closest('#goToTopBtn');
        if (topBtnTarget) {
            event.preventDefault(); 
            console.log("Go to Top button clicked"); 
            scrollToTop();
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
                console.log(`Loading page from body listener: ${page}, Anchor: ${anchorId}`); 
                loadContent(page, anchorId);
            }
        }

    });
});
