window.renderCart = function() {
    window.cartItems = window.cartItems || {};

    console.log('renderCart (cart.js) called. window.cartItems:', JSON.stringify(window.cartItems));
    const cartDisplay = document.getElementById('cart-items-display');
    const cartSummary = document.getElementById('cart-summary');
    const customerDetailsSection = document.getElementById('customer-details-section');
    const customerDetailsHeadingRow = customerDetailsSection?.previousElementSibling;
    const tipInput = document.getElementById('tip-amount');
    const placeOrderContainer = document.getElementById('place-order-button-container');

    if (!cartDisplay || !cartSummary || !customerDetailsSection || !tipInput || !placeOrderContainer) {
        return;
    }

    const customerNameInput = document.getElementById('customer-name');
    const customerMobileInput = document.getElementById('customer-mobile');
    const customerEmailInput = document.getElementById('customer-email');
    
    if (customerNameInput && customerMobileInput && customerEmailInput) {
        const savedCustomerDetails = JSON.parse(localStorage.getItem('customer_details') || '{}');
        if (savedCustomerDetails.name) customerNameInput.value = savedCustomerDetails.name;
        if (savedCustomerDetails.mobile) customerMobileInput.value = savedCustomerDetails.mobile;
        if (savedCustomerDetails.email) customerEmailInput.value = savedCustomerDetails.email;
    }

    cartDisplay.innerHTML = '';
    cartSummary.innerHTML = '';
    placeOrderContainer.innerHTML = '';

    let subtotal = 0;
    const taxRate = 0.10;
    const defaultTipRate = 0.10;

    const cartIsEmpty = Object.keys(window.cartItems).length === 0;

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

    renderSummary();

    const placeOrderButton = document.createElement('button');
    placeOrderButton.className = 'btn btn-custom';
    placeOrderButton.textContent = 'Place Order';
    placeOrderButton.type = 'submit';
    placeOrderButton.setAttribute('form', 'customer-details-form');

    const customerForm = document.getElementById('customer-details-form');
    if (customerForm && !customerForm.dataset.submitListenerAttached) {
        customerForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            console.log('Place Order button clicked, submit event triggered.');
            const name = document.getElementById('customer-name').value;
            const mobile = document.getElementById('customer-mobile').value;
            const email = document.getElementById('customer-email').value;
            let tip = parseFloat(tipInput.value);
            if (isNaN(tip) || tip < 0) tip = 0;
            if (!name || !mobile) {
                console.error('Validation failed: Name or Mobile missing.');
                alert('Please enter your Name and Mobile Number.');
                return;
            }
            localStorage.setItem('customer_details', JSON.stringify({ name, mobile, email }));
            
            console.log('Form validation passed.');
            const cartItemsArr = Object.entries(window.cartItems).map(([key, item]) => ({
                name: item.name,
                price: item.price,
                quantity: item.quantity
            }));
            console.log('Cart items prepared for ID lookup:', cartItemsArr);
            let itemIdData;
            try {
                const itemIdResp = await fetch('api/get_item_ids.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items: cartItemsArr })
                });
                console.log('get_item_ids.php response status:', itemIdResp.status);
                itemIdData = await itemIdResp.json();
                console.log('get_item_ids.php response data:', itemIdData);
            } catch (error) {
                console.error('Error fetching item IDs:', error);
                alert('Error looking up item details. Please try again.');
                return;
            }
            if (!itemIdData.success) {
                console.error('Item ID lookup failed:', itemIdData.error);
                alert('Could not process order: ' + (itemIdData.error || 'Item ID lookup failed.'));
                return;
            }
            
            const cartItemsWithIds = itemIdData.items;
            console.log('Cart items with IDs:', cartItemsWithIds);
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
            console.log('Placing order with payload:', orderPayload);
            
            let data;
            try {
                console.log('Attempting to fetch API configuration...');
                // Get API configuration from the dedicated endpoint
                const configResp = await fetch('config/get_api_config.php');
                if (!configResp.ok) {
                    throw new Error(`Config fetch failed with status: ${configResp.status}`);
                }
                
                const config = await configResp.json();
                console.log('API config received:', config);
                
                if (!config.staff_api_url || !config.api_key) {
                    throw new Error('Missing API configuration values');
                }
                
                console.log('Sending order to staff API at:', config.staff_api_url + 'receive_order.php');
                // Send order to staff API endpoint
                const resp = await fetch(config.staff_api_url + 'receive_order.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-API-Key': config.api_key
                    },
                    body: JSON.stringify(orderPayload)
                });
                console.log('Staff API response status:', resp.status);
                
                // If we get an error status, read the text first to log it
                if (!resp.ok) {
                    const errorText = await resp.text();
                    console.error('Error response from staff API:', errorText);
                    throw new Error(`Staff API returned status ${resp.status}: ${errorText}`);
                }
                
                data = await resp.json();
                console.log('Staff API response data:', data);
            } catch (error) {
                console.error('Error placing order:', error);
                alert('Error submitting order. Please try again.');
                return;
            }
            
            if (data.success) {
                console.log('Order placed successfully! Order ID:', data.order_id, 'Order Number:', data.order_number);

                window.cartItems = {};
                console.log('window.cartItems cleared:', JSON.stringify(window.cartItems));

                if(typeof window.renderCart === 'function') {
                    console.log('Calling renderCart to clear UI...');
                    window.renderCart();
                }
                if(typeof window.updateCartBadge === 'function') {
                     console.log('Calling updateCartBadge...');
                    window.updateCartBadge();
                }

                customerForm.reset();
                tipInput.value = (0).toFixed(2);
                
                renderSummary();

                let orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');
                if (!orderHistory.includes(data.order_id)) {
                    orderHistory.push(data.order_id);
                }
                localStorage.setItem('order_history', JSON.stringify(orderHistory));

                console.log('Redirecting to orders page...');
                if (typeof window.loadContent === 'function') {
                    window.loadContent('orders');
                } else {
                    console.error('window.loadContent function not found, falling back to hash change.');
                    window.location.hash = '#orders';
                }
            } else {
                console.error('Order placement failed:', data.error);
                alert('Order failed: ' + (data.error || 'Unknown error.'));
            }
        });
        customerForm.dataset.submitListenerAttached = 'true';
    }

    placeOrderContainer.appendChild(placeOrderButton);
};
