document.addEventListener("DOMContentLoaded", () => {
    const contentDiv = document.getElementById("content");
    const navbarDiv = document.getElementById("navbar");

    // Load navbar dynamically
    fetch("partials/navbar.html")
        .then((response) => response.text())
        .then((html) => {
            navbarDiv.innerHTML = html;
        })
        .catch((error) => {
            console.error("Error loading navbar:", error);
        });

    // Load content dynamically
    const loadContent = (page) => {
        fetch(`views/${page}.html`)
            .then((response) => response.text())
            .then((html) => {
                contentDiv.innerHTML = html;
            })
            .catch((error) => {
                contentDiv.innerHTML = "<p>Error loading content.</p>";
                console.error("Error loading page:", error);
            });
    };

    // Load homepage by default
    loadContent("home");

    // Event listeners to navbar links
    document.querySelectorAll(".nav-link").forEach((link) => {
        link.addEventListener("click", (event) => {
            event.preventDefault();
            const page = link.getAttribute("data-page");
            loadContent(page);

            // Update active link
            document.querySelectorAll(".nav-link").forEach((navLink) => {
                navLink.classList.remove("active");
            });
            link.classList.add("active");
        });
    });

    // Delegate clicks for dynamically loaded elements
    document.body.addEventListener("click", (event) => {
        const target = event.target;
        if (target.tagName === "A" && target.hasAttribute("data-page")) {
            event.preventDefault();
            const page = target.getAttribute("data-page");
            loadContent(page);

            // Update active link in the navbar if applicable
            document.querySelectorAll(".nav-link").forEach((navLink) => {
                navLink.classList.remove("active");
            });
            const navLink = document.querySelector(`.nav-link[data-page="${page}"]`);
            if (navLink) {
                navLink.classList.add("active");
            }
        }
    });
});
