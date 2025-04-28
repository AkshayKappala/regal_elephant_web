<?php
header('Content-Type: application/json');
// Add CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/api_config.php';

// Improved header handling for different server environments
$headers = getallheaders();
$apiKey = '';

// Check in multiple ways since server configurations can be different
if (isset($headers['X-API-Key'])) {
    $apiKey = $headers['X-API-Key'];
} elseif (isset($headers['x-api-key'])) {
    $apiKey = $headers['x-api-key'];
} elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
    $apiKey = $_SERVER['HTTP_X_API_KEY'];
}

// For debugging purposes - log the provided vs expected keys
error_log("API Key provided: " . $apiKey);
error_log("Expected API Key: " . API_KEY);

if ($apiKey !== API_KEY) {
    error_log("receive_order.php: Unauthorized access attempt");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['name']) || !isset($data['mobile']) || !isset($data['cartItems']) || empty($data['cartItems'])) {
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
    
    $order_number = 'ORD' . date('YmdHis') . rand(100, 999);
    
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
    $calculated_tip = max(0, $tip);
    $calculated_total = $calculated_subtotal + $calculated_tax + $calculated_tip;
    
    $stmt = $mysqli->prepare("INSERT INTO orders (order_number, customer_name, customer_phone, customer_email, order_total, status) VALUES (?, ?, ?, ?, ?, 'preparing')");
    if (!$stmt) {
        throw new Exception("Prepare statement failed for orders table.");
    }
    
    $stmt->bind_param('ssssd', $order_number, $name, $mobile, $email, $calculated_total);
    $stmt->execute();
    if ($stmt->error) {
        throw new Exception("Execute statement failed for orders table: " . $stmt->error);
    }
    
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
            throw new Exception("Execute statement failed for order_items: " . $stmt->error);
        }
    }
    $stmt->close();
    
    $mysqli->commit();
    
    $eventUrl = '../../api/order_status_events.php?order_id=' . $order_id . '&status=preparing&event_type=new_order';
    $absolutePath = realpath(__DIR__ . '/' . $eventUrl);
    
    if (file_exists($absolutePath)) {
        include_once $absolutePath;
    } else {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $path = explode('/staff', $_SERVER['REQUEST_URI'])[0] ?? '';
        $eventEndpoint = "$protocol://$host$path/api/order_status_events.php?order_id=$order_id&status=preparing&event_type=new_order";
        
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
    if (isset($mysqli) && $mysqli->ping()) {
        $mysqli->rollback();
    }
    error_log("receive_order.php: Error - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Order failed: ' . $e->getMessage()]);
}
?>