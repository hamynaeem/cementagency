<?php
// Common API endpoints handler
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
        // Create directory if it doesn't exist
        $sqliteDir = dirname($dbPath);
        if (!is_dir($sqliteDir)) {
            mkdir($sqliteDir, 0777, true);
        }
        
        // Create empty database file
        touch($dbPath);
    }
    
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create common tables if they don't exist
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                UserID INTEGER PRIMARY KEY AUTOINCREMENT,
                Username VARCHAR(100) UNIQUE NOT NULL,
                Password VARCHAR(255) NOT NULL,
                Email VARCHAR(100),
                FullName VARCHAR(255),
                Status INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'customers' => "
            CREATE TABLE IF NOT EXISTS customers (
                CustomerID INTEGER PRIMARY KEY AUTOINCREMENT,
                CustomerName VARCHAR(255) NOT NULL,
                Address TEXT,
                Phone VARCHAR(50),
                Email VARCHAR(100),
                Balance DECIMAL(15,2) DEFAULT 0,
                customer_type VARCHAR(50) DEFAULT 'customer',
                AcctTypeID INTEGER DEFAULT 1,
                Status INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'products' => "
            CREATE TABLE IF NOT EXISTS products (
                ProductID INTEGER PRIMARY KEY AUTOINCREMENT,
                ProductName VARCHAR(255) NOT NULL,
                SPrice DECIMAL(10,2) DEFAULT 0,
                PPrice DECIMAL(10,2) DEFAULT 0,
                Stock INTEGER DEFAULT 0,
                Packing VARCHAR(100),
                UnitValue INTEGER DEFAULT 1,
                Status INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'agents' => "
            CREATE TABLE IF NOT EXISTS agents (
                AgentID INTEGER PRIMARY KEY AUTOINCREMENT,
                AgentName VARCHAR(255) NOT NULL,
                Phone VARCHAR(50),
                Address TEXT,
                Commission DECIMAL(5,2) DEFAULT 0,
                status INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'vouchers' => "
            CREATE TABLE IF NOT EXISTS vouchers (
                VoucherID INTEGER PRIMARY KEY AUTOINCREMENT,
                VoucherNo VARCHAR(100),
                VoucherType VARCHAR(50),
                Amount DECIMAL(15,2),
                Description TEXT,
                VoucherDate DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'booking' => "
            CREATE TABLE IF NOT EXISTS booking (
                BookingID INTEGER PRIMARY KEY AUTOINCREMENT,
                BookingNo VARCHAR(100),
                CustomerID INTEGER,
                ProductID INTEGER,
                Quantity INTEGER,
                Rate DECIMAL(10,2),
                Amount DECIMAL(15,2),
                BookingDate DATE,
                Status VARCHAR(50) DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        "
    ];
    
    foreach ($tables as $tableName => $sql) {
        $pdo->exec($sql);
    }
    
    // Get the request URI and method
    $requestUri = $_SERVER['REQUEST_URI'];
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Route handling
    if (strpos($requestUri, '/users') !== false) {
        handleUsersEndpoint($pdo, $method, $input);
    } elseif (strpos($requestUri, '/customers') !== false) {
        handleCustomersEndpoint($pdo, $method, $input);
    } elseif (strpos($requestUri, '/products') !== false) {
        handleProductsEndpoint($pdo, $method, $input);
    } elseif (strpos($requestUri, '/agents') !== false) {
        handleAgentsEndpoint($pdo, $method, $input);
    } elseif (strpos($requestUri, '/qrystock') !== false) {
        handleStockQuery($pdo);
    } elseif (strpos($requestUri, '/qryvouchers') !== false) {
        handleVouchersQuery($pdo);
    } elseif (strpos($requestUri, '/qrybooking') !== false) {
        handleBookingQuery($pdo);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'Common API endpoints working',
            'available_endpoints' => [
                '/users', '/customers', '/products', '/agents',
                '/qrystock', '/qryvouchers', '/qrybooking'
            ]
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}

function handleUsersEndpoint($pdo, $method, $input) {
    switch ($method) {
        case 'GET':
            $sql = "SELECT UserID, Username, Email, FullName, Status FROM users WHERE Status = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleCustomersEndpoint($pdo, $method, $input) {
    switch ($method) {
        case 'GET':
            $filter = $_GET['filter'] ?? '1=1';
            $orderby = $_GET['orderby'] ?? 'CustomerName';
            $flds = $_GET['flds'] ?? '*';
            
            $sql = "SELECT {$flds} FROM customers WHERE {$filter} ORDER BY {$orderby}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleProductsEndpoint($pdo, $method, $input) {
    switch ($method) {
        case 'GET':
            $filter = $_GET['filter'] ?? '1=1';
            $orderby = $_GET['orderby'] ?? 'ProductName';
            $flds = $_GET['flds'] ?? '*';
            
            $sql = "SELECT {$flds} FROM products WHERE {$filter} ORDER BY {$orderby}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleAgentsEndpoint($pdo, $method, $input) {
    switch ($method) {
        case 'GET':
            $filter = $_GET['filter'] ?? 'status=1';
            $orderby = $_GET['orderby'] ?? 'AgentName';
            
            $sql = "SELECT * FROM agents WHERE {$filter} ORDER BY {$orderby}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleStockQuery($pdo) {
    $filter = $_GET['filter'] ?? '1=1';
    $orderby = $_GET['orderby'] ?? 'ProductName';
    $flds = $_GET['flds'] ?? 'ProductID,ProductName,Stock,SPrice,PPrice,Packing,UnitValue';
    
    $sql = "SELECT {$flds} FROM products WHERE {$filter} ORDER BY {$orderby}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function handleVouchersQuery($pdo) {
    $filter = $_GET['filter'] ?? '1=1';
    $orderby = $_GET['orderby'] ?? 'VoucherID';
    
    $sql = "SELECT * FROM vouchers WHERE {$filter} ORDER BY {$orderby} LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function handleBookingQuery($pdo) {
    $filter = $_GET['filter'] ?? '1=1';
    $orderby = $_GET['orderby'] ?? 'BookingID';
    
    $sql = "SELECT * FROM booking WHERE {$filter} ORDER BY {$orderby} LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>