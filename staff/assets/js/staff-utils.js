/**
 * Utility functions for the staff portal
 */

/**
 * Formats a date in a user-friendly way
 * @param {string|Date} dateInput - The date to format
 * @returns {string} The formatted date string
 */
export function formatDate(dateInput) {
    const date = new Date(dateInput);
    return `${date.toLocaleString('default', { month: 'short' })} ${date.getDate()}, ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
}

/**
 * Formats a status string for display (capitalized with proper badge class)
 * @param {string} status - The order status
 * @returns {object} An object with formatted status and badge class
 */
export function formatStatus(status) {
    return {
        text: status.charAt(0).toUpperCase() + status.slice(1),
        badgeClass: `badge-${status.replace(' ', '-')}`
    };
}

/**
 * Creates the contact info HTML with optional email
 * @param {string} phone - The customer's phone number
 * @param {string|null} email - The customer's email (optional)
 * @returns {string} Formatted HTML for contact info
 */
export function formatContactInfo(phone, email) {
    return email 
        ? `${phone}<br><small>${email}</small>`
        : phone;
}

/**
 * Limits an array to a maximum number of items
 * @param {Array} array - The array to limit
 * @param {number} maxItems - Maximum number of items to keep
 * @returns {Array} The limited array
 */
export function limitArray(array, maxItems) {
    return array.slice(0, maxItems);
}

/**
 * Checks if a status is considered "active"
 * @param {string} status - The status to check
 * @returns {boolean} Whether the status is active
 */
export function isActiveStatus(status) {
    return status !== 'archived' && status !== 'cancelled' && status !== 'picked up';
}