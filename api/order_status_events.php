<?php
// filepath: c:\xampp\htdocs\INFS730\regal_elephant_web\api\order_status_events.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Function to emit an order event to a log file that the SSE endpoint can read
function emitOrderEvent($orderId, $status, $eventType = 'status_change') {
    $eventData = [
        'order_id' => $orderId,
        'status' => $status,
        'event_type' => $eventType,
        'timestamp' => time()
    ];
    
    // Store the event in the events table
    try {
        $mysqli = Database::getConnection();
        $query = "INSERT INTO order_events (order_id, event_type, event_data) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $eventJson = json_encode($eventData);
        $stmt->bind_param('iss', $orderId, $eventType, $eventJson);
        $stmt->execute();
    } catch (Exception $e) {
        // If table doesn't exist yet, create it
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            try {
                $createTable = "CREATE TABLE order_events (
                    event_id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    event_type VARCHAR(50) NOT NULL,
                    event_data TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (order_id),
                    INDEX (event_type)
                )";
                $mysqli->query($createTable);
                
                // Try again after creating the table
                $query = "INSERT INTO order_events (order_id, event_type, event_data) VALUES (?, ?, ?)";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('iss', $orderId, $eventType, $eventJson);
                $stmt->execute();
            } catch (Exception $innerEx) {
                // Failed to create table or insert, fall back to file-based approach
                error_log("Database error: " . $innerEx->getMessage());
            }
        }
    }
    
    // Also write to a file as a fallback mechanism
    $eventsDir = __DIR__ . '/../temp/events';
    if (!is_dir($eventsDir)) {
        mkdir($eventsDir, 0755, true);
    }
    
    $eventFile = $eventsDir . '/order_events.json';
    $events = [];
    
    if (file_exists($eventFile)) {
        $content = file_get_contents($eventFile);
        if (!empty($content)) {
            $events = json_decode($content, true) ?: [];
        }
    }
    
    // Add new event
    $events[] = $eventData;
    
    // Keep only last 100 events
    if (count($events) > 100) {
        $events = array_slice($events, -100);
    }
    
    file_put_contents($eventFile, json_encode($events));
    
    return $eventData;
}

// Get the order ID and status from request
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$eventType = isset($_GET['event_type']) ? $_GET['event_type'] : 'status_change';

if (!$orderId || !$status) {
    echo json_encode(['success' => false, 'error' => 'Missing order_id or status']);
    exit;
}

$event = emitOrderEvent($orderId, $status, $eventType);
echo json_encode(['success' => true, 'event' => $event]);