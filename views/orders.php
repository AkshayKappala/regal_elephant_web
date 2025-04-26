<div class="container py-4">
    <h1 class="text-center mb-4">Your Orders</h1>
    
    <ul class="nav nav-tabs mb-4" id="ordersTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-orders" type="button" role="tab" aria-controls="active-orders" aria-selected="true">Active Orders</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past-orders" type="button" role="tab" aria-controls="past-orders" aria-selected="false">Past Orders</button>
        </li>
    </ul>
    
    <div class="tab-content" id="ordersTabContent">
        <div class="tab-pane fade show active" id="active-orders" role="tabpanel" aria-labelledby="active-tab">
            <div id="active-order-list" class="row justify-content-center g-4"></div>
        </div>
        <div class="tab-pane fade" id="past-orders" role="tabpanel" aria-labelledby="past-tab">
            <div id="past-order-list" class="row justify-content-center g-4"></div>
        </div>
    </div>
</div>

<script>
(async function() {
    const activeOrderList = document.getElementById('active-order-list');
    const pastOrderList = document.getElementById('past-order-list');
    const orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');

    if (!orderHistory || orderHistory.length === 0) {
        activeOrderList.innerHTML = `<div class='col-12 text-center'><p class='mt-4' style='color: #d4c3a2;'>You have no orders yet.</p></div>`;
        pastOrderList.innerHTML = `<div class='col-12 text-center'><p class='mt-4' style='color: #d4c3a2;'>You have no past orders.</p></div>`;
        return;
    }

    async function renderOrder(order, targetContainer) {
        try {
            let itemsHtml = '';
            order.items.forEach(item => {
                itemsHtml += `<tr>
                                <td>${item.name}</td>
                                <td class='text-center'>${item.quantity}</td>
                                <td class='text-end'>&#8377;${parseFloat(item.item_price).toFixed(2)}</td>
                              </tr>`;
            });

            // Determine status badge color
            let badgeClass = 'bg-warning text-dark';
            let statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
            
            if (order.status === 'ready') {
                badgeClass = 'bg-success';
            } else if (order.status === 'picked up') {
                badgeClass = 'bg-secondary';
            } else if (order.status === 'cancelled') {
                badgeClass = 'bg-danger';
            } else if (order.status === 'archived') {
                badgeClass = 'bg-info';
                statusText = 'Completed';
            }

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
                                    <p><strong>Status:</strong> <span class="badge ${badgeClass}">${statusText}</span></p>
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
            return `<div class='col-md-8'><div class='alert alert-danger'>Failed to load details for order.</div></div>`;
        }
    }

    // Process all orders
    const orderDetails = [];
    
    for (const orderId of orderHistory) {
        try {
            const response = await fetch(`api/get_order_details.php?order_id=${orderId}`);
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    orderDetails.push(data.order);
                }
            }
        } catch (error) {
            console.error(`Error fetching order ${orderId}:`, error);
        }
    }
    
    // Separate active from past orders
    // IMPORTANT: Past orders include archived AND cancelled
    const activeOrders = orderDetails.filter(order => 
        order.status !== 'archived' && order.status !== 'cancelled' && order.status !== 'picked up'
    );
    
    const pastOrders = orderDetails.filter(order => 
        order.status === 'archived' || order.status === 'cancelled' || order.status === 'picked up'
    );
    
    // Sort orders by placed time (newest first)
    activeOrders.sort((a, b) => new Date(b.order_placed_time) - new Date(a.order_placed_time));
    pastOrders.sort((a, b) => new Date(b.order_placed_time) - new Date(a.order_placed_time));
    
    // Render active orders
    if (activeOrders.length === 0) {
        activeOrderList.innerHTML = `<div class='col-12 text-center'><p class='mt-4' style='color: #d4c3a2;'>You have no active orders.</p></div>`;
    } else {
        const activeOrdersHtml = await Promise.all(activeOrders.map(order => renderOrder(order, activeOrderList)));
        activeOrderList.innerHTML = activeOrdersHtml.join('');
    }
    
    // Render past orders
    if (pastOrders.length === 0) {
        pastOrderList.innerHTML = `<div class='col-12 text-center'><p class='mt-4' style='color: #d4c3a2;'>You have no past orders.</p></div>`;
    } else {
        const pastOrdersHtml = await Promise.all(pastOrders.map(order => renderOrder(order, pastOrderList)));
        pastOrderList.innerHTML = pastOrdersHtml.join('');
    }
})();
</script>
