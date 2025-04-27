<style>
    /* Custom styling for order tabs */
    #orders-tabs {
        border-bottom: 2px solid #a04b25;
    }
    
    #orders-tabs .nav-link {
        font-family: "Fredoka", sans-serif;
        color: #d4c3a2;
        background-color: transparent;
        border: none;
        margin-bottom: -2px;
        padding: 0.75rem 1.5rem;
        font-size: 1.2rem;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    #orders-tabs .nav-link:hover {
        color: #f5e7c8;
        background-color: rgba(234, 218, 176, 0.1);
    }
    
    #orders-tabs .nav-link.active {
        color: #eadab0;
        background-color: rgba(160, 75, 37, 0.3);
        border-bottom: 3px solid #eadab0;
    }
    
    /* Custom styling for empty orders message */
    .empty-orders-message {
        background-color: rgba(234, 218, 176, 0.1);
        border: 1px solid rgba(234, 218, 176, 0.3);
        color: #eadab0;
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
        font-family: "Fredoka", sans-serif;
        font-size: 1.2rem;
        margin: 2rem 0;
    }
    
    .empty-orders-message i {
        font-size: 2rem;
        margin-bottom: 1rem;
        display: block;
    }
</style>

<div class="container py-4">
    <h1 class="text-center mb-4">Your Orders</h1>
    
    <!-- Tabs for Active Orders and Order History -->
    <ul class="nav nav-tabs mb-4 justify-content-center" id="orders-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-orders-tab" data-bs-toggle="tab" data-bs-target="#active-orders" type="button" role="tab" aria-controls="active-orders" aria-selected="true">Active Orders</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="order-history-tab" data-bs-toggle="tab" data-bs-target="#order-history" type="button" role="tab" aria-controls="order-history" aria-selected="false">Order History</button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="orders-tab-content">
        <!-- Active Orders Tab -->
        <div class="tab-pane fade show active" id="active-orders" role="tabpanel" aria-labelledby="active-orders-tab">
            <div id="active-orders-section" class="row justify-content-center g-4">
                <!-- Active orders will be loaded here -->
                <div class='col-12'><div class='alert alert-info text-center'>Loading active orders...</div></div>
            </div>
        </div>
        
        <!-- Order History Tab -->
        <div class="tab-pane fade" id="order-history" role="tabpanel" aria-labelledby="order-history-tab">
            <div id="order-history-section" class="row justify-content-center g-4">
                <!-- Order history will be loaded here -->
                <div class='col-12'><div class='alert alert-info text-center'>Loading order history...</div></div>
            </div>
        </div>
    </div>
