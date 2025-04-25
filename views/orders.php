<div class="container py-4">
    <h1 class="text-center mb-4">Your Orders</h1>
    <div id="order-list-section" class="row justify-content-center g-4"></div>
</div>
<script>
(async function() {
    const orderListSection = document.getElementById('order-list-section');
    const orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');

    if (!orderHistory || orderHistory.length === 0) {
        orderListSection.innerHTML =
            `<div class='col-12 text-center'><p class='mt-4' style='color: #d4c3a2;'>You have no orders yet.</p></div>`;
        return;
    }

    async function renderOrder(orderId) {
        try {
            const response = await fetch(`api/get_order_details.php?order_id=${orderId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (!data.success) {
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

            return `
                <div class='col-md-8'>
                    <div class='card menu-item-card order-confirmation-card shadow mb-4'>
                        <div class='card-header order-card-header'>
                            <h4 class='mb-0 menu-item-name'>Order Confirmation</h4>
                            <p class='mb-0 order-number-display'>Order Number: <span class='fw-bold'>${order.order_number}</span></p>
                            <p class='mb-0 order-time-display'>Placed: ${new Date(order.order_placed_time).toLocaleString()}</p>
                        </div>
                        <div class='card-body order-card-body'>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> ${order.customer_name}</p>
                                    <p><strong>Phone:</strong> ${order.customer_phone}</p>
                                    <p><strong>Email:</strong> ${order.customer_email || '-'}</p>
                                    <p><strong>Status:</strong> <span class="badge bg-warning text-dark">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></p>
                                </div>
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
            return `<div class='col-md-8'><div class='alert alert-danger'>Failed to load details for order ID ${orderId}.</div></div>`;
        }
    }

    const reversedOrderHistory = orderHistory.slice().reverse();
    const orderPromises = reversedOrderHistory.map(orderId => renderOrder(orderId));
    const orderHtmlResults = await Promise.all(orderPromises);

    orderListSection.innerHTML = orderHtmlResults.join('');

})();
</script>
