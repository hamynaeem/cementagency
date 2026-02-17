<?php
// Simple router for CodeIgniter with PHP development server

$url = parse_url($_SERVER['REQUEST_URI']);
$file = __DIR__ . $url['path'];

// Check if it's a static file that exists
if (is_file($file)) {
    return false; // Let PHP serve the file
}

// For all other requests, route to index.php
$_SERVER['PATH_INFO'] = $url['path'];
include_once 'index.php';
?>