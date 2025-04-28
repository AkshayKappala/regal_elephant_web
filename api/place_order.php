<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/api_config.php';

error_log("LEGACY API: place_order.php accessed - forwarding to staff API");

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
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
    
    http_response_code($httpCode);
    echo $response;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error forwarding request: ' . $e->getMessage()]);
}
