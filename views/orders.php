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
<script type="module">
import { addOrderEventListener } from './assets/src/events.js';

(async function() {
    const activeOrdersSection = document.getElementById('active-orders-section');
    const orderHistorySection = document.getElementById('order-history-section');
    let orderHistory = JSON.parse(localStorage.getItem('order_history') || '[]');

    // Active order statuses
    const activeStatuses = ['preparing', 'ready']; // Removed 'picked up' from active statuses
    // History statuses
    const historyStatuses = ['archived', 'cancelled', 'picked up']; // Added 'picked up' to history statuses

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
            console.log(`Fetching details for order ${orderId}`);
            const response = await fetch(`api/get_order_details.php?order_id=${orderId}&_nocache=${Date.now()}`);
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
            console.log(`Received order ${orderId} with status: ${order.status}`);
            
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
                <div class='col-md-8 order-card' data-order-id="${order.order_id}" data-order-status="${order.status}">
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

    // This function updates an order card when its status changes
    function updateOrderCard(order) {
        if (!order || !order.order_id) return;
        
        const orderId = order.order_id;
        // Try both selectors to ensure we find the card
        let existingCard = document.querySelector(`.order-card[data-order-id="${orderId}"]`);
        if (!existingCard) {
            existingCard = document.querySelector(`div[data-order-id="${orderId}"]`);
        }
        
        const newStatus = order.status || 'unknown';
        
        console.log(`Updating order ${orderId} status to ${newStatus}, card exists: ${!!existingCard}`);
        
        // Only force reload if the status has actually changed
        // This prevents constant reloading of cards
        if (existingCard) {
            const currentStatus = existingCard.getAttribute('data-order-status');
            if (currentStatus === newStatus) {
                console.log(`Order ${orderId} already has status ${newStatus}, skipping update`);
                return; // Skip update if status hasn't changed
            }
            
            console.log(`Status changed from ${currentStatus} to ${newStatus}, updating card`);
        }
        
        // If the card doesn't exist, we need to add it
        if (!existingCard) {
            // Fetch the full order details and render it with cache-busting
            renderOrder(orderId).then(result => {
                if (result.html) {
                    const targetSection = isActiveOrder(newStatus) ? activeOrdersSection : orderHistorySection;
                    
                    // Add to the top of the list
                    if (targetSection.firstChild) {
                        targetSection.insertAdjacentHTML('afterbegin', result.html);
                    } else {
                        targetSection.innerHTML = result.html;
                    }
                    
                    // Replace empty message if it exists
                    const emptyMessage = targetSection.querySelector('.empty-orders-message');
                    if (emptyMessage) {
                        emptyMessage.closest('.col-12').remove();
                    }
                    
                    // Add the order to local history if it doesn't exist
                    if (!orderHistory.includes(orderId)) {
                        orderHistory.push(orderId);
                        localStorage.setItem('order_history', JSON.stringify(orderHistory));
                    }
                    
                    console.log(`New order card added to the ${isActiveOrder(newStatus) ? 'active' : 'history'} section`);
                }
            });
            return;
        }
        
        // If card exists and status changed, update it
        const currentStatus = existingCard.getAttribute('data-order-status');
        
        if (currentStatus !== newStatus) {
            console.log(`Status changed from ${currentStatus} to ${newStatus}`);
            
            // Update status in localStorage
            if (typeof window.updateOrderStatus === 'function') {
                window.updateOrderStatus(orderId, newStatus);
            }
            
            // Check if we need to move the card to a different section
            const isCurrentActive = isActiveOrder(currentStatus);
            const isNewActive = isActiveOrder(newStatus);
            
            if (isCurrentActive !== isNewActive) {
                // Remove card from current section
                existingCard.remove();
                
                // Re-render the order in the new section
                renderOrder(orderId).then(result => {
                    if (result.html) {
                        const targetSection = isNewActive ? activeOrdersSection : orderHistorySection;
                        
                        // Add to the top of the list
                        if (targetSection.firstChild) {
                            targetSection.insertAdjacentHTML('afterbegin', result.html);
                        } else {
                            targetSection.innerHTML = result.html;
                        }
                        
                        // Replace empty list message if it exists
                        const emptyMessage = targetSection.querySelector('.empty-orders-message');
                        if (emptyMessage) {
                            emptyMessage.closest('.col-12').remove();
                        }
                        
                        console.log(`Order card moved to the ${isNewActive ? 'active' : 'history'} section`);
                    }
                });
            } else {
                // Just update the status badge
                const statusBadge = existingCard.querySelector('.order-status');
                if (statusBadge) {
                    statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    existingCard.setAttribute('data-order-status', newStatus);
                    
                    // Update badge color based on status
                    statusBadge.className = 'badge order-status';
                    if (newStatus === 'preparing') {
                        statusBadge.classList.add('bg-warning', 'text-dark');
                    } else if (newStatus === 'ready') {
                        statusBadge.classList.add('bg-success', 'text-white');
                    } else if (newStatus === 'picked up') {
                        statusBadge.classList.add('bg-primary', 'text-white');
                    } else if (newStatus === 'cancelled') {
                        statusBadge.classList.add('bg-danger', 'text-white');
                    } else if (newStatus === 'archived') {
                        statusBadge.classList.add('bg-secondary', 'text-white');
                    }
                    
                    console.log(`Order status badge updated to ${newStatus}`);
                } else {
                    console.error(`Status badge not found for order ${orderId}`);
                }
            }
        }
    }
    
    // Helper function to check if a status is considered active
    function isActiveOrder(status) {
        return activeStatuses.includes(status) && !historyStatuses.includes(status);
    }

    // Register event listeners for order events
    addOrderEventListener('order_update', function(data) {
        if (data.order) {
            console.log('Received order_update event with data:', data.order);
            updateOrderCard(data.order);
            // Update the orders badge in the navbar
            if (typeof window.initializeOrdersBadge === 'function') {
                window.initializeOrdersBadge();
            }
        }
    });
    
    addOrderEventListener('new_orders', function(data) {
        if (data.orders && data.orders.length > 0) {
            console.log('Received new_orders event with data:', data.orders);
            data.orders.forEach(order => {
                updateOrderCard(order);
            });
            // Update the orders badge in the navbar
            if (typeof window.initializeOrdersBadge === 'function') {
                window.initializeOrdersBadge();
            }
        }
    });

    // Initialize the orders display
    displayOrders();
    
    // Set up a periodic refresh to ensure we always have the latest order status
    // This serves as a backup in case SSE fails
    setInterval(() => {
        if (orderHistory.length > 0) {
            // Refresh each order one at a time to avoid overwhelming the server
            orderHistory.forEach((orderId, index) => {
                // Stagger the requests
                setTimeout(() => {
                    console.log(`Periodic refresh for order ${orderId}`);
                    fetch(`api/get_order_details.php?order_id=${orderId}&_nocache=${Date.now()}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.order) {
                                updateOrderCard(data.order);
                            }
                        })
                        .catch(error => console.error(`Error refreshing order ${orderId}:`, error));
                }, index * 500); // Stagger by 500ms per order
            });
        }
    }, 15000); // Refresh every 15 seconds
})();
</script>
