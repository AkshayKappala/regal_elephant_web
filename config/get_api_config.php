<?php
require_once __DIR__ . '/api_config.php';

header('Content-Type: application/json');
echo json_encode([
    'staff_api_url' => STAFF_API_URL,
    'api_key' => API_KEY
]);