document.addEventListener("DOMContentLoaded", () => {
    const contentDiv = document.getElementById("content");
    const navbarDiv = document.getElementById("navbar");

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
                setActiveNavLink(page); // Update active link after content loads
                // Scroll to anchor if provided
                if (anchorTarget) {
                    // Use setTimeout to ensure the element exists in the DOM after render before scrolling
                    setTimeout(() => {
                        const element = document.getElementById(anchorTarget);
                        if (element) {
                            element.scrollIntoView({ behavior: "smooth", block: "start" });
                        } else {
                            console.warn(`Anchor target #${anchorTarget} not found.`); // Keep warning for debugging
                        }
                    }, 150);
                }
            })
            .catch((error) => {
                contentDiv.innerHTML = "<p>Error loading content.</p>";
                console.error("Error loading page:", error); // Keep error logging
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
            console.error("Error loading navbar:", error); // Keep error logging
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
