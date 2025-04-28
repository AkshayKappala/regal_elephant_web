<?php
// Generate base URL dynamically based on server information
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$baseDir = dirname($_SERVER['PHP_SELF'], 3); // Go up three levels from config dir
$baseUrl = $protocol . $host . $baseDir;

// Define API URLs dynamically
define('STAFF_API_URL', $baseUrl . '/staff/api/');

define('API_KEY', 'regal_elephant_secure_api_key');

define('USE_SERVER_SENT_EVENTS', true);