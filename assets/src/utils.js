/**
 * Utility functions for the Regal Elephant application
 */

/**
 * Converts a string to a URL-friendly slug
 * @param {string} text - The text to slugify
 * @returns {string} The slugified text
 */
export function slugify(text) {
    if (!text) return '';
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}

/**
 * Scroll to the top of the page with smooth animation
 */
export function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}