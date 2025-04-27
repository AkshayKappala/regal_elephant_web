<div class="container py-4">
    <h1 class="text-center mb-4">Your Orders</h1>
    <div id="order-list-section" class="row justify-content-center g-4"></div>
</div>
<script>
(async function() {
    const orderListSection = document.getElementById('order-list-section');
    let orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');

    // Function to handle initial orders display
    function displayOrders() {
        if (!orderHistory || orderHistory.length === 0) {
            orderListSection.innerHTML =
                `<div class='col-12'><div class='alert alert-info text-center'>No orders placed in this session yet.</div></div>`;
            return;
        }

        // Show loading indicator
        orderListSection.innerHTML = 
            `<div class='col-12 text-center'><div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div></div>`;
            
        // Launch async loading process
        loadOrderDetails();
    }

    // Function to fetch and render all orders
    async function loadOrderDetails() {
        try {
            // Show latest orders first by reversing the history
            const reversedOrderHistory = orderHistory.slice().reverse();
            const orderPromises = reversedOrderHistory.map(orderId => renderOrder(orderId));
            const orderHtmlResults = await Promise.all(orderPromises);
            
            // Join the HTML strings and set the innerHTML
            orderListSection.innerHTML = orderHtmlResults.join('');
        } catch (error) {
            console.error('Error loading orders:', error);
            orderListSection.innerHTML = 
                `<div class='col-12'><div class='alert alert-danger text-center'>Error loading orders. Please try again later.</div></div>`;
        }
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
            
            // Skip archived orders
            if (order.status === 'archived') {
                return '';
            }
            
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
                <div class='col-md-8' data-order-id="${order.order_id}">
                    <div class='card menu-item-card order-confirmation-card shadow mb-4'>
                        <div class='card-header order-card-header'>
                            <h4 class='mb-0 menu-item-name'>Order Confirmation</h4>
                            <p class='mb-0 order-number-display'>Order Number: <span class='fw-bold'>${order.order_number}</span></p>
                            <p class='mb-0 order-time-display'>Placed: ${new Date(order.order_placed_time).toLocaleString()}</p>
                        </div>
                        <div class='card-body order-card-body'>
                            <div class="row">
                                <!-- Left Column: Customer Details & Status -->
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> ${order.customer_name}</p>
                                    <p><strong>Phone:</strong> ${order.customer_phone}</p>
                                    <p><strong>Email:</strong> ${order.customer_email || '-'}</p>
                                    <p><strong>Status:</strong> <span class="badge bg-warning text-dark order-status">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></p>
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

    // Function to update an existing order card or add a new one
    function updateOrderCard(order) {
        // Skip archived orders
        if (order.status === 'archived') {
            return;
        }
        
        // Check if this order is in our history
        if (!orderHistory.includes(order.order_id)) {
            // This is a new order, add it to history
            orderHistory.push(order.order_id);
            localStorage.setItem('order_history', JSON.stringify(orderHistory));
            
            // Render and insert the new order
            renderOrder(order.order_id).then(html => {
                if (html) {
                    // Add to the top of the list
                    if (orderListSection.firstChild) {
                        orderListSection.insertAdjacentHTML('afterbegin', html);
                    } else {
                        orderListSection.innerHTML = html;
                    }
                }
            });
            
            // Replace empty list message if it exists
            const emptyMessage = orderListSection.querySelector('.alert.alert-info');
            if (emptyMessage) {
                emptyMessage.remove();
            }
        } else {
            // Update the status in the existing card
            const existingCard = document.querySelector(`[data-order-id="${order.order_id}"]`);
            if (existingCard) {
                const statusBadge = existingCard.querySelector('.order-status');
                if (statusBadge) {
                    statusBadge.textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                    
                    // Update badge color based on status
                    statusBadge.className = 'badge order-status';
                    if (order.status === 'preparing') {
                        statusBadge.classList.add('bg-warning', 'text-dark');
                    } else if (order.status === 'ready') {
                        statusBadge.classList.add('bg-success', 'text-white');
                    } else if (order.status === 'picked_up') {
                        statusBadge.classList.add('bg-primary', 'text-white');
                    } else if (order.status === 'cancelled') {
                        statusBadge.classList.add('bg-danger', 'text-white');
                    }
                }
            }
        }
    }

    // Subscribe to server-sent events for real-time updates
    function subscribeToOrderEvents() {
        const eventSource = new EventSource('api/order_events.php');
        
        eventSource.addEventListener('connection', function(e) {
            console.log('SSE connection established');
        });
        
        eventSource.addEventListener('new_orders', function(e) {
            const data = JSON.parse(e.data);
            if (data.orders && data.orders.length > 0) {
                data.orders.forEach(order => {
                    updateOrderCard(order);
                });
            }
        });
        
        eventSource.addEventListener('order_update', function(e) {
            const data = JSON.parse(e.data);
            if (data.order) {
                updateOrderCard(data.order);
            }
        });
        
        eventSource.addEventListener('error', function(e) {
            console.error('SSE connection error:', e);
            // Try to reconnect after a delay
            setTimeout(function() {
                subscribeToOrderEvents();
            }, 5000);
        });
        
        // Clean up function to close the connection when navigating away
        window.addEventListener('beforeunload', function() {
            eventSource.close();
        });
    }

    // Initialize the orders display
    displayOrders();
    
    // Start listening for updates
    subscribeToOrderEvents();
})();
</script>
