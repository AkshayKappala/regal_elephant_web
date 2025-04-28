import { setActiveNavLink, updateCartBadge, addScrollListener, removeScrollListener, initializeOrdersBadge } from './ui.js';
import { updateMenuQuantities, attachQuantityWidgetListeners } from './cart-ui.js';
import { slugify } from './utils.js';

export function loadContent(page, anchorTarget = null) {
    const contentDiv = document.getElementById("content");
    removeScrollListener();
    let goToTopBtn = null;
    const goToCartBtn = document.getElementById('goToCartBtn');
    
    contentDiv.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
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
                if (typeof window.renderCart === 'function') {
                    window.renderCart();
                }
            }

            updateMenuQuantities(contentDiv, window.cartItems);
            attachQuantityWidgetListeners(contentDiv, window.cartItems);
            updateCartBadge();
            
            if (page !== 'orders') {
                initializeOrdersBadge();
            }

            if (page === 'menu') {
                goToTopBtn = document.getElementById('goToTopBtn');
                if (goToTopBtn) {
                    addScrollListener();
                }
            }

            if (anchorTarget) {
                const element = document.getElementById(anchorTarget);
                if (element) {
                    element.scrollIntoView({ behavior: "smooth", block: "start" });
                }
            }
        })
        .catch((error) => {
            contentDiv.innerHTML = "<div class='alert alert-danger'>Error loading content. Please try again.</div>";
        });
}

export function initNavigation() {
    const contentDiv = document.getElementById("content");
    const navbarDiv = document.getElementById("navbar");

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
            updateCartBadge();
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
}