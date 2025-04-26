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