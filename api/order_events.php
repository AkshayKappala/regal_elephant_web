<?php
// Server-Sent Events endpoint for real-time order updates
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests if needed
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

// Get last event ID if available
$lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? 
    intval($_SERVER['HTTP_LAST_EVENT_ID']) : 
    (isset($_GET['last_event_id']) ? intval($_GET['last_event_id']) : 0);

// Get order ID if available (for customer-specific events)
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Check if this is a staff or customer connection
$isStaff = isset($_GET['client']) && $_GET['client'] === 'staff';

// Events file path
$eventsFile = __DIR__ . '/../temp/events/order_events.json';

// Order details cache file path
$orderDetailsDir = __DIR__ . '/../temp/events/orders/';
if (!is_dir($orderDetailsDir)) {
    mkdir($orderDetailsDir, 0755, true);
}

// Function to send SSE data
function sendSSE($id, $event, $data) {
    echo "id: $id" . PHP_EOL;
    echo "event: $event" . PHP_EOL;
    echo "data: " . json_encode($data) . PHP_EOL;
    echo PHP_EOL;
    ob_flush();
    flush();
}

// Send initial connection established event
sendSSE(time(), 'connection', ['status' => 'connected']);

// Keep connection alive with a main loop
$lastCheck = time();
$reconnectTime = 30; // seconds
$keepAliveInterval = 15; // seconds
$lastEventCheckTime = 0;
$processedEventIds = [];

// Initialize event tracking
$lastStatusCheck = [];

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
    
    // Check for new events every 1 second
    if ($currentTime - $lastEventCheckTime >= 1) {
        $lastEventCheckTime = $currentTime;
        
        // Read events from file
        if (file_exists($eventsFile)) {
            $events = [];
            $fileContent = file_get_contents($eventsFile);
            if (!empty($fileContent)) {
                $events = json_decode($fileContent, true) ?: [];
            }
            
            if (!empty($events)) {
                // Filter events to only get new ones (with event_id > lastEventId)
                $newEvents = array_filter($events, function($event) use ($lastEventId, $processedEventIds) {
                    return isset($event['event_id']) && $event['event_id'] > $lastEventId && !in_array($event['event_id'], $processedEventIds);
                });
                
                if (!empty($newEvents)) {
                    // Track max event ID
                    $maxId = $lastEventId;
                    
                    // Process all new events
                    foreach ($newEvents as $event) {
                        // Mark event as processed to avoid duplicates
                        if (isset($event['event_id'])) {
                            $processedEventIds[] = $event['event_id'];
                            
                            // Keep processedEventIds manageable
                            if (count($processedEventIds) > 100) {
                                $processedEventIds = array_slice($processedEventIds, -100);
                            }
                            
                            if ($event['event_id'] > $maxId) {
                                $maxId = $event['event_id'];
                            }
                        }
                        
                        // Process based on event type and client type
                        if (isset($event['event_type']) && $event['event_type'] === 'status_change' && isset($event['order_id'])) {
                            $updatedOrderId = $event['order_id'];
                            
                            // Only send update if it's the specific order being watched or staff is viewing all orders
                            if ($isStaff || $orderId === 0 || $orderId === $updatedOrderId) {
                                // Get the order details from file cache
                                $orderDetailFile = $orderDetailsDir . 'order_' . $updatedOrderId . '.json';
                                
                                // If order detail file exists, use it instead of database
                                if (file_exists($orderDetailFile)) {
                                    $orderContent = file_get_contents($orderDetailFile);
                                    if (!empty($orderContent)) {
                                        $order = json_decode($orderContent, true);
                                        
                                        // Send order update event with data from file
                                        sendSSE($updatedOrderId . '_' . $currentTime, 'order_update', ['order' => $order]);
                                    }
                                } else {
                                    // Just send the basic event information if full details aren't available
                                    sendSSE($updatedOrderId . '_' . $currentTime, 'order_update', [
                                        'order' => [
                                            'order_id' => $updatedOrderId,
                                            'status' => $event['status'] ?? 'unknown',
                                            'timestamp' => $event['timestamp'] ?? $currentTime
                                        ]
                                    ]);
                                }
                            }
                        }
                    }
                    
                    // Update lastEventId
                    if ($maxId > $lastEventId) {
                        $lastEventId = $maxId;
                    }
                }
            }
        }
        
        // For customer: If watching a specific order, send updates regularly from cache
        if (!$isStaff && $orderId > 0) {
            // Get the order details from file cache instead of database
            $orderDetailFile = $orderDetailsDir . 'order_' . $orderId . '.json';
            
            if (file_exists($orderDetailFile)) {
                $orderContent = file_get_contents($orderDetailFile);
                if (!empty($orderContent)) {
                    $order = json_decode($orderContent, true);
                    
                    // Check if we should send an update
                    $shouldUpdate = false;
                    
                    // Send updates when the order is new or its status has changed
                    if (!isset($lastStatusCheck[$orderId])) {
                        $shouldUpdate = true;  // New order we haven't seen
                        $lastStatusCheck[$orderId] = [
                            'status' => $order['status'] ?? 'unknown',
                            'time' => $currentTime
                        ];
                    } 
                    // Check if status has changed or if enough time has passed (every 5 seconds)
                    else if (
                        (!isset($lastStatusCheck[$orderId]['status']) || 
                        $lastStatusCheck[$orderId]['status'] !== ($order['status'] ?? 'unknown')) || 
                        ($currentTime - $lastStatusCheck[$orderId]['time']) > 5
                    ) {
                        $shouldUpdate = true;
                        $lastStatusCheck[$orderId] = [
                            'status' => $order['status'] ?? 'unknown',
                            'time' => $currentTime
                        ];
                    }
                    
                    if ($shouldUpdate) {
                        // Send order update event with data from file
                        sendSSE($orderId . '_' . $currentTime, 'order_update', ['order' => $order]);
                    }
                }
            }
        }
    }
    
    // Sleep to avoid CPU hogging
    usleep(200000); // 0.2 seconds
}