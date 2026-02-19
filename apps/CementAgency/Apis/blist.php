<?php
// Business List API endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers for Angular frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Database connection
    $dbPath = __DIR__ . '/sqlite3/db_cement.db';
    
    if (!file_exists($dbPath)) {
        echo json_encode(['error' => 'Database file not found', 'path' => $dbPath]);
        exit;
    }
    
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create businesses table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS businesses (
            BusinessID INTEGER PRIMARY KEY AUTOINCREMENT,
            BusinessName VARCHAR(255) NOT NULL,
            Address VARCHAR(500),
            Phone VARCHAR(50),
            Email VARCHAR(100),
            Status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Check if there are any businesses, if not create sample data
    $countSql = "SELECT COUNT(*) FROM businesses";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute();
    $count = $countStmt->fetchColumn();
    
    if ($count == 0) {
        // Insert sample business data
        $sampleBusinesses = [
            ['Cement Agency Main Branch', '123 Main St, City', '123-456-7890', 'main@cementagency.com'],
            ['Cement Agency North Branch', '456 North Ave, City', '123-456-7891', 'north@cementagency.com'],
            ['Cement Agency South Branch', '789 South St, City', '123-456-7892', 'south@cementagency.com']
        ];
        
        $insertSql = "INSERT INTO businesses (BusinessName, Address, Phone, Email) VALUES (?, ?, ?, ?)";
        $insertStmt = $pdo->prepare($insertSql);
        
        foreach ($sampleBusinesses as $business) {
            $insertStmt->execute($business);
        }
    }
    
    // Get the request method and data
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get all active businesses
            $filter = isset($_GET['filter']) ? $_GET['filter'] : "Status = 1";
            $orderby = isset($_GET['orderby']) ? preg_replace('/[^a-zA-Z0-9_\s]/', '', $_GET['orderby']) : "BusinessName";
            
            $sql = "SELECT BusinessID, BusinessName, Address, Phone, Email, Status FROM businesses WHERE {$filter} ORDER BY {$orderby}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($results);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>