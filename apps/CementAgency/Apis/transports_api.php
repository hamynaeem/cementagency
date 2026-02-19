<?php
// Transport API endpoint
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
    
    // Create transports table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS transports (
            TransportID INTEGER PRIMARY KEY AUTOINCREMENT,
            TransportName VARCHAR(255) NOT NULL,
            VehicleNo VARCHAR(255) NOT NULL,
            DriverName VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Get the request method and data
    $method = $_SERVER['REQUEST_METHOD'];
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Parse JSON input for POST/PUT requests
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo);
            break;
            
        case 'POST':
            handlePostRequest($pdo, $input);
            break;
            
        case 'PUT':
            handlePutRequest($pdo, $input);
            break;
            
        case 'DELETE':
            handleDeleteRequest($pdo);
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

function handleGetRequest($pdo) {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : "1=1";
    $orderby = isset($_GET['orderby']) ? preg_replace('/[^a-zA-Z0-9_\s]/', '', $_GET['orderby']) : "TransportName";
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    
    // Check if requesting a specific transport by ID
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM transports WHERE TransportID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Transport not found']);
        }
        return;
    }
    
    // Get all transports with optional filtering
    try {
        $sql = "SELECT * FROM transports";
        $params = [];
        
        if ($filter !== "1=1") {
            $sql .= " WHERE " . $filter;
        }
        
        $sql .= " ORDER BY " . $orderby . " LIMIT " . $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $results]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error fetching transports', 'message' => $e->getMessage()]);
    }
}

function handlePostRequest($pdo, $input) {
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'No data provided']);
        return;
    }
    
    // Validate required fields
    if (empty($input['TransportName']) || empty($input['VehicleNo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Transport Name and Vehicle No are required']);
        return;
    }
    
    try {
        // Check if transport with same name or vehicle number already exists
        $checkSql = "SELECT COUNT(*) FROM transports WHERE TransportName = ? OR VehicleNo = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$input['TransportName'], $input['VehicleNo']]);
        $count = $checkStmt->fetchColumn();
        
        if ($count > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Transport with same name or vehicle number already exists']);
            return;
        }
        
        // Insert new transport
        $sql = "INSERT INTO transports (TransportName, VehicleNo, DriverName) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['TransportName'],
            $input['VehicleNo'],
            $input['DriverName'] ?? null
        ]);
        
        $transportId = $pdo->lastInsertId();
        
        // Return the created transport
        $selectSql = "SELECT * FROM transports WHERE TransportID = ?";
        $selectStmt = $pdo->prepare($selectSql);
        $selectStmt->execute([$transportId]);
        $newTransport = $selectStmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Transport created successfully',
            'data' => $newTransport
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error creating transport', 'message' => $e->getMessage()]);
    }
}

function handlePutRequest($pdo, $input) {
    if (!$input || !isset($input['TransportID'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Transport ID is required for update']);
        return;
    }
    
    $transportId = intval($input['TransportID']);
    
    // Validate required fields
    if (empty($input['TransportName']) || empty($input['VehicleNo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Transport Name and Vehicle No are required']);
        return;
    }
    
    try {
        // Check if transport exists
        $checkSql = "SELECT COUNT(*) FROM transports WHERE TransportID = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$transportId]);
        $exists = $checkStmt->fetchColumn();
        
        if ($exists == 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Transport not found']);
            return;
        }
        
        // Check for duplicate names/vehicle numbers (excluding current record)
        $duplicateSql = "SELECT COUNT(*) FROM transports WHERE (TransportName = ? OR VehicleNo = ?) AND TransportID != ?";
        $duplicateStmt = $pdo->prepare($duplicateSql);
        $duplicateStmt->execute([$input['TransportName'], $input['VehicleNo'], $transportId]);
        $duplicateCount = $duplicateStmt->fetchColumn();
        
        if ($duplicateCount > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Transport with same name or vehicle number already exists']);
            return;
        }
        
        // Update transport
        $sql = "UPDATE transports SET TransportName = ?, VehicleNo = ?, DriverName = ?, updated_at = CURRENT_TIMESTAMP WHERE TransportID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['TransportName'],
            $input['VehicleNo'],
            $input['DriverName'] ?? null,
            $transportId
        ]);
        
        // Return updated transport
        $selectSql = "SELECT * FROM transports WHERE TransportID = ?";
        $selectStmt = $pdo->prepare($selectSql);
        $selectStmt->execute([$transportId]);
        $updatedTransport = $selectStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Transport updated successfully',
            'data' => $updatedTransport
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error updating transport', 'message' => $e->getMessage()]);
    }
}

function handleDeleteRequest($pdo) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Transport ID is required for deletion']);
        return;
    }
    
    $transportId = intval($_GET['id']);
    
    try {
        // Check if transport exists
        $checkSql = "SELECT * FROM transports WHERE TransportID = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$transportId]);
        $transport = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transport) {
            http_response_code(404);
            echo json_encode(['error' => 'Transport not found']);
            return;
        }
        
        // Delete transport
        $sql = "DELETE FROM transports WHERE TransportID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$transportId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Transport deleted successfully',
            'data' => $transport
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error deleting transport', 'message' => $e->getMessage()]);
    }
}
?>