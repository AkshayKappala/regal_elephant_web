<?php
/**
 * API Configuration values for JavaScript
 * 
 * This endpoint exposes necessary API configuration values to the client-side
 * JavaScript in a controlled manner. Only non-sensitive information should be
 * exposed here.
 */
require_once __DIR__ . '/api_config.php';

// Return only the configuration values needed by client-side code
header('Content-Type: application/json');
echo json_encode([
    'staff_api_url' => STAFF_API_URL,
    'api_key' => API_KEY // Note: In a production environment, consider using a more secure approach for API key management
]);