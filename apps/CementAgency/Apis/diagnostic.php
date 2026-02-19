<?php
// Simple diagnostic script to identify 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PHP Diagnostic Test ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current Directory: " . getcwd() . "\n";

// Test basic file includes
echo "\n=== Testing File Includes ===\n";

try {
    echo "Testing APPPATH definition...\n";
    if (!defined('APPPATH')) {
        define('APPPATH', __DIR__ . '/application/');
        echo "APPPATH defined as: " . APPPATH . "\n";
    }
    
    echo "Testing REST_Controller include...\n";
    if (file_exists(APPPATH . '/libraries/REST_Controller.php')) {
        echo "REST_Controller.php found\n";
        require_once APPPATH . '/libraries/REST_Controller.php';
        echo "REST_Controller included successfully\n";
    } else {
        echo "ERROR: REST_Controller.php not found\n";
    }
    
    echo "Testing JWT include...\n";
    if (file_exists(APPPATH . '/libraries/JWT.php')) {
        echo "JWT.php found\n";
        require_once APPPATH . '/libraries/JWT.php';
        echo "JWT included successfully\n";
    } else {
        echo "ERROR: JWT.php not found\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>