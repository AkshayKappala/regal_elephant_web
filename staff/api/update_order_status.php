<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['order_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$orderId = intval($input['order_id']);
$status = $input['status'];

$validStatuses = ['preparing', 'ready', 'picked up', 'cancelled', 'archived'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status value']);
    exit;
}

$statusMapping = [
    'preparing' => 'in_progress',
    'ready' => 'ready',
    'picked up' => 'completed',
    'cancelled' => 'cancelled',
    'archived' => 'archived'
];

$eventStatus = isset($statusMapping[$status]) ? $statusMapping[$status] : $status;

try {
    $mysqli = Database::getConnection();
    
    $query = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('si', $status, $orderId);
    
    if ($stmt->execute()) {
        if ($status === 'picked up') {
            $pickupQuery = "UPDATE orders SET pickup_time = NOW() WHERE order_id = ? AND pickup_time IS NULL";
            $pickupStmt = $mysqli->prepare($pickupQuery);
            $pickupStmt->bind_param('i', $orderId);
            $pickupStmt->execute();
        }
        
        $eventFilePath = __DIR__ . '/../../api/order_status_events.php';
        
        if (file_exists($eventFilePath)) {
            $_GET['order_id'] = $orderId;
            $_GET['status'] = $eventStatus;
            include_once $eventFilePath;
        } else {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $path = explode('/staff', $_SERVER['REQUEST_URI'])[0] ?? '';
            $eventEndpoint = "$protocol://$host$path/api/order_status_events.php?order_id=$orderId&status=" . urlencode($eventStatus);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $eventEndpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_exec($ch);
            curl_close($ch);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update order status'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error updating order status: ' . $e->getMessage()
    ]);
}
?>