<?php
// Script to update the orders table to include the 'archived' status
require_once __DIR__ . '/../../config/database.php';

try {
    $mysqli = Database::getConnection();
    
    // Check if 'archived' is already in the enum
    $checkQuery = "SHOW COLUMNS FROM orders LIKE 'status'";
    $result = $mysqli->query($checkQuery);
    $column = $result->fetch_assoc();
    
    if ($column) {
        $type = $column['Type'];
        // Check if 'archived' is already in the enum
        if (strpos($type, 'archived') === false) {
            // Alter the table to add 'archived' to the enum
            $alterQuery = "ALTER TABLE orders MODIFY COLUMN status ENUM('preparing', 'ready', 'picked up', 'cancelled', 'archived') DEFAULT 'preparing'";
            $alterResult = $mysqli->query($alterQuery);
            
            if ($alterResult) {
                echo json_encode(['success' => true, 'message' => 'Successfully updated orders table to include archived status']);
            } else {
                throw new Exception('Failed to alter table: ' . $mysqli->error);
            }
        } else {
            echo json_encode(['success' => true, 'message' => 'Status field already includes archived option']);
        }
    } else {
        throw new Exception('Could not find status column in orders table');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>