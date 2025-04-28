export function formatDate(dateInput) {
    const date = new Date(dateInput);
    return `${date.toLocaleString('default', { month: 'short' })} ${date.getDate()}, ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
}

export function formatStatus(status) {
    return {
        text: status.charAt(0).toUpperCase() + status.slice(1),
        badgeClass: `badge-${status.replace(' ', '-')}`
    };
}

export function formatContactInfo(phone, email) {
    return email 
        ? `${phone}<br><small>${email}</small>`
        : phone;
}

export function limitArray(array, maxItems) {
    return array.slice(0, maxItems);
}

export function isActiveStatus(status) {
    return status !== 'archived' && status !== 'cancelled' && status !== 'picked up';
}