/**
 * Cart functionality - handles rendering cart contents and order submission
 */

window.renderCart = function() {
    // Ensure cartItems exists, default to empty object if not
    window.cartItems = window.cartItems || {};

    const cartDisplay = document.getElementById('cart-items-display');
    const cartSummary = document.getElementById('cart-summary');
    const customerDetailsSection = document.getElementById('customer-details-section');
    const customerDetailsHeadingRow = customerDetailsSection?.previousElementSibling;
    const tipInput = document.getElementById('tip-amount');
    const placeOrderContainer = document.getElementById('place-order-button-container');

    if (!cartDisplay || !cartSummary || !customerDetailsSection || !tipInput || !placeOrderContainer) {
        return;
    }

    // Load saved customer details if they exist
    const customerNameInput = document.getElementById('customer-name');
    const customerMobileInput = document.getElementById('customer-mobile');
    const customerEmailInput = document.getElementById('customer-email');
    
    if (customerNameInput && customerMobileInput && customerEmailInput) {
        const savedCustomerDetails = JSON.parse(localStorage.getItem('customer_details') || '{}');
        if (savedCustomerDetails.name) customerNameInput.value = savedCustomerDetails.name;
        if (savedCustomerDetails.mobile) customerMobileInput.value = savedCustomerDetails.mobile;
        if (savedCustomerDetails.email) customerEmailInput.value = savedCustomerDetails.email;
    }

    // Clear existing content
    cartDisplay.innerHTML = '';
    cartSummary.innerHTML = '';
    placeOrderContainer.innerHTML = '';

    // Initialize variables
    let subtotal = 0;
    const taxRate = 0.10;
    const defaultTipRate = 0.10;

    // Check if cart is empty
    const cartIsEmpty = Object.keys(window.cartItems).length === 0;

    // Show empty cart message if cart is empty
    if (cartIsEmpty) {
        cartDisplay.innerHTML = '<div class="col-12 text-center"><p class="cart-empty-message my-5">Your cart is empty.</p></div>';
        customerDetailsSection.style.display = 'none';
        if (customerDetailsHeadingRow && customerDetailsHeadingRow.querySelector('h3')) {
            customerDetailsHeadingRow.style.display = 'none';
        }
        return;
    } else {
        customerDetailsSection.style.display = 'block';
        if (customerDetailsHeadingRow && customerDetailsHeadingRow.querySelector('h3')) {
            customerDetailsHeadingRow.style.display = '';
        }
    }

    // Create cart items table
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

    // Add cart items to table
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

    // Function to render the summary part
    const renderSummary = () => {
        cartSummary.innerHTML = '';

        let tip = parseFloat(tipInput.value);
        if (isNaN(tip) || tip < 0) {
            tip = 0;
        }

        const tax = subtotal * taxRate;
        const total = subtotal + tax + tip;

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

    // Setup tip input
    if (!tipInput.dataset.listenerAttached) {
        tipInput.value = (subtotal * defaultTipRate).toFixed(2);
        tipInput.addEventListener('input', renderSummary);
        tipInput.dataset.listenerAttached = 'true';
    } else {
        let currentTip = parseFloat(tipInput.value);
         if (isNaN(currentTip) || currentTip < 0) {
             tipInput.value = (subtotal * defaultTipRate).toFixed(2);
         }
    }

    // Initial rendering of the summary
    renderSummary();

    // Create place order button
    const placeOrderButton = document.createElement('button');
    placeOrderButton.className = 'btn btn-custom';
    placeOrderButton.textContent = 'Place Order';
    placeOrderButton.type = 'submit';
    placeOrderButton.setAttribute('form', 'customer-details-form');

    // Setup form submission handling
    const customerForm = document.getElementById('customer-details-form');
    if (customerForm && !customerForm.dataset.submitListenerAttached) {
        customerForm.addEventListener('submit', placeOrderHandler);
        customerForm.dataset.submitListenerAttached = 'true';
    }

    placeOrderContainer.appendChild(placeOrderButton);
};

/**
 * Handle order placement when the form is submitted
 * @param {Event} event - The form submit event
 */
async function placeOrderHandler(event) {
    event.preventDefault();
    
    const name = document.getElementById('customer-name').value;
    const mobile = document.getElementById('customer-mobile').value;
    const email = document.getElementById('customer-email').value;
    const tipInput = document.getElementById('tip-amount');
    let tip = parseFloat(tipInput.value);
    if (isNaN(tip) || tip < 0) tip = 0;
    
    // Validate form inputs
    if (!name || !mobile) {
        alert('Please enter your Name and Mobile Number.');
        return;
    }
    
    // Save customer details
    localStorage.setItem('customer_details', JSON.stringify({ name, mobile, email }));
    
    // Prepare cart items data
    const cartItemsArr = Object.entries(window.cartItems).map(([key, item]) => ({
        name: item.name,
        price: item.price,
        quantity: item.quantity
    }));
    
    // Get item IDs from API
    let itemIdData;
    try {
        const itemIdResp = await fetch('api/get_item_ids.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: cartItemsArr })
        });
        itemIdData = await itemIdResp.json();
    } catch (error) {
        alert('Error looking up item details. Please try again.');
        return;
    }
    
    if (!itemIdData.success) {
        alert('Could not process order: ' + (itemIdData.error || 'Item ID lookup failed.'));
        return;
    }
    
    // Calculate order totals
    const cartItemsWithIds = itemIdData.items;
    const taxRate = 0.10;
    let subtotal = 0;
    cartItemsWithIds.forEach(item => { subtotal += item.price * item.quantity; });
    
    // Create order payload
    const orderPayload = {
        name,
        mobile,
        email,
        tip,
        cartItems: cartItemsWithIds
    };
    
    // Submit order to API
    let data;
    try {
        const configResp = await fetch('config/get_api_config.php');
        const config = await configResp.json();
        
        if (!config.staff_api_url || !config.api_key) {
            throw new Error('Missing API configuration');
        }
        
        const resp = await fetch(config.staff_api_url + 'receive_order.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-API-Key': config.api_key
            },
            body: JSON.stringify(orderPayload)
        });
        
        if (!resp.ok) {
            throw new Error(`Staff API returned status ${resp.status}`);
        }
        
        data = await resp.json();
    } catch (error) {
        alert('Error submitting order. Please try again.');
        return;
    }
    
    // Handle order placement result
    if (data.success) {
        // Clear cart data
        window.cartItems = {};

        // Update UI
        if(typeof window.renderCart === 'function') {
            window.renderCart();
        }
        if(typeof window.updateCartBadge === 'function') {
            window.updateCartBadge();
        }

        // Reset form
        event.target.reset();
        if (tipInput) tipInput.value = (0).toFixed(2);
        
        // Update order history
        let orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');
        if (!orderHistory.includes(data.order_id)) {
            orderHistory.push(data.order_id);
        }
        localStorage.setItem('order_history', JSON.stringify(orderHistory));

        // Navigate to orders page
        if (typeof window.loadContent === 'function') {
            window.loadContent('orders');
        } else {
            window.location.hash = '#orders';
        }
    } else {
        alert('Order failed: ' + (data.error || 'Unknown error.'));
    }
}
