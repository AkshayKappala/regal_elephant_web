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
        customerForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            console.log('Place Order button clicked, submit event triggered.'); // Log: Start
            const name = document.getElementById('customer-name').value;
            const mobile = document.getElementById('customer-mobile').value;
            const email = document.getElementById('customer-email').value;
            let tip = parseFloat(tipInput.value);
            if (isNaN(tip) || tip < 0) tip = 0;
            if (!name || !mobile) {
                console.error('Validation failed: Name or Mobile missing.'); // Log: Validation fail
                alert('Please enter your Name and Mobile Number.');
                return;
            }
            console.log('Form validation passed.'); // Log: Validation pass
            const cartItemsArr = Object.entries(window.cartItems).map(([key, item]) => ({
                name: item.name,
                price: item.price,
                quantity: item.quantity
            }));
            console.log('Cart items prepared for ID lookup:', cartItemsArr); // Log: Cart items
            let itemIdData;
            try {
                const itemIdResp = await fetch('api/get_item_ids.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items: cartItemsArr })
                });
                console.log('get_item_ids.php response status:', itemIdResp.status); // Log: Item ID response status
                itemIdData = await itemIdResp.json();
                console.log('get_item_ids.php response data:', itemIdData); // Log: Item ID response data
            } catch (error) {
                console.error('Error fetching item IDs:', error); // Log: Item ID fetch error
                alert('Error looking up item details. Please try again.');
                return;
            }
            if (!itemIdData.success) {
                console.error('Item ID lookup failed:', itemIdData.error); // Log: Item ID lookup fail
                alert('Could not process order: ' + (itemIdData.error || 'Item ID lookup failed.'));
                return;
            }
            const cartItemsWithIds = itemIdData.items;
            console.log('Cart items with IDs:', cartItemsWithIds); // Log: Cart items with IDs
            let subtotal = 0;
            cartItemsWithIds.forEach(item => { subtotal += item.price * item.quantity; });
            const tax = subtotal * taxRate;
            const total = subtotal + tax + tip;
            const orderPayload = {
                name,
                mobile,
                email,
                tip,
                cartItems: cartItemsWithIds
            };
            console.log('Placing order with payload:', orderPayload); // Log: Order payload
            let data;
            try {
                const resp = await fetch('api/place_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderPayload)
                });
                console.log('place_order.php response status:', resp.status); // Log: Place order response status
                data = await resp.json();
                console.log('place_order.php response data:', data); // Log: Place order response data
            } catch (error) {
                console.error('Error placing order:', error); // Log: Place order fetch error
                alert('Error submitting order. Please try again.');
                return;
            }
            if (data.success) {
                console.log('Order placed successfully! Order ID:', data.order_id, 'Order Number:', data.order_number); // Log: Success

                // Clear cart data first
                window.cartItems = {};
                console.log('window.cartItems cleared:', JSON.stringify(window.cartItems)); // Log: Confirm clear

                // Update UI elements related to cart
                if(typeof window.renderCart === 'function') {
                    console.log('Calling renderCart to clear UI...');
                    window.renderCart(); // Rerender the cart view (should show empty)
                }
                if(typeof window.updateCartBadge === 'function') {
                     console.log('Calling updateCartBadge...');
                    window.updateCartBadge(); // Update navbar badge
                }

                // Reset form and tip
                customerForm.reset();
                tipInput.value = (0).toFixed(2);
                // renderSummary might not be needed if renderCart handles the empty state correctly, but let's keep it for now
                renderSummary();

                // Store order details for the next page
                // localStorage.setItem('last_order_id', data.order_id);
                // localStorage.setItem('last_order_number', data.order_number); // Number might not be needed if fetched later

                // --- Store order ID in a list --- 
                let orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');
                if (!orderHistory.includes(data.order_id)) { // Avoid duplicates if user refreshes somehow
                    orderHistory.push(data.order_id);
                }
                // Optional: Limit history size if needed
                // const MAX_HISTORY = 10;
                // if (orderHistory.length > MAX_HISTORY) {
                //     orderHistory = orderHistory.slice(-MAX_HISTORY);
                // }
                localStorage.setItem('order_history', JSON.stringify(orderHistory));
                // --- End storing order ID ---

                // Redirect
                console.log('Redirecting to orders page...'); // Log: Redirecting
                if (typeof window.loadContent === 'function') {
                    window.loadContent('orders');
                } else {
                    console.error('window.loadContent function not found, falling back to hash change.'); // Log: loadContent missing
                    window.location.hash = '#orders';
                }
            } else {
                console.error('Order placement failed:', data.error); // Log: Order placement fail
                alert('Order failed: ' + (data.error || 'Unknown error.'));
            }
        });
        customerForm.dataset.submitListenerAttached = 'true';
    }

    placeOrderContainer.appendChild(placeOrderButton);

    // No dummy button logic was present, so no removal needed.
};
