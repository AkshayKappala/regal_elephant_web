<?php
// filepath: c:\xampp\htdocs\INFS730\regal_elephant_web\api\order_events.php
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
    
    // For staff portal (get all recent order updates)
    if (!$orderId) {
        $query = "SELECT o.*, 
                 (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
                 FROM orders o 
                 WHERE o.order_id > ? 
                 ORDER BY o.order_id DESC LIMIT 10";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $lastEventId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $orders = [];
            $maxId = $lastEventId;
            
            while ($order = $result->fetch_assoc()) {
                $orders[] = $order;
                if ($order['order_id'] > $maxId) {
                    $maxId = $order['order_id'];
                }
            }
            
            if (!empty($orders)) {
                sendSSE($maxId, 'orders_update', ['orders' => $orders]);
                $lastEventId = $maxId;
            }
        }
    }
    // For customer portal (get specific order updates)
    else {
        $query = "SELECT * FROM orders WHERE order_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $order = $result->fetch_assoc();
            
            // Get order items as well
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
    
    // Sleep for a short time to prevent CPU overuse
    sleep(2);
}