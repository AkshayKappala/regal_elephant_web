<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

try {
    $mysqli = Database::getConnection();
    
    $mysqli->begin_transaction();
    
    $updateQuery = "UPDATE orders SET status = 'archived' WHERE status IN ('preparing', 'ready', 'picked up', 'cancelled')";
    $result = $mysqli->query($updateQuery);
    
    if (!$result) {
        throw new Exception('Failed to update order statuses: ' . $mysqli->error);
    }
    
    $affectedRows = $mysqli->affected_rows;
    
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'All orders have been archived successfully',
        'affected_rows' => $affectedRows
    ]);
    
} catch (Exception $e) {
    if ($mysqli->connect_errno === 0 && $mysqli->errno !== 0) {
        $mysqli->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Error archiving orders: ' . $e->getMessage()
    ]);
}
?>