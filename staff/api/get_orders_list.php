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

// Status filter
$status = isset($_GET['status']) ? $_GET['status'] : '';
$includeArchived = isset($_GET['include_archived']) ? (bool)$_GET['include_archived'] : true;

try {
    $mysqli = Database::getConnection();
    
    // Build the WHERE clause based on filters
    $whereClause = [];
    $params = [];
    $types = '';
    
    // Apply status filter if provided and not 'all'
    if (!empty($status) && $status !== 'all') {
        if ($status === 'active') {
            $whereClause[] = "o.status != 'archived'";
        } else {
            $whereClause[] = "o.status = ?";
            $params[] = $status;
            $types .= 's';
        }
    } else if (!$includeArchived) {
        $whereClause[] = "o.status != 'archived'";
    }
    
    // Construct WHERE clause string
    $whereString = !empty($whereClause) ? " WHERE " . implode(" AND ", $whereClause) : "";
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM orders o" . $whereString;
    
    if (!empty($params)) {
        $countStmt = $mysqli->prepare($countQuery);
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $totalResult = $countStmt->get_result()->fetch_assoc();
    } else {
        $totalResult = $mysqli->query($countQuery)->fetch_assoc();
    }
    
    $total = $totalResult['total'];
    $totalPages = ceil($total / $limit);
    
    // Get orders with pagination
    $query = "SELECT o.*, 
             (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) as item_count 
             FROM orders o 
             $whereString
             ORDER BY o.order_placed_time DESC 
             LIMIT ? OFFSET ?";
    
    $stmt = $mysqli->prepare($query);
    
    // Add limit and offset parameters
    $offset = ($page - 1) * $limit;
    $newParams = $params;
    $newParams[] = $limit;
    $newParams[] = $offset;
    $newTypes = $types . 'ii';
    
    if (!empty($newTypes)) {
        $stmt->bind_param($newTypes, ...$newParams);
    }
    
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