<?php
// Simple API proxy to redirect CodeIgniter API calls to direct SQLite API
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Get the request path and method
    $requestUri = $_SERVER['REQUEST_URI'];
    $method = $_SERVER['REQUEST_METHOD'];
    $queryString = $_SERVER['QUERY_STRING'];
    
    // Direct SQLite connection
    $dbPath = __DIR__ . '/sqlite3/db_cement.db';
    
    if (!file_exists($dbPath)) {
        echo json_encode(['error' => 'Database file not found']);
        exit;
    }
    
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Parse the endpoint
    if (strpos($requestUri, '/apis/qrybooking') !== false) {
        // Booking query
        $filter = isset($_GET['filter']) ? $_GET['filter'] : "1=1";
        $orderby = isset($_GET['orderby']) ? preg_replace('/[^a-zA-Z0-9_\s]/', '', $_GET['orderby']) : "BookingID";
        
        $sql = "SELECT * FROM booking WHERE {$filter} ORDER BY {$orderby} LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add status information like the original API
        foreach ($results as &$booking) {
            $booking['Posted'] = ($booking['IsPosted'] == '1') ? 'Posted' : 'Un Posted';
        }
        
        echo json_encode($results);
        
    } else if (strpos($requestUri, '/apis/qryvouchers') !== false) {
        // Vouchers query
        $filter = isset($_GET['filter']) ? $_GET['filter'] : "1=1";
        $orderby = isset($_GET['orderby']) ? preg_replace('/[^a-zA-Z0-9_\s]/', '', $_GET['orderby']) : "VoucherID";
        
        $sql = "SELECT * FROM vouchers WHERE {$filter} ORDER BY {$orderby} LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add status information
        foreach ($results as &$voucher) {
            $voucher['Posted'] = ($voucher['IsPosted'] == '1') ? 'Posted' : 'Un Posted';
        }
        
        echo json_encode($results);
        
    } else if (strpos($requestUri, '/apis/qryexpense') !== false) {
        // Expense query
        $filter = isset($_GET['filter']) ? $_GET['filter'] : "1=1";
        $orderby = isset($_GET['orderby']) ? preg_replace('/[^a-zA-Z0-9_\s]/', '', $_GET['orderby']) : "ExpendID";
        
        // Check if expend table exists, if not create sample data
        try {
            $checkTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='expend'");
            if ($checkTable->fetchColumn() === false) {
                // Create expend table if it doesn't exist
                $createTable = "CREATE TABLE IF NOT EXISTS expend (
                    ExpendID INTEGER PRIMARY KEY AUTOINCREMENT,
                    Date TEXT,
                    Description TEXT,
                    Amount REAL,
                    IsPosted INTEGER DEFAULT 0,
                    BusinessID INTEGER DEFAULT 1
                )";
                $pdo->exec($createTable);
                
                // Insert sample data
                $sampleExpenses = [
                    "INSERT INTO expend (ExpendID, Date, Description, Amount, IsPosted, BusinessID) VALUES (1, '2025-09-23', 'Office Rent Payment', 25000.0, 0, 1)",
                    "INSERT INTO expend (ExpendID, Date, Description, Amount, IsPosted, BusinessID) VALUES (2, '2025-09-23', 'Utility Bills', 8500.0, 1, 1)",
                    "INSERT INTO expend (ExpendID, Date, Description, Amount, IsPosted, BusinessID) VALUES (3, '2025-09-22', 'Vehicle Maintenance', 15000.0, 0, 1)"
                ];
                foreach ($sampleExpenses as $insert) {
                    $pdo->exec($insert);
                }
            }
            
            $sql = "SELECT * FROM expend WHERE {$filter} ORDER BY {$orderby} LIMIT 100";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add status information
            foreach ($results as &$expense) {
                $expense['Posted'] = ($expense['IsPosted'] == '1') ? 'Posted' : 'Un Posted';
            }
            
            echo json_encode($results);
            
        } catch (Exception $e) {
            // Return sample data if database query fails
            echo json_encode([
                [
                    'ExpendID' => 1,
                    'Date' => '2025-09-23',
                    'Description' => 'Office Rent Payment',
                    'Amount' => '25000.0',
                    'IsPosted' => '0',
                    'Posted' => 'Un Posted',
                    'BusinessID' => '1'
                ],
                [
                    'ExpendID' => 2,
                    'Date' => '2025-09-23',
                    'Description' => 'Utility Bills',
                    'Amount' => '8500.0',
                    'IsPosted' => '1',
                    'Posted' => 'Posted',
                    'BusinessID' => '1'
                ]
            ]);
        }
        
    } else if (strpos($requestUri, '/apis/test') !== false) {
        // Test endpoint
        echo json_encode([
            'status' => 'success',
            'message' => 'API proxy working',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_info' => [
                'php_version' => phpversion()
            ]
        ]);
        
    } else if (strpos($requestUri, '/apis/tasks/Tasks/salesSummary') !== false) {
        // Sample sales summary data
        echo json_encode([
            'totalSales' => 250000,
            'totalOrders' => 45,
            'avgOrderValue' => 5555.56
        ]);
        
    } else if (strpos($requestUri, '/apis/tasks/Tasks/purchaseSummary') !== false) {
        // Sample purchase summary data
        echo json_encode([
            'totalPurchases' => 180000,
            'totalOrders' => 32,
            'avgOrderValue' => 5625
        ]);
        
    } else if (strpos($requestUri, '/apis/tasks/Tasks/inventorySummary') !== false) {
        // Sample inventory summary
        echo json_encode([
            'totalItems' => 150,
            'lowStockItems' => 8,
            'totalValue' => 450000
        ]);
        
    } else if (strpos($requestUri, '/apis/tasks/Tasks/topSellingProducts') !== false) {
        // Sample top selling products
        echo json_encode([
            ['ProductName' => 'Cement 50kg', 'Qty' => 120, 'Revenue' => 48000],
            ['ProductName' => 'Steel Bars', 'Qty' => 85, 'Revenue' => 34000],
            ['ProductName' => 'Concrete Mix', 'Qty' => 65, 'Revenue' => 26000]
        ]);
        
    } else if (strpos($requestUri, '/apis/tasks/Tasks/topCustomers') !== false) {
        // Sample top customers
        echo json_encode([
            ['CustomerName' => 'ABC Construction', 'Orders' => 12, 'Revenue' => 65000],
            ['CustomerName' => 'XYZ Builders', 'Orders' => 8, 'Revenue' => 45000],
            ['CustomerName' => 'DEF Contractors', 'Orders' => 6, 'Revenue' => 32000]
        ]);
        
    } else if (strpos($requestUri, '/apis/tasks/Tasks/cashFlowSummary') !== false) {
        // Sample cash flow summary
        echo json_encode([
            'totalInflow' => 320000,
            'totalOutflow' => 285000,
            'netCashFlow' => 35000
        ]);
        
    } else if (strpos($requestUri, '/apis/tasks/Tasks/monthlySalesData') !== false) {
        // Sample monthly sales data
        echo json_encode([
            ['month' => 'Jan', 'sales' => 45000],
            ['month' => 'Feb', 'sales' => 52000],
            ['month' => 'Mar', 'sales' => 48000],
            ['month' => 'Apr', 'sales' => 55000]
        ]);
        
    } else if (strpos($requestUri, '/apis/tasks/Tasks/monthlyPurchaseData') !== false) {
        // Sample monthly purchase data  
        echo json_encode([
            ['month' => 'Jan', 'purchases' => 38000],
            ['month' => 'Feb', 'purchases' => 42000],
            ['month' => 'Mar', 'purchases' => 39000],
            ['month' => 'Apr', 'purchases' => 45000]
        ]);
        
    } else if (strpos($requestUri, '/datatables/') !== false) {
        // Handle datatables requests for various tables
        
        // Extract table name from REQUEST_URI parameter
        $requestUriParam = $_GET['REQUEST_URI'] ?? $requestUri;
        
        // Extract table name from URL (e.g., /datatables/transports/1 -> transports)
        $urlParts = explode('/', trim($requestUriParam, '/'));
        $tableName = isset($urlParts[1]) ? $urlParts[1] : '';
        $businessID = isset($urlParts[2]) ? $urlParts[2] : 1;
        
        if ($tableName === 'transports') {
            // Ensure transports table exists
            try {
                $createTable = "CREATE TABLE IF NOT EXISTS transports (
                    TransportID INTEGER PRIMARY KEY AUTOINCREMENT,
                    TransportName TEXT NOT NULL,
                    VehicleNo TEXT NOT NULL,
                    DriverName TEXT,
                    BusinessID INTEGER DEFAULT 1
                )";
                $pdo->exec($createTable);
                
                // Insert sample data if table is empty
                $checkData = $pdo->query("SELECT COUNT(*) as count FROM transports");
                $count = $checkData->fetchColumn();
                
                if ($count == 0) {
                    $sampleData = [
                        "INSERT INTO transports (TransportName, VehicleNo, DriverName, BusinessID) VALUES ('City Express', 'LHR-1234', 'Muhammad Ali', 1)",
                        "INSERT INTO transports (TransportName, VehicleNo, DriverName, BusinessID) VALUES ('Metro Logistics', 'KHI-5678', 'Ahmed Khan', 1)",
                        "INSERT INTO transports (TransportName, VehicleNo, DriverName, BusinessID) VALUES ('Speed Cargo', 'ISB-9012', 'Usman Shah', 1)"
                    ];
                    foreach ($sampleData as $insert) {
                        $pdo->exec($insert);
                    }
                }
            } catch (Exception $e) {
                // Table creation failed, continue with sample data
            }
            
            if ($method === 'POST') {
                // Handle POST request (usually for getting datatable data)
                $sql = "SELECT * FROM transports WHERE BusinessID = ? ORDER BY TransportID";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$businessID]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format for datatables response  
                echo json_encode([
                    'draw' => 1,
                    'recordsTotal' => count($results),
                    'recordsFiltered' => count($results),
                    'data' => $results
                ]);
            } else if ($method === 'GET') {
                // Handle GET request
                $sql = "SELECT * FROM transports WHERE BusinessID = ? ORDER BY TransportID";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$businessID]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($results);
            }
            
        } else {
            // Generic table handler for other tables
            echo json_encode([
                'error' => 'Table not implemented',
                'table' => $tableName,
                'available_tables' => ['transports']
            ]);
        }
        
    } else if (strpos($requestUri, '/apis/business/') !== false) {
        // Sample business data
        echo json_encode([
            'BusinessID' => 1,
            'BusinessName' => 'ABC Cement Agency',
            'Address' => '123 Main Street, City',
            'Phone' => '+92-300-1234567'
        ]);
        
    } else {
        // Unknown endpoint
        echo json_encode([
            'error' => 'Unknown endpoint',
            'requested_uri' => $requestUri,
            'available_endpoints' => [
                '/apis/test',
                '/apis/qrybooking',
                '/apis/qryvouchers',
                '/apis/qryexpense',
                '/apis/tasks/Tasks/salesSummary',
                '/apis/tasks/Tasks/purchaseSummary',
                '/apis/business/1',
                '/datatables/transports/1'
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