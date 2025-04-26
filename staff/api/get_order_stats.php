<?php
// API endpoint to get order statistics for the staff dashboard
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

try {
    $mysqli = Database::getConnection();
    
    // Initialize stats
    $stats = [
        'preparing' => 0,
        'ready' => 0,
        'picked_up' => 0,
        'cancelled' => 0,
        'total' => 0
    ];
    
    // Count orders by status
    $query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $result = $mysqli->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = str_replace(' ', '_', $row['status']);
            $stats[$status] = intval($row['count']);
            $stats['total'] += intval($row['count']);
        }
    }
    
    // Send response
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error retrieving order statistics: ' . $e->getMessage()
    ]);
}
?>