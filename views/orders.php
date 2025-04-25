<div class="container py-4">
    <h1 class="text-center mb-4">Your Orders</h1>
    <div id="order-list-section" class="row justify-content-center g-4"></div> <!-- Changed ID and added gap -->
</div>
<script>
(async function() { // Use async function for easier await
    const orderListSection = document.getElementById('order-list-section');
    const orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');

    if (!orderHistory || orderHistory.length === 0) {
        orderListSection.innerHTML =
            `<div class='col-12'><div class='alert alert-info text-center'>No orders placed in this session yet.</div></div>`;
        return;
    }

    // Function to fetch and render a single order
    async function renderOrder(orderId) {
        try {
            const response = await fetch(`api/get_order_details.php?order_id=${orderId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (!data.success) {
                console.error(`Error fetching order ${orderId}:`, data.error);
                return `<div class='col-md-8'><div class='alert alert-warning'>Could not load details for order ID ${orderId}.</div></div>`;
            }

            const order = data.order;
            let itemsHtml = '';
            order.items.forEach(item => {
                itemsHtml += `<tr>
                                <td>${item.name}</td>
                                <td class='text-center'>${item.quantity}</td>
                                <td class='text-end'>&#8377;${parseFloat(item.item_price).toFixed(2)}</td>
                              </tr>`;
            });

            // Return the HTML string for this order card
            return `
                <div class='col-md-8'>
                    <div class='card menu-item-card order-confirmation-card shadow mb-4'> <!-- Added order-confirmation-card class -->
                        <div class='card-header order-card-header'>
                            <h4 class='mb-0 menu-item-name'>Order Confirmation</h4>
                            <p class='mb-0 order-number-display'>Order Number: <span class='fw-bold'>${order.order_number}</span></p>
                            <p class='mb-0 order-time-display'>Placed: ${new Date(order.order_placed_time).toLocaleString()}</p> <!-- Added timestamp -->
                        </div>
                        <div class='card-body order-card-body'>
                            <div class="row">
                                <!-- Left Column: Customer Details & Status -->
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> ${order.customer_name}</p>
                                    <p><strong>Phone:</strong> ${order.customer_phone}</p>
                                    <p><strong>Email:</strong> ${order.customer_email || '-'}</p>
                                    <p><strong>Status:</strong> <span class="badge bg-warning text-dark">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></p>
                                </div>
                                <!-- Right Column: Items & Total -->
                                <div class="col-md-6">
                                    <h5 class="mb-2">Items Ordered:</h5>
                                    <table class='table cart-items-table mb-3'>
                                        <thead><tr><th>Item</th><th class='text-center'>Qty</th><th class='text-end'>Price</th></tr></thead>
                                        <tbody>${itemsHtml}</tbody>
                                    </table>
                                    <div class='text-end order-total-display'>
                                        <span class='fw-bold fs-5'>Total: &#8377;${parseFloat(order.order_total).toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
        } catch (error) {
            console.error(`Error fetching order ${orderId}:`, error);
            return `<div class='col-md-8'><div class='alert alert-danger'>Failed to load details for order ID ${orderId}.</div></div>`;
        }
    }

    // Fetch all orders in parallel and render them
    // Show latest orders first by reversing the history
    const reversedOrderHistory = orderHistory.slice().reverse();
    const orderPromises = reversedOrderHistory.map(orderId => renderOrder(orderId));
    const orderHtmlResults = await Promise.all(orderPromises);

    // Join the HTML strings and set the innerHTML
    orderListSection.innerHTML = orderHtmlResults.join('');

})();
</script>
