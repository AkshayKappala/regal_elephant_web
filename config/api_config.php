<?php
$isProduction = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'ondigitalocean.app') !== false);

if ($isProduction) {
    $protocol = 'https://';
    $projectPath = '/staff/api/';
} else {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $projectPath = '/INFS730/regal_elephant_web/staff/api/';
}

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

$baseUrl = $protocol . $host;

define('STAFF_API_URL', $baseUrl . $projectPath);

define('API_KEY', 'regal_elephant_secure_api_key');

define('USE_SERVER_SENT_EVENTS', true);