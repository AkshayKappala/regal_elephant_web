<div class="container">
    <h1>Cart</h1>
    <div id="cart-items-display">
    </div>
    <div id="cart-summary">
    </div>
</div>

<script>
function renderCart() {
    const cartDisplay = document.getElementById('cart-items-display');
    const cartSummary = document.getElementById('cart-summary');
    if (!cartDisplay || !cartSummary) return;

    cartDisplay.innerHTML = ''; 
    let total = 0;

    if (Object.keys(window.cartItems).length === 0) {
        cartDisplay.innerHTML = '<p>Your cart is empty.</p>';
        cartSummary.innerHTML = '';
        return;
    }

    const table = document.createElement('table');
    table.className = 'table'; 
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

document.addEventListener('DOMContentLoaded', renderCart);

</script>
