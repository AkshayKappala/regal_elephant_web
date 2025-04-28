<?php
header('Content-Type: application/json');
// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing order ID parameter']);
    exit;
}

$orderId = intval($_GET['id']);

try {
    $mysqli = Database::getConnection();
    
    $orderQuery = "SELECT * FROM orders WHERE order_id = ?";
    $orderStmt = $mysqli->prepare($orderQuery);
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    
    if ($orderResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    $order = $orderResult->fetch_assoc();
    
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
    
    $response = [
        'success' => true,
        'order' => $order,
        'items' => $items
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error retrieving order data: ' . $e->getMessage()
    ]);
}
?>