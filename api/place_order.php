<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $mysqli = Database::getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection error.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$name = $data['name'] ?? '';
$mobile = $data['mobile'] ?? '';
$email = $data['email'] ?? '';
$tip = $data['tip'] ?? 0;
$cartItems = $data['cartItems'] ?? [];

if (!$name || !$mobile || empty($cartItems)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$order_number = 'ORD' . date('YmdHis') . rand(100, 999);

$mysqli->begin_transaction();
try {
    $calculated_subtotal = 0;
    foreach ($cartItems as $item) {
        $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
        $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
        $item_id = filter_var($item['item_id'], FILTER_VALIDATE_INT);

        if ($quantity === false || $quantity <= 0 || $price === false || $price < 0 || $item_id === false) {
            throw new Exception("Invalid item data received for item ID: " . ($item['item_id'] ?? 'unknown'));
        }
        $calculated_subtotal += $price * $quantity;
    }

    $tax_rate = 0.10;
    $calculated_tax = $calculated_subtotal * $tax_rate;
    $calculated_tip = filter_var($tip, FILTER_VALIDATE_FLOAT);
    if ($calculated_tip === false || $calculated_tip < 0) {
        $calculated_tip = 0;
    }

    $calculated_total = $calculated_subtotal + $calculated_tax + $calculated_tip;

    $stmt = $mysqli->prepare("INSERT INTO orders (order_number, customer_name, customer_phone, customer_email, order_total, status) VALUES (?, ?, ?, ?, ?, 'preparing')");
    if (!$stmt) {
        throw new Exception("Prepare statement failed for orders table.");
    }
    $stmt->bind_param('ssssd', $order_number, $name, $mobile, $email, $calculated_total);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, item_id, quantity, item_price) VALUES (?, ?, ?, ?)");
     if (!$stmt) {
        throw new Exception("Prepare statement failed for order_items table.");
    }
    foreach ($cartItems as $item) {
        $item_id = $item['item_id'];
        $quantity = $item['quantity'];
        $item_price = $item['price'];
        $stmt->bind_param('iiid', $order_id, $item_id, $quantity, $item_price);
        $stmt->execute();
        if ($stmt->error) {
             throw new Exception("Execute statement failed for order_items.");
        }
    }
    $stmt->close();

    $mysqli->commit();
    
    // Trigger an event for the new order
    $status = 'preparing'; // Default status for new orders
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $eventEndpoint = "$protocol://$host/INFS730/regal_elephant_web/api/order_status_events.php?order_id=$order_id&status=" . urlencode($status);
    
    // Non-blocking request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $eventEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Short timeout - we don't care about the response
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    curl_close($ch);
    
    echo json_encode(['success' => true, 'order_id' => $order_id, 'order_number' => $order_number]);
} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Order failed: ' . $e->getMessage()]);
}
