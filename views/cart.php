<div class="container">
    <h1>Cart</h1>
    <!-- Add cart display logic here later -->
    <div id="cart-items-display">
        <!-- Cart items will be rendered here by JavaScript -->
    </div>
    <div id="cart-summary">
        <!-- Cart total will be shown here -->
    </div>
</div>

<script>
// Function to render cart items (can be called when cart page loads)
function renderCart() {
    const cartDisplay = document.getElementById('cart-items-display');
    const cartSummary = document.getElementById('cart-summary');
    if (!cartDisplay || !cartSummary) return;

    cartDisplay.innerHTML = ''; // Clear previous items
    let total = 0;

    if (Object.keys(window.cartItems).length === 0) {
        cartDisplay.innerHTML = '<p>Your cart is empty.</p>';
        cartSummary.innerHTML = '';
        return;
    }

    const table = document.createElement('table');
    table.className = 'table'; // Add bootstrap table styling
    table.innerHTML = `
        <thead>
            <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    `;
    const tbody = table.querySelector('tbody');

    for (const [itemId, item] of Object.entries(window.cartItems)) {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td>&#8377;${item.price.toFixed(2)}</td>
            <td>${item.quantity}</td>
            <td>&#8377;${itemTotal.toFixed(2)}</td>
        `;
        tbody.appendChild(row);
    }

    cartDisplay.appendChild(table);

    cartSummary.innerHTML = `<h4>Total: &#8377;${total.toFixed(2)}</h4>`;
}

// Render the cart when the cart view is loaded
document.addEventListener('DOMContentLoaded', renderCart);
// Also ensure renderCart is called if cartItems changes while on the cart page
// (This might need more robust event handling later if items can be changed *from* the cart page)

</script>
