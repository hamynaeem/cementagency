<?php
// Enhanced router for API endpoints
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers for all requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$url = parse_url($_SERVER['REQUEST_URI']);
$path = $url['path'];

// API routing rules
$routes = [
    '/blist' => 'blist.php',
    '/transports_api.php' => 'transports_api.php',
    '/transports' => 'transports_api.php',
    '/test_server.php' => 'test_server.php',
    '/test' => 'test_server.php',
    '/direct_api.php' => 'direct_api.php',
    '/api' => 'direct_api.php',
    '/users' => 'common_api.php',
    '/customers' => 'common_api.php',
    '/products' => 'common_api.php',
    '/agents' => 'common_api.php',
    '/qrystock' => 'common_api.php',
    '/qryvouchers' => 'common_api.php',
    '/qrybooking' => 'common_api.php'
];

// Check for API routes first
foreach ($routes as $route => $file) {
    if ($path === $route || strpos($path, $route) === 0) {
        if (file_exists(__DIR__ . '/' . $file)) {
            include_once __DIR__ . '/' . $file;
            exit();
        }
    }
}

// Check if it's a static file that exists
$file = __DIR__ . $path;
if (is_file($file)) {
    return false; // Let PHP serve the file
}

// For all other requests, try CodeIgniter
if (file_exists(__DIR__ . '/index.php')) {
    $_SERVER['PATH_INFO'] = $path;
    include_once __DIR__ . '/index.php';
} else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Not Found',
        'message' => 'The requested endpoint was not found',
        'path' => $path,
        'available_routes' => array_keys($routes)
    ]);
}
?>