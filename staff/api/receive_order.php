<?php
// API endpoint to receive orders from customer site
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/api_config.php'; // Include API config

// Get request headers
$headers = getallheaders();
$apiKey = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : '';

// Validate API key
if ($apiKey !== API_KEY) {
    error_log("receive_order.php: Invalid API key provided");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get JSON input data
$input = file_get_contents('php://input');
$data = json_decode($input, true);
error_log("receive_order.php: Received data: " . print_r($data, true));

// Validate input
if (!isset($data['name']) || !isset($data['mobile']) || !isset($data['cartItems']) || empty($data['cartItems'])) {
    error_log("receive_order.php: Missing required fields");
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$name = $data['name'];
$mobile = $data['mobile'];
$email = $data['email'] ?? '';
$tip = isset($data['tip']) ? floatval($data['tip']) : 0;
$cartItems = $data['cartItems'];

try {
    $mysqli = Database::getConnection();
    $mysqli->begin_transaction();
    error_log("receive_order.php: Transaction started");
    
    // Generate unique order number
    $order_number = 'ORD' . date('YmdHis') . rand(100, 999);
    error_log("receive_order.php: Generated Order Number: $order_number");
    
    // Calculate order totals server-side for security
    $calculated_subtotal = 0;
    foreach ($cartItems as $item) {
        // Ensure quantity and price are numeric and valid
        $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
        $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
        $item_id = filter_var($item['item_id'], FILTER_VALIDATE_INT);
        
        if ($quantity === false || $quantity <= 0 || $price === false || $price < 0 || $item_id === false) {
            throw new Exception("Invalid item data received for item ID: " . ($item['item_id'] ?? 'unknown'));
        }
        $calculated_subtotal += $price * $quantity;
    }
    
    $tax_rate = 0.10; // Define tax rate on the server
    $calculated_tax = $calculated_subtotal * $tax_rate;
    $calculated_tip = max(0, $tip); // Ensure tip is not negative
    $calculated_total = $calculated_subtotal + $calculated_tax + $calculated_tip;
    
    error_log("receive_order.php: Server Calculated - Subtotal: $calculated_subtotal, Tax: $calculated_tax, Tip: $calculated_tip, Total: $calculated_total");
    
    // Insert into orders table using calculated total
    $stmt = $mysqli->prepare("INSERT INTO orders (order_number, customer_name, customer_phone, customer_email, order_total, status) VALUES (?, ?, ?, ?, ?, 'preparing')");
    if (!$stmt) {
        throw new Exception("Prepare statement failed for orders table.");
    }
    
    // Use the server-calculated total
    $stmt->bind_param('ssssd', $order_number, $name, $mobile, $email, $calculated_total);
    $stmt->execute();
    if ($stmt->error) {
        throw new Exception("Execute statement failed for orders table: " . $stmt->error);
    }
    
    $order_id = $stmt->insert_id;
    error_log("receive_order.php: Inserted into orders. Order ID: $order_id");
    $stmt->close();
    
    // Insert order items
    $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, item_id, quantity, item_price) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare statement failed for order_items table.");
    }
    
    foreach ($cartItems as $item) {
        $item_id = $item['item_id'];
        $quantity = $item['quantity'];
        $item_price = $item['price'];
        
        error_log("receive_order.php: Inserting item - OrderID: $order_id, ItemID: $item_id, Qty: $quantity, Price: $item_price");
        $stmt->bind_param('iiid', $order_id, $item_id, $quantity, $item_price);
        $stmt->execute();
        if ($stmt->error) {
            throw new Exception("Execute statement failed for order_items: " . $stmt->error);
        }
    }
    $stmt->close();
    error_log("receive_order.php: All items inserted.");
    
    // Commit the transaction
    $mysqli->commit();
    error_log("receive_order.php: Transaction committed. Order ID: $order_id, Order Number: $order_number");
    
    // Trigger server-sent event for new order
    // Similar approach to what's done in update_order_status.php
    $eventUrl = '../../api/order_status_events.php?order_id=' . $order_id . '&status=preparing';
    $absolutePath = realpath(__DIR__ . '/' . $eventUrl);
    
    if (file_exists($absolutePath)) {
        include_once $absolutePath;
    } else {
        // Make an asynchronous HTTP request
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $path = explode('/staff', $_SERVER['REQUEST_URI'])[0] ?? '';
        $eventEndpoint = "$protocol://$host$path/api/order_status_events.php?order_id=$order_id&status=preparing";
        
        // Non-blocking request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $eventEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);
        curl_close($ch);
    }
    
    echo json_encode([
        'success' => true, 
        'order_id' => $order_id, 
        'order_number' => $order_number
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($mysqli) && $mysqli->ping()) {
        $mysqli->rollback();
    }
    error_log("receive_order.php: Error - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Order failed: ' . $e->getMessage()]);
}

error_log("receive_order.php: Script finished");
?>