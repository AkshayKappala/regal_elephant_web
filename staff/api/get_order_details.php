<?php
// API endpoint to get detailed information for a specific order
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Validate input
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing order ID parameter']);
    exit;
}

$orderId = intval($_GET['id']);

try {
    $mysqli = Database::getConnection();
    
    // Get order details
    $orderQuery = "SELECT * FROM orders WHERE order_id = ?";
    $orderStmt = $mysqli->prepare($orderQuery);
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    
    if ($orderResult->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    $order = $orderResult->fetch_assoc();
    
    // Get order items
    $itemsQuery = "SELECT oi.*, fi.name, fi.category 
                  FROM order_items oi 
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
    
    // Prepare the response
    $response = [
        'success' => true,
        'order' => $order,
        'items' => $items
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error retrieving order data: ' . $e->getMessage()
    ]);
}
?>