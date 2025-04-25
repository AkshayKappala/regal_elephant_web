<?php
error_log("place_order.php: Script started."); // Log: Start
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $mysqli = Database::getConnection();
    error_log("place_order.php: Database connection obtained."); // Log: DB Connected
} catch (Exception $e) {
    error_log("place_order.php: Database connection failed: " . $e->getMessage()); // Log: DB Fail
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection error.']);
    exit;
}

// Get POST data
$input = file_get_contents('php://input');
error_log("place_order.php: Raw input: " . $input); // Log: Raw Input
$data = json_decode($input, true);

if (!$data) {
    error_log("place_order.php: Invalid JSON input."); // Log: Invalid JSON
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}
error_log("place_order.php: Decoded data: " . print_r($data, true)); // Log: Decoded Data

$name = $data['name'] ?? '';
$mobile = $data['mobile'] ?? '';
$email = $data['email'] ?? '';
$tip = $data['tip'] ?? 0;
$cartItems = $data['cartItems'] ?? [];

if (!$name || !$mobile || empty($cartItems)) {
    error_log("place_order.php: Missing required fields. Name: $name, Mobile: $mobile, CartEmpty: " . empty($cartItems)); // Log: Missing Fields
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Generate unique order number (e.g., ORD + timestamp + random)
$order_number = 'ORD' . date('YmdHis') . rand(100, 999);
error_log("place_order.php: Generated Order Number: $order_number"); // Log: Order Number

$mysqli->begin_transaction();
error_log("place_order.php: Transaction started."); // Log: Transaction Start
try {
    // --- Server-side Calculation ---
    $calculated_subtotal = 0;
    foreach ($cartItems as $item) {
        // Ensure quantity and price are numeric and valid
        $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
        $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT); // Use price confirmed by get_item_ids.php
        $item_id = filter_var($item['item_id'], FILTER_VALIDATE_INT); // Ensure item_id is present

        if ($quantity === false || $quantity <= 0 || $price === false || $price < 0 || $item_id === false) {
            throw new Exception("Invalid item data received for item ID: " . ($item['item_id'] ?? 'unknown'));
        }
        $calculated_subtotal += $price * $quantity;
    }

    $tax_rate = 0.10; // Define tax rate on the server
    $calculated_tax = $calculated_subtotal * $tax_rate;
    $calculated_tip = filter_var($tip, FILTER_VALIDATE_FLOAT); // Validate submitted tip
    if ($calculated_tip === false || $calculated_tip < 0) {
        $calculated_tip = 0; // Default to 0 if invalid
    }

    $calculated_total = $calculated_subtotal + $calculated_tax + $calculated_tip;
    error_log("place_order.php: Server Calculated - Subtotal: $calculated_subtotal, Tax: $calculated_tax, Tip: $calculated_tip, Total: $calculated_total"); // Log: Server Calc

    // Insert into orders table using calculated total
    $stmt = $mysqli->prepare("INSERT INTO orders (order_number, customer_name, customer_phone, customer_email, order_total, status) VALUES (?, ?, ?, ?, ?, 'preparing')"); // Added default status
    if (!$stmt) {
        error_log("place_order.php: Prepare failed (orders): (" . $mysqli->errno . ") " . $mysqli->error); // Log: Prepare Fail
        throw new Exception("Prepare statement failed for orders table.");
    }
    // Use the server-calculated total
    $stmt->bind_param('ssssd', $order_number, $name, $mobile, $email, $calculated_total);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    error_log("place_order.php: Inserted into orders. Order ID: $order_id"); // Log: Order Inserted
    $stmt->close();

    // Insert order items
    $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, item_id, quantity, item_price) VALUES (?, ?, ?, ?)");
     if (!$stmt) {
        error_log("place_order.php: Prepare failed (order_items): (" . $mysqli->errno . ") " . $mysqli->error); // Log: Prepare Fail Items
        throw new Exception("Prepare statement failed for order_items table.");
    }
    foreach ($cartItems as $item) {
        $item_id = $item['item_id'];
        $quantity = $item['quantity'];
        $item_price = $item['price'];
        error_log("place_order.php: Inserting item - OrderID: $order_id, ItemID: $item_id, Qty: $quantity, Price: $item_price"); // Log: Inserting Item
        $stmt->bind_param('iiid', $order_id, $item_id, $quantity, $item_price);
        $stmt->execute();
        if ($stmt->error) {
             error_log("place_order.php: Execute failed (order_items): (" . $stmt->errno . ") " . $stmt->error); // Log: Execute Fail Item
             throw new Exception("Execute statement failed for order_items.");
        }
    }
    $stmt->close();
    error_log("place_order.php: All items inserted."); // Log: Items Inserted

    $mysqli->commit();
    error_log("place_order.php: Transaction committed. Order ID: $order_id, Order Number: $order_number"); // Log: Commit
    echo json_encode(['success' => true, 'order_id' => $order_id, 'order_number' => $order_number]);
} catch (Exception $e) {
    $mysqli->rollback();
    error_log("place_order.php: Transaction rolled back. Error: " . $e->getMessage()); // Log: Rollback
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Order failed: ' . $e->getMessage()]);
}
error_log("place_order.php: Script finished."); // Log: End
