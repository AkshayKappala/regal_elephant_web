<?php
/**
 * Legacy API endpoint for order placement - now proxies to staff API
 * 
 * This file serves as a compatibility layer for any existing client code 
 * that still sends requests to this endpoint. All functionality has been
 * moved to the staff API receive_order.php endpoint.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/api_config.php';

// Log incoming request
error_log("LEGACY API: place_order.php accessed - forwarding to staff API");

// Read the incoming data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
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
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception("Error connecting to staff API: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Forward the response from the staff API
    http_response_code($httpCode);
    echo $response;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error forwarding request: ' . $e->getMessage()]);
}
