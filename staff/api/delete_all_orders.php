<?php
// API endpoint to delete all orders (marks them as archived)
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
    
    // Start a transaction to ensure data consistency
    $mysqli->begin_transaction();
    
    // First, update the status of all orders to 'archived'
    // This ensures they're not displayed in active orders but still accessible in order history
    $updateQuery = "UPDATE orders SET status = 'archived' WHERE status IN ('preparing', 'ready', 'picked up', 'cancelled')";
    $result = $mysqli->query($updateQuery);
    
    if (!$result) {
        throw new Exception('Failed to update order statuses: ' . $mysqli->error);
    }
    
    // Get count of affected rows
    $affectedRows = $mysqli->affected_rows;
    
    // Create an archive record of orders (optional - for future implementation)
    /*
    $archiveQuery = "INSERT INTO order_archives (order_id, order_data, archived_date) 
                    SELECT order_id, JSON_OBJECT('order_number', order_number, 'customer_name', customer_name, 
                           'customer_phone', customer_phone, 'order_total', order_total, 
                           'status', status, 'order_placed_time', order_placed_time), 
                           NOW() FROM orders";
    $mysqli->query($archiveQuery);
    */
    
    // Commit the transaction
    $mysqli->commit();
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'All orders have been archived successfully',
        'affected_rows' => $affectedRows
    ]);
    
} catch (Exception $e) {
    // Roll back the transaction in case of error
    if ($mysqli->connect_errno === 0 && $mysqli->errno !== 0) {
        $mysqli->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Error archiving orders: ' . $e->getMessage()
    ]);
}
?>