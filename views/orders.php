<div class="container py-4">
    <h1 class="text-center mb-4">Your Orders</h1>
    
    <ul class="nav nav-tabs mb-4" id="ordersTab" role="tablist" style="border-bottom-color: #a04b25;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-orders" 
                    type="button" role="tab" aria-controls="active-orders" aria-selected="true"
                    style="color: #eadab0; background-color: #a04b25; border-color: #a04b25;">
                Active Orders
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past-orders" 
                    type="button" role="tab" aria-controls="past-orders" aria-selected="false"
                    style="color: #eadab0; border-color: #a04b25;">
                Past Orders
            </button>
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
    let activeOrderCount = 0;
    let evtSource = null;

    // Add event listener to style the tabs when clicked
    document.querySelectorAll('#ordersTab .nav-link').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('#ordersTab .nav-link').forEach(t => {
                if (t === this) {
                    t.style.backgroundColor = '#a04b25';
                    t.style.color = '#eadab0';
                } else {
                    t.style.backgroundColor = 'transparent';
                    t.style.color = '#eadab0';
                }
            });
        });
    });

    if (!orderHistory || orderHistory.length === 0) {
        activeOrderList.innerHTML = `<div class='col-12 text-center'><p class='mt-4 cart-empty-message'>You have no orders yet.</p></div>`;
        pastOrderList.innerHTML = `<div class='col-12 text-center'><p class='mt-4 cart-empty-message'>You have no past orders.</p></div>`;
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
                statusText = 'Picked Up';
            } else if (order.status === 'cancelled') {
                badgeClass = 'bg-danger';
            } else if (order.status === 'archived') {
                badgeClass = 'bg-info';
                statusText = 'Completed';
            }

            return `
                <div class='col-md-8 order-card' data-order-id="${order.order_id}">
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
                                    <p><strong>Status:</strong> <span class="badge ${badgeClass} order-status-badge">${statusText}</span></p>
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
    // IMPORTANT: Past orders include archived AND cancelled AND picked up
    const activeOrders = orderDetails.filter(order => 
        order.status !== 'archived' && order.status !== 'cancelled' && order.status !== 'picked up'
    );
    
    const pastOrders = orderDetails.filter(order => 
        order.status === 'archived' || order.status === 'cancelled' || order.status === 'picked up'
    );
    
    // Sort orders by placed time (newest first)
    activeOrders.sort((a, b) => new Date(b.order_placed_time) - new Date(a.order_placed_time));
    pastOrders.sort((a, b) => new Date(b.order_placed_time) - new Date(a.order_placed_time));
    
    // Update count of active orders
    activeOrderCount = activeOrders.length;
    
    // Add count to the navbar orders link if there are active orders
    if (activeOrderCount > 0) {
        const ordersNavLink = document.querySelector('a.nav-link[data-page="orders"]');
        if (ordersNavLink) {
            // Create or update the badge for orders
            let ordersBadge = document.getElementById('orders-count-badge');
            if (!ordersBadge) {
                ordersBadge = document.createElement('span');
                ordersBadge.id = 'orders-count-badge';
                ordersBadge.className = 'position-absolute top-0 start-50 translate-middle-x badge rounded-pill bg-danger';
                ordersBadge.innerHTML = activeOrderCount + '<span class="visually-hidden">active orders</span>';
                ordersNavLink.style.position = 'relative';
                ordersNavLink.appendChild(ordersBadge);
            } else {
                ordersBadge.textContent = activeOrderCount;
            }
        }
    }
    
    // Render active orders
    if (activeOrders.length === 0) {
        activeOrderList.innerHTML = `<div class='col-12 text-center'><p class='mt-4 cart-empty-message'>You have no active orders.</p></div>`;
    } else {
        const activeOrdersHtml = await Promise.all(activeOrders.map(order => renderOrder(order, activeOrderList)));
        activeOrderList.innerHTML = activeOrdersHtml.join('');
    }
    
    // Render past orders
    if (pastOrders.length === 0) {
        pastOrderList.innerHTML = `<div class='col-12 text-center'><p class='mt-4 cart-empty-message'>You have no past orders.</p></div>`;
    } else {
        const pastOrdersHtml = await Promise.all(pastOrders.map(order => renderOrder(order, pastOrderList)));
        pastOrderList.innerHTML = pastOrdersHtml.join('');
    }

    // Setup SSE connections for real-time order updates
    function setupSSEConnections() {
        // Only setup SSE if browser supports it and if we have active orders
        if (typeof EventSource === 'undefined' || activeOrders.length === 0) {
            return;
        }

        // Close any existing connections
        if (evtSource) {
            evtSource.close();
            evtSource = null;
        }

        // Setup SSE connection for each active order
        activeOrders.forEach(order => {
            const orderId = order.order_id;
            const sseUrl = `api/order_events.php?order_id=${orderId}`;
            
            const eventSource = new EventSource(sseUrl);
            
            eventSource.addEventListener('order_update', function(event) {
                const data = JSON.parse(event.data);
                if (data.order && data.order.order_id === orderId) {
                    console.log('Order updated via SSE:', data.order);
                    updateOrderCard(data.order);
                }
            });
            
            eventSource.addEventListener('connection', function(event) {
                console.log(`SSE connection established for order ${orderId}`);
            });
            
            eventSource.onerror = function(error) {
                console.error(`SSE error for order ${orderId}:`, error);
                eventSource.close();
                
                // Reconnect after a delay
                setTimeout(() => {
                    setupSSEConnections();
                }, 5000);
            };
            
            // Store the event source in the global variable for the most recent order
            if (!evtSource) {
                evtSource = eventSource;
            }
        });
    }
    
    // Update an order card with new data
    function updateOrderCard(order) {
        const orderCard = document.querySelector(`.order-card[data-order-id="${order.order_id}"]`);
        if (!orderCard) return;
        
        // Update status badge
        const statusBadge = orderCard.querySelector('.order-status-badge');
        if (statusBadge) {
            // Store current status before updating to check if it changed
            const currentStatus = statusBadge.textContent.toLowerCase();
            
            let badgeClass = 'bg-warning text-dark';
            let statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
            
            if (order.status === 'ready') {
                badgeClass = 'bg-success';
                // Only notify if status changed from a different status to 'ready'
                if (currentStatus !== 'ready') {
                    // Play notification sound for ready orders
                    playNotificationSound();
                    // Show notification
                    showNotification(`Your order #${order.order_number} is ready for pickup!`);
                }
            } else if (order.status === 'picked up') {
                badgeClass = 'bg-secondary';
                statusText = 'Picked Up';
            } else if (order.status === 'cancelled') {
                badgeClass = 'bg-danger';
            } else if (order.status === 'archived') {
                badgeClass = 'bg-info';
                statusText = 'Completed';
            }
            
            // Remove all classes and add the new ones
            statusBadge.className = `badge ${badgeClass} order-status-badge`;
            statusBadge.textContent = statusText;
            
            // Check if order has moved from active to past
            if (order.status === 'cancelled' || order.status === 'picked up' || order.status === 'archived') {
                // Move the order card to past orders
                const orderCardHTML = orderCard.parentElement.outerHTML;
                orderCard.parentElement.remove();
                
                // If past orders is empty, clear it first
                if (pastOrderList.querySelector('.cart-empty-message')) {
                    pastOrderList.innerHTML = '';
                }
                
                pastOrderList.insertAdjacentHTML('afterbegin', orderCardHTML);
                
                // Update active order count
                activeOrderCount--;
                
                // Update badge
                const ordersBadge = document.getElementById('orders-count-badge');
                if (ordersBadge) {
                    if (activeOrderCount > 0) {
                        ordersBadge.textContent = activeOrderCount;
                    } else {
                        ordersBadge.style.display = 'none';
                        // Show empty message if no more active orders
                        if (activeOrderList.children.length === 0) {
                            activeOrderList.innerHTML = `<div class='col-12 text-center'><p class='mt-4 cart-empty-message'>You have no active orders.</p></div>`;
                        }
                    }
                }
            }
        }
    }
    
    // Function to play notification sound
    function playNotificationSound() {
        try {
            const audio = new Audio('assets/sounds/notification.mp3');
            audio.play();
        } catch (e) {
            console.error('Failed to play notification sound:', e);
        }
    }
    
    // Function to show a notification
    function showNotification(message) {
        if (!('Notification' in window)) {
            console.log('This browser does not support notifications');
            return;
        }
        
        if (Notification.permission === 'granted') {
            new Notification('Regal Elephant', {
                body: message,
                icon: 'assets/images/logo.png'
            });
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification('Regal Elephant', {
                        body: message,
                        icon: 'assets/images/logo.png'
                    });
                }
            });
        }
        
        // Also show an in-page notification
        const notification = document.createElement('div');
        notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.maxWidth = '300px';
        notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
    
    // Set up SSE connections after rendering
    setupSSEConnections();
    
    // Set up periodic polling fallback every 30 seconds
    setInterval(async function() {
        for (const orderId of orderHistory) {
            try {
                const response = await fetch(`api/get_order_details.php?order_id=${orderId}`);
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        // Check if order is active
                        const isCurrentlyActive = data.order.status !== 'archived' && 
                                                 data.order.status !== 'cancelled' && 
                                                 data.order.status !== 'picked up';
                        
                        // Find current order to compare with
                        const existingActiveOrder = activeOrders.find(o => o.order_id === data.order.order_id);
                        
                        // Only update if status has changed
                        if (existingActiveOrder && existingActiveOrder.status !== data.order.status) {
                            console.log('Order status changed via polling:', data.order);
                            updateOrderCard(data.order);
                        }
                    }
                }
            } catch (error) {
                console.error(`Error polling order ${orderId}:`, error);
            }
        }
    }, 30000);
})();
</script>
