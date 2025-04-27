<?php
// API endpoint to get a paginated list of orders
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Simple pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(5, intval($_GET['limit']))) : 20;

try {
    $mysqli = Database::getConnection();
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM orders";
    $totalResult = $mysqli->query($countQuery)->fetch_assoc();
    $total = $totalResult['total'];
    $totalPages = ceil($total / $limit);
    
    // Get orders with pagination - no filtering
    $query = "SELECT o.*, 
             (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
             FROM orders o 
             ORDER BY o.order_placed_time DESC 
             LIMIT ? OFFSET ?";
    
    $stmt = $mysqli->prepare($query);
    $offset = ($page - 1) * $limit;
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Construct response
    $response = [
        'success' => true,
        'orders' => $orders,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error retrieving orders: ' . $e->getMessage()
    ]);
}
?>