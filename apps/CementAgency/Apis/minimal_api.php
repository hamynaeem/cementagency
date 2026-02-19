<?php
// Simple API endpoint test without REST controller
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

try {
    // Load CodeIgniter database config
    require_once __DIR__ . '/application/config/database.php';
    
    $dbPath = $db['default']['database'];
    
    if (!file_exists($dbPath)) {
        echo json_encode(['error' => 'Database file not found', 'path' => $dbPath]);
        exit;
    }
    
    // Connect to SQLite
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle different endpoints based on URL path
    $requestUri = $_SERVER['REQUEST_URI'];
    $queryParams = $_GET;
    
    if (strpos($requestUri, '/minimal_booking') !== false) {
        // Simple booking query
        $filter = isset($queryParams['filter']) ? $queryParams['filter'] : "1=1";
        $orderby = isset($queryParams['orderby']) ? preg_replace('/[^a-zA-Z0-9_\s]/', '', $queryParams['orderby']) : "BookingID";
        
        $sql = "SELECT * FROM booking WHERE {$filter} ORDER BY {$orderby} LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($results);
        
    } else if (strpos($requestUri, '/minimal_vouchers') !== false) {
        // Simple vouchers query
        $filter = isset($queryParams['filter']) ? $queryParams['filter'] : "1=1";
        $orderby = isset($queryParams['orderby']) ? preg_replace('/[^a-zA-Z0-9_\s]/', '', $queryParams['orderby']) : "VoucherID";
        
        $sql = "SELECT * FROM vouchers WHERE {$filter} ORDER BY {$orderby} LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($results);
        
    } else {
        // Default test response
        echo json_encode([
            'status' => 'success',
            'message' => 'Minimal API working',
            'timestamp' => date('Y-m-d H:i:s'),
            'available_endpoints' => [
                '/minimal_api.php/minimal_booking',
                '/minimal_api.php/minimal_vouchers'
            ],
            'sample_query' => '/minimal_api.php/minimal_booking?filter=Date%20between%20\'2025-09-23\'%20and%20\'2025-09-23\'&orderby=BookingID'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>