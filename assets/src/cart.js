// Define renderCart globally 
window.renderCart = function() {
    // Ensure cartItems exists, default to empty object if not
    window.cartItems = window.cartItems || {};

    console.log('renderCart (cart.js) called. window.cartItems:', JSON.stringify(window.cartItems));
    const cartDisplay = document.getElementById('cart-items-display');
    const cartSummary = document.getElementById('cart-summary');
    const customerDetailsSection = document.getElementById('customer-details-section'); // Get the right column section
    const tipInput = document.getElementById('tip-amount'); // Get tip input
    const placeOrderContainer = document.getElementById('place-order-button-container'); // Get container for Place Order button

    // Check if the target elements exist
    if (!cartDisplay || !cartSummary || !customerDetailsSection || !tipInput || !placeOrderContainer) {
        return;
    }

    cartDisplay.innerHTML = '';
    cartSummary.innerHTML = ''; // Clear summary initially
    placeOrderContainer.innerHTML = ''; // Clear button container initially

    let subtotal = 0;
    const taxRate = 0.10; // Reintroduced fixed tax rate (10%)
    // Default tip percentage - used if input is empty or invalid initially
    const defaultTipRate = 0.10; // Changed default tip rate to 10%

    const cartIsEmpty = Object.keys(window.cartItems).length === 0;
    console.log('Is cart empty?', cartIsEmpty);

    if (cartIsEmpty) {
        // Add class for styling
        cartDisplay.innerHTML = '<p class="cart-empty-message text-center my-5">Your cart is empty.</p>';
        customerDetailsSection.style.display = 'none'; // Hide customer details if cart is empty
        return;
    } else {
        customerDetailsSection.style.display = 'block'; // Show customer details if cart is not empty
    }

    const table = document.createElement('table');
    table.className = 'table cart-items-table';
    table.innerHTML = `
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-end">Price</th>
                <th class="text-center">Quantity</th>
                <th class="text-end">Total</th>
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
            <td class="text-end">&#8377;${item.price.toFixed(2)}</td>
            <td class="text-center">${item.quantity}</td>
            <td class="text-end">&#8377;${itemTotal.toFixed(2)}</td>
        `;
        tbody.appendChild(row);
    }

    cartDisplay.appendChild(table);

    // Function to render the summary part - needed for tip updates
    const renderSummary = () => {
        cartSummary.innerHTML = ''; // Clear previous summary

        // Get tip amount from input, default if invalid or empty
        let tip = parseFloat(tipInput.value);
        if (isNaN(tip) || tip < 0) {
            tip = 0;
        }

        // Calculate tax and total using fixed taxRate
        const tax = subtotal * taxRate;
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
                    <td>Tax (${(taxRate * 100).toFixed(0)}%)</td> <!-- Display fixed percentage -->
                    <td class="text-end">&#8377;${tax.toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Tip</td>
                    <td class="text-end">&#8377;${tip.toFixed(2)}</td>
                </tr>
                <tr class="fw-bold">
                    <td>Total</td>
                    <td class="text-end">&#8377;${total.toFixed(2)}</td>
                </tr>
            </tbody>
        `;
        cartSummary.appendChild(summaryTable);
    };

    // Initial setup for tip input
    if (!tipInput.dataset.listenerAttached) {
        // Calculate default tip based on subtotal and new rate
        tipInput.value = (subtotal * defaultTipRate).toFixed(2);
        tipInput.addEventListener('input', renderSummary);
        tipInput.dataset.listenerAttached = 'true';
    } else {
        let currentTip = parseFloat(tipInput.value);
         if (isNaN(currentTip) || currentTip < 0) {
             // Recalculate default if existing value is invalid
             tipInput.value = (subtotal * defaultTipRate).toFixed(2);
         }
    }

    // Initial rendering of the summary
    renderSummary();

    // Move Place Order button to the right column
    const placeOrderButton = document.createElement('button');
    placeOrderButton.className = 'btn btn-custom'; // Removed w-100 class
    placeOrderButton.textContent = 'Place Order';
    placeOrderButton.type = 'submit'; // Make it submit the form
    placeOrderButton.setAttribute('form', 'customer-details-form'); // Associate with the form

    // Add validation for form submission
    const customerForm = document.getElementById('customer-details-form');
    if (customerForm && !customerForm.dataset.submitListenerAttached) {
        customerForm.addEventListener('submit', (event) => {
            event.preventDefault(); // Prevent default form submission for now
            // Basic validation example
            const name = document.getElementById('customer-name').value;
            const mobile = document.getElementById('customer-mobile').value;
            if (!name || !mobile) {
                 alert('Please enter your Name and Mobile Number.');
                 return;
            }

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
            // Clear form fields
            customerForm.reset();
            // Reset tip to default calculation after clearing cart
            const newSubtotal = 0; // Cart is empty now
            // Calculate default tip based on 0 subtotal
            tipInput.value = (newSubtotal * defaultTipRate).toFixed(2);
            renderSummary(); // Update summary display
        });
        customerForm.dataset.submitListenerAttached = 'true';
    }

    placeOrderContainer.appendChild(placeOrderButton);

    // No dummy button logic was present, so no removal needed.
};
