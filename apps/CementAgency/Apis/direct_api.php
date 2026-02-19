<?php
// Direct SQLite API without CodeIgniter
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Direct SQLite connection
    $dbPath = __DIR__ . '/sqlite3/db_cement.db';
    
    if (!file_exists($dbPath)) {
        echo json_encode(['error' => 'Database file not found', 'path' => $dbPath]);
        exit;
    }
    
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle different endpoints
    $requestUri = $_SERVER['REQUEST_URI'];
    
    if (strpos($requestUri, '/qrybooking') !== false) {
        // Booking query similar to the original API
        $filter = isset($_GET['filter']) ? $_GET['filter'] : "1=1";
        $orderby = isset($_GET['orderby']) ? preg_replace('/[^a-zA-Z0-9_\s]/', '', $_GET['orderby']) : "BookingID";
        
        $sql = "SELECT * FROM booking WHERE {$filter} ORDER BY {$orderby} LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($results);
        
    } else if (strpos($requestUri, '/qryvouchers') !== false) {
        // Vouchers query similar to the original API
        $filter = isset($_GET['filter']) ? $_GET['filter'] : "1=1";
        $orderby = isset($_GET['orderby']) ? preg_replace('/[^a-zA-Z0-9_\s]/', '', $_GET['orderby']) : "VoucherID";
        
        $sql = "SELECT * FROM vouchers WHERE {$filter} ORDER BY {$orderby} LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($results);
        
    } else {
        // Test endpoint
        echo json_encode([
            'status' => 'success',
            'message' => 'Direct SQLite API working',
            'timestamp' => date('Y-m-d H:i:s'),
            'database_path' => $dbPath,
            'available_endpoints' => [
                '/direct_api.php/qrybooking',
                '/direct_api.php/qryvouchers'
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error', 
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>