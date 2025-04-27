/**
 * Navigation related functionality
 */
import { setActiveNavLink, updateCartBadge, addScrollListener, removeScrollListener, initializeOrdersBadge } from './ui.js';
import { updateMenuQuantities, attachQuantityWidgetListeners } from './cart-ui.js';
import { slugify } from './utils.js';

/**
 * Load page content via AJAX
 * @param {string} page - The page to load
 * @param {string|null} anchorTarget - Optional anchor ID to scroll to
 */
export function loadContent(page, anchorTarget = null) {
    const contentDiv = document.getElementById("content");
    removeScrollListener();
    let goToTopBtn = null;
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

            // Execute any script tags within the loaded content
            const scripts = contentDiv.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                script.getAttributeNames().forEach(attr => newScript.setAttribute(attr, script.getAttribute(attr)));
                if (script.innerHTML) {
                    newScript.appendChild(document.createTextNode(script.innerHTML));
                }
                script.parentNode.replaceChild(newScript, script);
            });

            // Handle cart rendering if on cart page
            if (page === 'cart') {
                setTimeout(() => {
                    if (typeof window.renderCart === 'function') {
                        window.renderCart();
                    }
                }, 0);
            }

            updateMenuQuantities(contentDiv, window.cartItems);
            attachQuantityWidgetListeners(contentDiv, window.cartItems);
            updateCartBadge();
            
            // Make sure orders badge is maintained when navigating
            if (page !== 'orders') {
                setTimeout(() => initializeOrdersBadge(), 100);
            }

            // Add scroll listener for menu page
            setTimeout(() => {
                if (page === 'menu') {
                    goToTopBtn = document.getElementById('goToTopBtn');
                    if (goToTopBtn) {
                        addScrollListener();
                    }
                }
                updateCartBadge();
            }, 50);

            // Scroll to anchor target if specified
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
}

/**
 * Initialize navigation and content loading
 */
export function initNavigation() {
    const contentDiv = document.getElementById("content");
    const navbarDiv = document.getElementById("navbar");

    // Load the navbar
    fetch("partials/navbar.php")
        .then((response) => response.text())
        .then((html) => {
            navbarDiv.innerHTML = html;
            
            // Handle clicks on navbar links
            navbarDiv.addEventListener("click", (event) => {
                const target = event.target;
                if (target.tagName === "A" && target.classList.contains("nav-link") && target.hasAttribute("data-page")) {
                    event.preventDefault();
                    const page = target.getAttribute("data-page");
                    loadContent(page);
                }
            });
            
            // Load home page by default
            loadContent("home");
            updateCartBadge();
            initializeOrdersBadge();
        })
        .catch((error) => {
            console.error("Error loading navbar:", error);
            loadContent("home");
        });

    // Global click handler for navigation elements
    document.body.addEventListener("click", (event) => {
        const topBtnTarget = event.target.closest('#goToTopBtn');
        const cartBtnTarget = event.target.closest('#goToCartBtn');

        // Handle "Go to Top" button clicks
        if (topBtnTarget) {
            event.preventDefault();
            scrollToTop();
            return;
        }

        // Handle "Go to Cart" button clicks
        if (cartBtnTarget) {
            event.preventDefault();
            loadContent('cart');
            return;
        }

        // Handle other navigation links
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
}