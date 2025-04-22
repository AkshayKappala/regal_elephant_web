// Define renderCart globally 
window.renderCart = function() {
    // Ensure cartItems exists, default to empty object if not
    window.cartItems = window.cartItems || {}; 

    console.log('renderCart (cart.js) called. window.cartItems:', JSON.stringify(window.cartItems)); 
    const cartDisplay = document.getElementById('cart-items-display');
    const cartSummary = document.getElementById('cart-summary');
    
    // Check if the target elements exist 
    if (!cartDisplay || !cartSummary) {
        // Don't log error if elements aren't expected (e.g., not on cart page)
        // console.error('Cart display or summary element not found when renderCart (cart.js) was called!'); 
        return;
    }

    cartDisplay.innerHTML = '';
    cartSummary.innerHTML = ''; // Clear summary initially
    let subtotal = 0;
    const taxRate = 0.10; // Example: 10% tax
    const tipRate = 0.15; // Example: 15% tip

    const cartIsEmpty = Object.keys(window.cartItems).length === 0;
    console.log('Is cart empty?', cartIsEmpty); 

    if (cartIsEmpty) {
        // Add class for styling
        cartDisplay.innerHTML = '<p class="cart-empty-message text-center my-5">Your cart is empty.</p>'; 
        // Don't add buttons if cart is empty
        return; 
    }

    const table = document.createElement('table');
    table.className = 'table cart-items-table'; 
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
        subtotal += itemTotal;
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

    // Calculate tax, tip, and total
    const tax = subtotal * taxRate;
    const tip = subtotal * tipRate;
    const total = subtotal + tax + tip;

    // Render summary table
    const summaryTable = document.createElement('table');
    summaryTable.className = 'table cart-summary-table'; 
    summaryTable.innerHTML = `
        <tbody>
            <tr>
                <td>Subtotal</td>
                <td class="text-end">&#8377;${subtotal.toFixed(2)}</td>
            </tr>
            <tr>
                <td>Tax (${(taxRate * 100).toFixed(0)}%)</td>
                <td class="text-end">&#8377;${tax.toFixed(2)}</td>
            </tr>
            <tr>
                <td>Tip (${(tipRate * 100).toFixed(0)}%)</td>
                <td class="text-end">&#8377;${tip.toFixed(2)}</td>
            </tr>
            <tr class="fw-bold">
                <td>Total</td>
                <td class="text-end">&#8377;${total.toFixed(2)}</td>
            </tr>
        </tbody>
    `;
    cartSummary.appendChild(summaryTable);

    // Create a container for buttons to manage layout
    const buttonContainer = document.createElement('div');
    buttonContainer.className = 'd-flex justify-content-end mt-3'; 

    // Add Dummy button
    const dummyButton = document.createElement('button');
    dummyButton.className = 'btn btn-secondary me-2'; 
    dummyButton.textContent = 'Dummy Button';
    dummyButton.onclick = () => {
        alert('Dummy button clicked!');
    };
    buttonContainer.appendChild(dummyButton); 

    // Add Place Order button
    const placeOrderButton = document.createElement('button');
    placeOrderButton.className = 'btn btn-custom'; 
    placeOrderButton.textContent = 'Place Order';
    placeOrderButton.onclick = () => {
        alert('Order Placed! (Placeholder)'); 
        window.cartItems = {}; 
        // Call renderCart again (it's globally available via window)
        if(typeof window.renderCart === 'function') {
            window.renderCart(); 
        }
        // Also update the badge via the global function from main.js
        if(typeof window.updateCartBadge === 'function') {
            window.updateCartBadge(); 
        }
    };
    buttonContainer.appendChild(placeOrderButton); 

    // Append the container with both buttons after the summary table
    cartSummary.appendChild(buttonContainer);
};
