<?php
// API configuration values for JavaScript
require_once __DIR__ . '/api_config.php';

// Return configuration values for client-side code
// Don't expose any sensitive information here!
header('Content-Type: application/json');
echo json_encode([
    'staff_api_url' => STAFF_API_URL,
    'api_key' => API_KEY // In a production environment, consider a more secure approach
]);