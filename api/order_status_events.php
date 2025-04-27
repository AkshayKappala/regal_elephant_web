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
    
    // Store the event directly in the file system
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
    
    // Add new event with a unique ID
    $eventData['event_id'] = count($events) > 0 ? max(array_column($events, 'event_id')) + 1 : 1;
    $events[] = $eventData;
    
    // Keep only last 100 events
    if (count($events) > 100) {
        $events = array_slice($events, -100);
    }
    
    // Write events to file
    file_put_contents($eventFile, json_encode($events));
    
    // Create order detail cache file
    createOrderDetailCache($orderId, $status);
    
    return $eventData;
}

// Function to create/update order detail cache file
function createOrderDetailCache($orderId, $newStatus) {
    $orderCacheDir = __DIR__ . '/../temp/events/orders/';
    if (!is_dir($orderCacheDir)) {
        mkdir($orderCacheDir, 0755, true);
    }
    
    $orderCacheFile = $orderCacheDir . 'order_' . $orderId . '.json';
    
    try {
        // Get order details from database
        $mysqli = Database::getConnection();
        
        $query = "SELECT o.*, 
                    GROUP_CONCAT(oi.item_id, ':', oi.quantity, ':', oi.price_each SEPARATOR '|') as items,
                    c.name as customer_name, c.email as customer_email 
                FROM orders o 
                LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                WHERE o.order_id = ? 
                GROUP BY o.order_id";
                
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Make sure status is updated to the latest one
            $row['status'] = $newStatus;
            
            // Format items as array
            $items = [];
            if (!empty($row['items'])) {
                $itemsData = explode('|', $row['items']);
                foreach ($itemsData as $item) {
                    $itemParts = explode(':', $item);
                    if (count($itemParts) >= 3) {
                        // Get the item name from the database
                        $itemQuery = "SELECT name FROM menu_items WHERE item_id = ?";
                        $itemStmt = $mysqli->prepare($itemQuery);
                        $itemStmt->bind_param('i', $itemParts[0]);
                        $itemStmt->execute();
                        $itemResult = $itemStmt->get_result();
                        $itemName = ($itemRow = $itemResult->fetch_assoc()) ? $itemRow['name'] : 'Unknown Item';
                        
                        $items[] = [
                            'item_id' => $itemParts[0],
                            'name' => $itemName,
                            'quantity' => $itemParts[1],
                            'price_each' => $itemParts[2],
                            'subtotal' => floatval($itemParts[1]) * floatval($itemParts[2])
                        ];
                        
                        $itemStmt->close();
                    }
                }
            }
            $row['items'] = $items;
            
            // Calculate total
            $total = 0;
            foreach ($items as $item) {
                $total += $item['subtotal'];
            }
            $row['total'] = $total;
            
            // Format date
            if (isset($row['created_at'])) {
                $row['formatted_date'] = date('M j, Y g:i A', strtotime($row['created_at']));
            }
            
            // Add status text for display
            $statusMap = [
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                'ready' => 'Ready for Pickup',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled'
            ];
            $row['status_text'] = $statusMap[$newStatus] ?? ucfirst($newStatus);
            
            // Write to cache file
            file_put_contents($orderCacheFile, json_encode($row));
            
            // Debug log
            error_log("Order cache updated for order #$orderId with status $newStatus");
        } else {
            error_log("Error: Order #$orderId not found in database");
            // Create a minimal cache file if we can't find the order
            $minimalData = [
                'order_id' => $orderId,
                'status' => $newStatus,
                'status_text' => ucfirst($newStatus),
                'timestamp' => time(),
                'items' => []
            ];
            file_put_contents($orderCacheFile, json_encode($minimalData));
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error creating order cache file: " . $e->getMessage());
        // Create a minimal cache file in case of database errors
        $minimalData = [
            'order_id' => $orderId,
            'status' => $newStatus,
            'status_text' => ucfirst($newStatus),
            'timestamp' => time(),
            'error' => 'Failed to load order details'
        ];
        file_put_contents($orderCacheFile, json_encode($minimalData));
    }
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