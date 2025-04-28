let orderEventsSource = null;
const eventListeners = {
    'order_update': [],
    'new_orders': [],
    'orders_update': [],
    'connection': [],
    'ping': [],
    'error': []
};

const MAX_RECONNECT_ATTEMPTS = 5;
let reconnectAttempts = 0;
let reconnectTimeout = null;

export function initializeOrderEvents() {
    if (orderEventsSource) return;
    
    connectEventSource();
}

function connectEventSource() {
    try {
        orderEventsSource = new EventSource('api/order_events.php');
        
        orderEventsSource.addEventListener('connection', function(e) {
            reconnectAttempts = 0;
            triggerEventListeners('connection', JSON.parse(e.data));
        });
        
        orderEventsSource.addEventListener('new_orders', function(e) {
            const data = JSON.parse(e.data);
            if (data.orders && data.orders.length > 0) {
                triggerEventListeners('new_orders', data);
                
                if (typeof window.initializeOrdersBadge === 'function') {
                    window.initializeOrdersBadge();
                }
            }
        });
        
        orderEventsSource.addEventListener('order_update', function(e) {
            const data = JSON.parse(e.data);
            if (data.order) {
                triggerEventListeners('order_update', data);
                
                if (typeof window.initializeOrdersBadge === 'function') {
                    window.initializeOrdersBadge();
                }
            }
        });
        
        orderEventsSource.addEventListener('orders_update', function(e) {
            const data = JSON.parse(e.data);
            if (data.orders && data.orders.length > 0) {
                triggerEventListeners('orders_update', data);
                
                if (typeof window.initializeOrdersBadge === 'function') {
                    window.initializeOrdersBadge();
                }
            }
        });
        
        orderEventsSource.addEventListener('error', function(e) {
            triggerEventListeners('error', { event: e });
            
            orderEventsSource.close();
            orderEventsSource = null;
            
            if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
                
                clearTimeout(reconnectTimeout);
                reconnectTimeout = setTimeout(() => {
                    reconnectAttempts++;
                    connectEventSource();
                }, delay);
            } else {
                if (document.getElementById('connection-error-message')) {
                    document.getElementById('connection-error-message').style.display = 'block';
                }
            }
        });
        
        orderEventsSource.addEventListener('ping', function(e) {
            triggerEventListeners('ping', { timestamp: new Date().getTime() });
        });
        
        orderEventsSource.addEventListener('message', function(e) {
            try {
                const data = JSON.parse(e.data);
            } catch (error) {
            }
        });
        
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                if (!orderEventsSource || orderEventsSource.readyState === EventSource.CLOSED) {
                    reconnectAttempts = 0;
                    connectEventSource();
                }
            }
        });
        
        window.addEventListener('beforeunload', function() {
            if (orderEventsSource) {
                orderEventsSource.close();
                orderEventsSource = null;
            }
            clearTimeout(reconnectTimeout);
        });
        
    } catch (err) {
        if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
            const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
            
            clearTimeout(reconnectTimeout);
            reconnectTimeout = setTimeout(() => {
                reconnectAttempts++;
                connectEventSource();
            }, delay);
        }
    }
}

export function addOrderEventListener(eventType, callback) {
    if (!eventListeners[eventType]) {
        eventListeners[eventType] = [];
    }
    
    if (eventListeners[eventType].indexOf(callback) === -1) {
        eventListeners[eventType].push(callback);
    }
}

export function removeOrderEventListener(eventType, callback) {
    if (eventListeners[eventType]) {
        const index = eventListeners[eventType].indexOf(callback);
        if (index !== -1) {
            eventListeners[eventType].splice(index, 1);
        }
    }
}

function triggerEventListeners(eventType, data) {
    if (eventListeners[eventType] && eventListeners[eventType].length > 0) {
        eventListeners[eventType].forEach(callback => {
            try {
                callback(data);
            } catch (error) {
            }
        });
    }
}