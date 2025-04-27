<?php
// API endpoint to update order status
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get JSON input data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Validate input
if (!isset($input['order_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$orderId = intval($input['order_id']);
$status = $input['status'];

// Validate status value
$validStatuses = ['preparing', 'ready', 'picked up', 'cancelled', 'archived'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status value']);
    exit;
}

try {
    $mysqli = Database::getConnection();
    
    // Update order status
    $query = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('si', $status, $orderId);
    
    if ($stmt->execute()) {
        // Update pickup time if status is "picked up"
        if ($status === 'picked up') {
            $pickupQuery = "UPDATE orders SET pickup_time = NOW() WHERE order_id = ? AND pickup_time IS NULL";
            $pickupStmt = $mysqli->prepare($pickupQuery);
            $pickupStmt->bind_param('i', $orderId);
            $pickupStmt->execute();
        }
        
        // Trigger the event emission for real-time updates
        $eventUrl = '../../api/order_status_events.php?order_id=' . $orderId . '&status=' . urlencode($status);
        $absolutePath = realpath(__DIR__ . '/' . $eventUrl);
        
        if (file_exists($absolutePath)) {
            // Method 1: Include the file directly (synchronous)
            include_once $absolutePath;
        } else {
            // Method 2: Make an asynchronous HTTP request (if on the same server)
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $path = explode('/staff', $_SERVER['REQUEST_URI'])[0] ?? '';
            $eventEndpoint = "$protocol://$host$path/api/order_status_events.php?order_id=$orderId&status=" . urlencode($status);
            
            // Non-blocking request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $eventEndpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Short timeout - we don't care about the response
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