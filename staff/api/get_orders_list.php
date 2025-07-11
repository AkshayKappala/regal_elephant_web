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

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(5, intval($_GET['limit']))) : 20;

$status = isset($_GET['status']) ? $_GET['status'] : '';
$includeArchived = isset($_GET['include_archived']) ? (bool)$_GET['include_archived'] : true;

try {
    $mysqli = Database::getConnection();
    
    $whereClause = [];
    $params = [];
    $types = '';
    
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
    
    $whereString = !empty($whereClause) ? " WHERE " . implode(" AND ", $whereClause) : "";
    
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
    
    $query = "SELECT o.*, 
             (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) as item_count 
             FROM orders o 
             $whereString
             ORDER BY o.order_placed_time DESC 
             LIMIT ? OFFSET ?";
    
    $stmt = $mysqli->prepare($query);
    
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