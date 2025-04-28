import { updateCartBadge } from './ui.js';

export function updateMenuQuantities(contentDiv, cartItems) {
    for (const [itemId, cartItem] of Object.entries(cartItems)) {
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

        if (!cartItems[itemId] || cartItems[itemId].quantity === 0) {
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
    
    updateCartBadge();
}

export function attachQuantityWidgetListeners(contentDiv, cartItems) {
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
            if (!cartItems[itemId]) {
                cartItems[itemId] = { quantity: 1, name: itemName, price: itemPrice };
            } else {
                cartItems[itemId].quantity += 1;
            }
            updateMenuQuantities(contentDiv, cartItems);
        });

        decBtn.addEventListener('click', function() {
            if (cartItems[itemId] && cartItems[itemId].quantity > 0) {
                cartItems[itemId].quantity -= 1;
                if (cartItems[itemId].quantity === 0) {
                    delete cartItems[itemId];
                }
                updateMenuQuantities(contentDiv, cartItems);
            }
        });
    });
}