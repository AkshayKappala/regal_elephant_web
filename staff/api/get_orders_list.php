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

// Pagination and filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(5, intval($_GET['limit']))) : 20;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Calculate offset
$offset = ($page - 1) * $limit;

// Build query conditions
$conditions = [];
$params = [];
$types = '';

if ($status) {
    $conditions[] = "o.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($date) {
    $conditions[] = "DATE(o.order_placed_time) = ?";
    $params[] = $date;
    $types .= 's';
}

if ($search) {
    $conditions[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

// Construct the WHERE clause
$whereClause = '';
if (!empty($conditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

try {
    $mysqli = Database::getConnection();
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM orders o $whereClause";
    
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
             (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
             FROM orders o 
             $whereClause 
             ORDER BY o.order_placed_time DESC 
             LIMIT ? OFFSET ?";
    
    // Add limit and offset to params
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
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