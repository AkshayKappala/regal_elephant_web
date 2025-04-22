document.addEventListener("DOMContentLoaded", () => {
    const contentDiv = document.getElementById("content");
    const navbarDiv = document.getElementById("navbar");

    // --- Cart State & Logic ---
    window.cartItems = {}; // Global cart state

    // Utility to update quantity widgets based on cartItems
    window.updateMenuQuantities = function() {
        // Update quantities for items in the cart
        for (const [itemId, cartItem] of Object.entries(window.cartItems)) {
            const qtySpanId = itemId + '-qty';
            const qtySpan = contentDiv.querySelector(`#${CSS.escape(qtySpanId)}`);
            if (qtySpan) {
                qtySpan.textContent = cartItem.quantity;
                const widget = qtySpan.closest('.quantity-widget');
                if (widget) {
                    const decBtn = widget.querySelector('[data-action="decrement"]');
                    if (decBtn) decBtn.disabled = cartItem.quantity <= 0;
                }
            }
        }
        // Reset quantities for items NOT in the cart (or ensure they are 0)
        contentDiv.querySelectorAll('.quantity-widget').forEach(widget => {
            const itemId = widget.getAttribute('data-item');
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
            }
        });
    };

    // Function to attach listeners to quantity widgets within #content
    function attachQuantityWidgetListeners() {
        const widgets = contentDiv.querySelectorAll('.quantity-widget');

        widgets.forEach(widget => {
            // Check if listeners are already attached to prevent duplicates
            if (widget.dataset.listenersAttached === 'true') {
                return;
            }
            widget.dataset.listenersAttached = 'true'; // Mark as attached

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
                window.updateMenuQuantities(); // Update UI immediately
            });

            decBtn.addEventListener('click', function() {
                if (window.cartItems[itemId] && window.cartItems[itemId].quantity > 0) {
                    window.cartItems[itemId].quantity -= 1;
                    if (window.cartItems[itemId].quantity === 0) {
                        delete window.cartItems[itemId];
                    }
                    window.updateMenuQuantities(); // Update UI immediately
                }
            });
        });
    }
    // --- End Cart State & Logic ---

    // Helper function to slugify text for IDs
    function slugify(text) {
        if (!text) return '';
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w-]+/g, '')       // Remove all non-word chars except hyphen
            .replace(/--+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
    }

    // Function to set the active nav link
    const setActiveNavLink = (page) => {
        document.querySelectorAll("#navbar .nav-link").forEach((navLink) => {
            navLink.classList.remove("active");
        });
        const activeLink = document.querySelector(`#navbar .nav-link[data-page="${page}"]`);
        if (activeLink) {
            activeLink.classList.add("active");
        }
    };

    // Load content dynamically
    const loadContent = (page, anchorTarget = null) => {
        fetch(`views/${page}.php`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then((html) => {
                contentDiv.innerHTML = html;
                setActiveNavLink(page); // Update active link

                // --- Cart Related Updates After Load ---
                window.updateMenuQuantities(); // Update quantities based on current cart state
                attachQuantityWidgetListeners(); // Re-attach listeners to newly loaded widgets
                // --- End Cart Related Updates ---

                // Specific actions for cart page
                if (page === 'cart' && typeof renderCart === 'function') {
                    renderCart(); // Call renderCart if it exists (defined in cart.php)
                }

                // Scroll to anchor if provided
                if (anchorTarget) {
                    // Use setTimeout to ensure the element exists in the DOM after render before scrolling
                    setTimeout(() => {
                        const element = document.getElementById(anchorTarget);
                        if (element) {
                            element.scrollIntoView({ behavior: "smooth", block: "start" });
                        }
                    }, 150); // Increased timeout slightly just in case
                }
            })
            .catch((error) => {
                contentDiv.innerHTML = "<p>Error loading content.</p>";
                console.error("Error loading page:", error);
            });
    };

    // Load navbar dynamically and then attach listeners
    fetch("partials/navbar.php")
        .then((response) => response.text())
        .then((html) => {
            navbarDiv.innerHTML = html;
            // Navbar specific listener for direct nav-link clicks
            navbarDiv.addEventListener("click", (event) => {
                const target = event.target;
                if (target.tagName === "A" && target.classList.contains("nav-link") && target.hasAttribute("data-page")) {
                    event.preventDefault();
                    const page = target.getAttribute("data-page");
                    loadContent(page);
                }
            });
            // Load initial content after navbar is ready
            loadContent("home");
        })
        .catch((error) => {
            console.error("Error loading navbar:", error);
            loadContent("home"); // Attempt to load home anyway
        });

    // Delegated listener for clicks anywhere in the body
    document.body.addEventListener("click", (event) => {
        const target = event.target.closest('a[data-page]');

        if (target) {
            // Check if the click originated inside the navbar AND was on a nav-link
            const isNavLinkClick = navbarDiv.contains(target) && target.classList.contains('nav-link');

            if (!isNavLinkClick) {
                // Handles clicks on buttons in explore, home page button, etc.
                event.preventDefault();

                const page = target.getAttribute("data-page");
                const categoryTarget = target.getAttribute("data-category-target");
                let anchorId = null;

                if (page === 'menu' && categoryTarget) {
                    anchorId = `category-${slugify(categoryTarget)}`;
                }

                loadContent(page, anchorId);
            }
        }
    });
});
