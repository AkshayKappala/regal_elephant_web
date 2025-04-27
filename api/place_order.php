<?php
// Legacy place_order.php - now redirects to the staff API endpoint
header('Content-Type: application/json');
require_once __DIR__ . '/../config/api_config.php';

// Log the legacy access with more details
error_log("LEGACY API: place_order.php accessed - redirecting to staff API");
error_log("Request headers: " . json_encode(getallheaders()));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Read the incoming data
$input = file_get_contents('php://input');
$data = json_decode($input, true);
error_log("Request data: " . $input);

if (!$data) {
    error_log("Invalid JSON input in place_order.php");
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    // Forward the request to the staff API
    $ch = curl_init(STAFF_API_URL . 'receive_order.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . API_KEY
    ]);
    
    // Add more detailed curl error handling
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log("cURL Error in place_order.php: " . curl_error($ch));
        throw new Exception("Error connecting to staff API: " . curl_error($ch));
    }
    
    error_log("Staff API response code: " . $httpCode);
    error_log("Staff API response: " . $response);
    
    curl_close($ch);
    
    // Forward the response from the staff API
    http_response_code($httpCode);
    echo $response;
} catch (Exception $e) {
    error_log("Exception in place_order.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error forwarding request: ' . $e->getMessage()]);
}