</div>
<script>
(async function() {
    const activeOrdersSection = document.getElementById('active-orders-section');
    const orderHistorySection = document.getElementById('order-history-section');
    let orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');

    // Active order statuses
    const activeStatuses = ['preparing', 'ready', 'picked up'];
    // History statuses
    const historyStatuses = ['archived', 'cancelled'];

    // Function to handle initial orders display
    function displayOrders() {
        if (!orderHistory || orderHistory.length === 0) {
            activeOrdersSection.innerHTML =
                `<div class='col-12'><div class='empty-orders-message'><i class="bi bi-bag"></i>No active orders yet. Browse our menu to place an order.</div></div>`;
            orderHistorySection.innerHTML =
                `<div class='col-12'><div class='empty-orders-message'><i class="bi bi-clock-history"></i>No order history available.</div></div>`;
            return;
        }

        // Show loading indicators
        activeOrdersSection.innerHTML = 
            `<div class='col-12 text-center'><div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div></div>`;
        orderHistorySection.innerHTML = 
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
            const orderResults = await Promise.all(orderPromises);
            
            // Separate active orders from history
            const activeOrdersHtml = [];
            const orderHistoryHtml = [];
            
            orderResults.forEach(result => {
                if (result.html && result.status) {
                    if (activeStatuses.includes(result.status.toLowerCase())) {
                        activeOrdersHtml.push(result.html);
                    } else if (historyStatuses.includes(result.status.toLowerCase())) {
                        orderHistoryHtml.push(result.html);
                    }
                }
            });
            
            // Update the sections
            if (activeOrdersHtml.length > 0) {
                activeOrdersSection.innerHTML = activeOrdersHtml.join('');
            } else {
                activeOrdersSection.innerHTML = `<div class='col-12'><div class='empty-orders-message'><i class="bi bi-bag"></i>No active orders yet. Browse our menu to place an order.</div></div>`;
            }
            
            if (orderHistoryHtml.length > 0) {
                orderHistorySection.innerHTML = orderHistoryHtml.join('');
            } else {
                orderHistorySection.innerHTML = `<div class='col-12'><div class='empty-orders-message'><i class="bi bi-clock-history"></i>No order history available.</div></div>`;
            }
        } catch (error) {
            console.error('Error loading orders:', error);
            activeOrdersSection.innerHTML = 
                `<div class='col-12'><div class='alert alert-danger text-center'>Error loading orders. Please try again later.</div></div>`;
            orderHistorySection.innerHTML = 
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
                return {
                    html: `<div class='col-md-8'><div class='alert alert-warning'>Could not load details for order ID ${orderId}.</div></div>`,
                    status: null
                };
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

            // Determine badge color class based on status
            let badgeClass = 'bg-warning text-dark';
            if (order.status === 'ready') {
                badgeClass = 'bg-success text-white';
            } else if (order.status === 'picked up') {
                badgeClass = 'bg-primary text-white';
            } else if (order.status === 'cancelled') {
                badgeClass = 'bg-danger text-white';
            } else if (order.status === 'archived') {
                badgeClass = 'bg-secondary text-white';
            }

            // Return the HTML string for this order card and its status
            return {
                html: `
                <div class='col-md-8' data-order-id="${order.order_id}" data-order-status="${order.status}">
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
                                    <p><strong>Status:</strong> <span class="badge ${badgeClass} order-status">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></p>
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
                </div>`,
                status: order.status
            };
        } catch (error) {
            console.error(`Error fetching order ${orderId}:`, error);
            return {
                html: `<div class='col-md-8'><div class='alert alert-danger'>Failed to load details for order ID ${orderId}.</div></div>`,
                status: null
            };
        }
    }

    // Function to update an existing order card or add a new one
    function updateOrderCard(order) {
        // Function to check if the order belongs in active or history
        function isActiveOrder(status) {
            return activeStatuses.includes(status.toLowerCase());
        }
        
        // Get the current status of the order
        const currentStatus = order.status.toLowerCase();
        
        // Check if this order is in our history
        if (!orderHistory.includes(order.order_id)) {
            // This is a new order, add it to history
            orderHistory.push(order.order_id);
            localStorage.setItem('order_history', JSON.stringify(orderHistory));
            
            // Render and insert the new order
            renderOrder(order.order_id).then(result => {
                if (result.html) {
                    const targetSection = isActiveOrder(currentStatus) ? activeOrdersSection : orderHistorySection;
                    
                    // Add to the top of the list
                    if (targetSection.firstChild) {
                        targetSection.insertAdjacentHTML('afterbegin', result.html);
                    } else {
                        targetSection.innerHTML = result.html;
                    }
                    
                    // Replace empty list message if it exists
                    const emptyMessage = targetSection.querySelector('.alert.alert-info');
                    if (emptyMessage) {
                        emptyMessage.remove();
                    }
                }
            });
        } else {
            // Find the existing card
            const existingCard = document.querySelector(`[data-order-id="${order.order_id}"]`);
            if (existingCard) {
                const oldStatus = existingCard.getAttribute('data-order-status').toLowerCase();
                const newStatus = order.status.toLowerCase();
                
                // Check if the order is moving between tabs
                if (isActiveOrder(oldStatus) !== isActiveOrder(newStatus)) {
                    // Need to move the card to a different tab
                    existingCard.remove();
                    
                    // Re-render the order in the new section
                    renderOrder(order.order_id).then(result => {
                        if (result.html) {
                            const targetSection = isActiveOrder(newStatus) ? activeOrdersSection : orderHistorySection;
                            
                            // Add to the top of the list
                            if (targetSection.firstChild) {
                                targetSection.insertAdjacentHTML('afterbegin', result.html);
                            } else {
                                targetSection.innerHTML = result.html;
                            }
                            
                            // Replace empty list message if it exists
                            const emptyMessage = targetSection.querySelector('.alert.alert-info');
                            if (emptyMessage) {
                                emptyMessage.remove();
                            }
                        }
                    });
                } else {
                    // Just update the status badge
                    const statusBadge = existingCard.querySelector('.order-status');
                    if (statusBadge) {
                        statusBadge.textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                        existingCard.setAttribute('data-order-status', order.status);
                        
                        // Update badge color based on status
                        statusBadge.className = 'badge order-status';
                        if (order.status === 'preparing') {
                            statusBadge.classList.add('bg-warning', 'text-dark');
                        } else if (order.status === 'ready') {
                            statusBadge.classList.add('bg-success', 'text-white');
                        } else if (order.status === 'picked up') {
                            statusBadge.classList.add('bg-primary', 'text-white');
                        } else if (order.status === 'cancelled') {
                            statusBadge.classList.add('bg-danger', 'text-white');
                        } else if (order.status === 'archived') {
                            statusBadge.classList.add('bg-secondary', 'text-white');
                        }
                    }
                }
            }
        }
        
        // Check if sections are empty and add messages if needed
        if (activeOrdersSection.children.length === 0) {
            activeOrdersSection.innerHTML = `<div class='col-12'><div class='empty-orders-message'><i class="bi bi-bag"></i>No active orders yet. Browse our menu to place an order.</div></div>`;
        }
        
        if (orderHistorySection.children.length === 0) {
            orderHistorySection.innerHTML = `<div class='col-12'><div class='empty-orders-message'><i class="bi bi-clock-history"></i>No order history available.</div></div>`;
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
