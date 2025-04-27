/**
 * Events related functionality for real-time order updates
 */

let orderEventsSource = null;
const eventListeners = {
    'order_update': [],
    'new_orders': [],
    'orders_update': [],
    'connection': [],
    'ping': [],
    'error': []
};

// Configuration for reconnection
const MAX_RECONNECT_ATTEMPTS = 5;
let reconnectAttempts = 0;
let reconnectTimeout = null;

/**
 * Initialize global event listener for order events
 * This will ensure we listen to order updates site-wide
 */
export function initializeOrderEvents() {
    // Only initialize once
    if (orderEventsSource) return;
    
    connectEventSource();
}

/**
 * Connect to the EventSource and set up all event listeners
 */
function connectEventSource() {
    try {
        console.log('Attempting to establish SSE connection for order events...');
        orderEventsSource = new EventSource('api/order_events.php');
        
        // Connection established
        orderEventsSource.addEventListener('connection', function(e) {
            console.log('SSE connection established for order events');
            reconnectAttempts = 0; // Reset reconnect attempts on successful connection
            triggerEventListeners('connection', JSON.parse(e.data));
        });
        
        // New orders event
        orderEventsSource.addEventListener('new_orders', function(e) {
            console.log('Received new_orders event:', e.data);
            const data = JSON.parse(e.data);
            if (data.orders && data.orders.length > 0) {
                triggerEventListeners('new_orders', data);
                
                // Update orders badge when a new order arrives
                if (typeof window.initializeOrdersBadge === 'function') {
                    console.log('Updating orders badge due to new orders');
                    window.initializeOrdersBadge();
                }
            }
        });
        
        // Order status update event
        orderEventsSource.addEventListener('order_update', function(e) {
            console.log('Received order_update event:', e.data);
            const data = JSON.parse(e.data);
            if (data.order) {
                triggerEventListeners('order_update', data);
                
                // Update orders badge when order status changes
                if (typeof window.initializeOrdersBadge === 'function') {
                    console.log('Updating orders badge due to order status update');
                    window.initializeOrdersBadge();
                }
            }
        });
        
        // Orders list update event
        orderEventsSource.addEventListener('orders_update', function(e) {
            console.log('Received orders_update event:', e.data);
            const data = JSON.parse(e.data);
            if (data.orders && data.orders.length > 0) {
                triggerEventListeners('orders_update', data);
                
                // Update orders badge when orders list is updated
                if (typeof window.initializeOrdersBadge === 'function') {
                    console.log('Updating orders badge due to orders list update');
                    window.initializeOrdersBadge();
                }
            }
        });
        
        // Add error handler
        orderEventsSource.addEventListener('error', function(e) {
            console.error('SSE connection error:', e);
            triggerEventListeners('error', { event: e });
            
            // Close current connection
            orderEventsSource.close();
            orderEventsSource = null;
            
            // Attempt to reconnect with exponential backoff
            if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000); // Exponential backoff, max 30 seconds
                console.log(`Attempting to reconnect in ${delay/1000} seconds... (Attempt ${reconnectAttempts + 1}/${MAX_RECONNECT_ATTEMPTS})`);
                
                clearTimeout(reconnectTimeout);
                reconnectTimeout = setTimeout(() => {
                    reconnectAttempts++;
                    connectEventSource();
                }, delay);
            } else {
                console.error('Maximum reconnection attempts reached. Please refresh the page to restore updates.');
                // Optionally display a message to the user that they need to refresh the page
                if (document.getElementById('connection-error-message')) {
                    document.getElementById('connection-error-message').style.display = 'block';
                }
            }
        });
        
        // Handle ping events to keep connection alive
        orderEventsSource.addEventListener('ping', function(e) {
            console.log('Ping received, connection active');
            triggerEventListeners('ping', { timestamp: new Date().getTime() });
        });
        
        // Default message event (fallback)
        orderEventsSource.addEventListener('message', function(e) {
            console.log('Received generic message event:', e.data);
            try {
                const data = JSON.parse(e.data);
                // Handle generic messages if needed
            } catch (error) {
                console.error('Error parsing message event data:', error);
            }
        });
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // Reconnect if the connection was closed when the page was hidden
                if (!orderEventsSource || orderEventsSource.readyState === EventSource.CLOSED) {
                    console.log('Page became visible, reconnecting SSE...');
                    reconnectAttempts = 0; // Reset reconnect attempts
                    connectEventSource();
                }
            }
        });
        
        // Clean up on page unload
        window.addEventListener('beforeunload', function() {
            if (orderEventsSource) {
                orderEventsSource.close();
                orderEventsSource = null;
            }
            clearTimeout(reconnectTimeout);
        });
        
    } catch (err) {
        console.error('Failed to initialize SSE connection:', err);
        // Attempt to reconnect if initialization fails
        if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
            const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
            console.log(`Initialization failed. Attempting to reconnect in ${delay/1000} seconds...`);
            
            clearTimeout(reconnectTimeout);
            reconnectTimeout = setTimeout(() => {
                reconnectAttempts++;
                connectEventSource();
            }, delay);
        }
    }
}

/**
 * Add an event listener for order events
 * @param {string} eventType - The event type to listen for
 * @param {Function} callback - The callback function to call when the event occurs
 */
export function addOrderEventListener(eventType, callback) {
    if (!eventListeners[eventType]) {
        eventListeners[eventType] = [];
    }
    
    // Check if callback is already registered to prevent duplicates
    if (eventListeners[eventType].indexOf(callback) === -1) {
        console.log(`Adding listener for '${eventType}' event`);
        eventListeners[eventType].push(callback);
    }
}

/**
 * Remove an event listener
 * @param {string} eventType - The event type to remove the listener from
 * @param {Function} callback - The callback function to remove
 */
export function removeOrderEventListener(eventType, callback) {
    if (eventListeners[eventType]) {
        const index = eventListeners[eventType].indexOf(callback);
        if (index !== -1) {
            console.log(`Removing listener for '${eventType}' event`);
            eventListeners[eventType].splice(index, 1);
        }
    }
}

/**
 * Trigger all registered event listeners for a specific event type
 * @param {string} eventType - The event type to trigger
 * @param {Object} data - The event data
 */
function triggerEventListeners(eventType, data) {
    if (eventListeners[eventType] && eventListeners[eventType].length > 0) {
        console.log(`Triggering ${eventListeners[eventType].length} listeners for '${eventType}' event`);
        eventListeners[eventType].forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error(`Error in ${eventType} event listener:`, error);
            }
        });
    } else {
        console.log(`No listeners registered for '${eventType}' event`);
    }
}