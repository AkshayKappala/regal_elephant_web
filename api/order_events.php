<?php
// Server-Sent Events endpoint for real-time order updates
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering for Nginx

// Prevent PHP from buffering the output
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}

// Turn off output buffering
if (ob_get_level()) ob_end_clean();

// Send padding for IE
echo str_repeat(' ', 2048);
echo PHP_EOL;

// Flush headers and send initial message
flush();

// Check for client disconnect
if (connection_aborted()) {
    exit();
}

require_once __DIR__ . '/../config/database.php';
$mysqli = Database::getConnection();

// Get last event ID if available
$lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? 
    intval($_SERVER['HTTP_LAST_EVENT_ID']) : 
    (isset($_GET['last_event_id']) ? intval($_GET['last_event_id']) : 0);

// Get order ID if available (for customer-specific events)
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Check if this is a staff or customer connection
$isStaff = isset($_GET['client']) && $_GET['client'] === 'staff';

// Function to send SSE data
function sendSSE($id, $event, $data) {
    echo "id: $id" . PHP_EOL;
    echo "event: $event" . PHP_EOL;
    echo "data: " . json_encode($data) . PHP_EOL;
    echo PHP_EOL;
    flush();
}

// Send initial connection established event
sendSSE(time(), 'connection', ['status' => 'connected']);

// Keep connection alive with a main loop
$lastCheck = time();
$reconnectTime = 30; // seconds
$keepAliveInterval = 15; // seconds
$lastEventCheckTime = 0;

while (true) {
    // Check for client disconnect
    if (connection_aborted()) {
        break;
    }

    $currentTime = time();
    
    // Send a keep-alive message every 15 seconds
    if ($currentTime - $lastCheck >= $keepAliveInterval) {
        sendSSE($currentTime, 'ping', ['time' => $currentTime]);
        $lastCheck = $currentTime;
    }
    
    // Check for new events every 2 seconds
    if ($currentTime - $lastEventCheckTime >= 2) {
        $lastEventCheckTime = $currentTime;
        
        // For staff: get any new orders or status changes
        if ($isStaff) {
            // First check for new or recently updated orders
            $query = "SELECT o.*, 
                     (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
                     FROM orders o 
                     WHERE o.status != 'archived'
                     ORDER BY o.order_placed_time DESC LIMIT 10";
            $result = $mysqli->query($query);
            
            if ($result && $result->num_rows > 0) {
                $orders = [];
                while ($order = $result->fetch_assoc()) {
                    $orders[] = $order;
                }
                
                if (!empty($orders)) {
                    sendSSE(time(), 'orders_update', ['orders' => $orders]);
                }
            }
            
            // Then check for recent events
            $eventQuery = "SELECT * FROM order_events 
                         WHERE event_id > ? 
                         ORDER BY event_id DESC LIMIT 20";
            $stmt = $mysqli->prepare($eventQuery);
            $stmt->bind_param('i', $lastEventId);
            $stmt->execute();
            $eventResult = $stmt->get_result();
            
            if ($eventResult && $eventResult->num_rows > 0) {
                $events = [];
                $maxId = $lastEventId;
                
                while ($event = $eventResult->fetch_assoc()) {
                    $events[] = $event;
                    if ($event['event_id'] > $maxId) {
                        $maxId = $event['event_id'];
                    }
                }
                
                if (!empty($events)) {
                    sendSSE($maxId, 'events_update', ['events' => $events]);
                    $lastEventId = $maxId;
                }
            }
        }
        // For customer: get updates on specific order(s)
        else if ($orderId > 0) {
            // Get the current order status
            $query = "SELECT * FROM orders WHERE order_id = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $order = $result->fetch_assoc();
                
                // Get order items
                $itemsQuery = "SELECT oi.*, fi.name FROM order_items oi 
                              JOIN food_items fi ON oi.item_id = fi.item_id 
                              WHERE oi.order_id = ?";
                $itemsStmt = $mysqli->prepare($itemsQuery);
                $itemsStmt->bind_param('i', $orderId);
                $itemsStmt->execute();
                $itemsResult = $itemsStmt->get_result();
                
                $items = [];
                while ($item = $itemsResult->fetch_assoc()) {
                    $items[] = $item;
                }
                
                $order['items'] = $items;
                sendSSE($orderId, 'order_update', ['order' => $order]);
            }
        }
        // For customer orders page without specific order ID: listen for new orders
        else {
            // Use lastEventId to track the last event seen
            $eventQuery = "SELECT * FROM order_events 
                         WHERE event_id > ? AND event_type = 'new_order'
                         ORDER BY event_id DESC LIMIT 10";
            $stmt = $mysqli->prepare($eventQuery);
            $stmt->bind_param('i', $lastEventId);
            $stmt->execute();
            $eventResult = $stmt->get_result();
            
            if ($eventResult && $eventResult->num_rows > 0) {
                $newOrders = [];
                $maxId = $lastEventId;
                
                while ($event = $eventResult->fetch_assoc()) {
                    $eventData = json_decode($event['event_data'], true);
                    $newOrderId = $eventData['order_id'];
                    
                    // Get the full order details
                    $orderQuery = "SELECT * FROM orders WHERE order_id = ?";
                    $orderStmt = $mysqli->prepare($orderQuery);
                    $orderStmt->bind_param('i', $newOrderId);
                    $orderStmt->execute();
                    $orderResult = $orderStmt->get_result();
                    
                    if ($orderResult && $orderResult->num_rows > 0) {
                        $order = $orderResult->fetch_assoc();
                        
                        // Get order items
                        $itemsQuery = "SELECT oi.*, fi.name FROM order_items oi 
                                      JOIN food_items fi ON oi.item_id = fi.item_id 
                                      WHERE oi.order_id = ?";
                        $itemsStmt = $mysqli->prepare($itemsQuery);
                        $itemsStmt->bind_param('i', $newOrderId);
                        $itemsStmt->execute();
                        $itemsResult = $itemsStmt->get_result();
                        
                        $items = [];
                        while ($item = $itemsResult->fetch_assoc()) {
                            $items[] = $item;
                        }
                        
                        $order['items'] = $items;
                        $newOrders[] = $order;
                    }
                    
                    if ($event['event_id'] > $maxId) {
                        $maxId = $event['event_id'];
                    }
                }
                
                if (!empty($newOrders)) {
                    sendSSE($maxId, 'new_orders', ['orders' => $newOrders]);
                    $lastEventId = $maxId;
                }
            }
        }
    }
    
    // Sleep to prevent CPU overuse
    usleep(500000); // 0.5 seconds
}