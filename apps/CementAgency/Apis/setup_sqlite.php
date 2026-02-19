<?php
// SQLite Database Setup Script
$dbPath = __DIR__ . '/sqlite3/db_cement.db';
$dbDir = dirname($dbPath);

echo "<h2>SQLite Database Setup</h2>";

// Create directory if it doesn't exist
if (!is_dir($dbDir)) {
    if (mkdir($dbDir, 0777, true)) {
        echo "<p style='color: green;'>✅ Created SQLite directory: {$dbDir}</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create SQLite directory</p>";
        exit;
    }
}

try {
    // Create/connect to SQLite database
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ SQLite database connected/created: {$dbPath}</p>";
    
    // Create vouchers table
    $createVouchersTable = "
        CREATE TABLE IF NOT EXISTS vouchers (
            VoucherID INTEGER PRIMARY KEY AUTOINCREMENT,
            Date TEXT DEFAULT NULL,
            CustomerID INTEGER DEFAULT NULL,
            Description TEXT DEFAULT NULL,
            Debit REAL DEFAULT 0.00,
            Credit REAL DEFAULT 0.00,
            RefID INTEGER DEFAULT 0,
            RefType INTEGER DEFAULT 1,
            FinYearID INTEGER DEFAULT 0,
            IsPosted INTEGER DEFAULT 0,
            AcctType INTEGER DEFAULT NULL,
            BusinessID INTEGER DEFAULT 1
        )
    ";
    
    $pdo->exec($createVouchersTable);
    echo "<p style='color: green;'>✅ Vouchers table created successfully</p>";
    
    // Create other essential tables
    $createCategoriesTable = "
        CREATE TABLE IF NOT EXISTS categories (
            CatID INTEGER PRIMARY KEY AUTOINCREMENT,
            CatName TEXT NOT NULL,
            Description TEXT
        )
    ";
    
    $pdo->exec($createCategoriesTable);
    echo "<p style='color: green;'>✅ Categories table created successfully</p>";
    
    $createProductsTable = "
        CREATE TABLE IF NOT EXISTS products (
            ProductID INTEGER PRIMARY KEY AUTOINCREMENT,
            ProductName TEXT NOT NULL,
            Category INTEGER,
            PPrice REAL DEFAULT 0,
            SPrice REAL DEFAULT 0,
            Status INTEGER DEFAULT 1
        )
    ";
    
    $pdo->exec($createProductsTable);
    echo "<p style='color: green;'>✅ Products table created successfully</p>";
    
    // Create customers table
    $createCustomersTable = "
        CREATE TABLE IF NOT EXISTS customers (
            CustomerID INTEGER PRIMARY KEY AUTOINCREMENT,
            CustomerName TEXT NOT NULL,
            Address TEXT,
            Phone TEXT,
            Email TEXT,
            Balance REAL DEFAULT 0
        )
    ";
    
    $pdo->exec($createCustomersTable);
    echo "<p style='color: green;'>✅ Customers table created successfully</p>";
    
    // Create booking table
    $createBookingTable = "
        CREATE TABLE IF NOT EXISTS booking (
            BookingID INTEGER PRIMARY KEY AUTOINCREMENT,
            Date TEXT DEFAULT NULL,
            CustomerID INTEGER DEFAULT NULL,
            InvoiceNo TEXT,
            VehicleNo TEXT,
            BuiltyNo TEXT,
            Amount REAL DEFAULT 0.00,
            Discount REAL DEFAULT 0.00,
            Carriage REAL DEFAULT 0.00,
            NetAmount REAL DEFAULT 0.00,
            IsPosted INTEGER DEFAULT 0,
            BusinessID INTEGER DEFAULT 1
        )
    ";
    
    $pdo->exec($createBookingTable);
    echo "<p style='color: green;'>✅ Booking table created successfully</p>";
    
    // Create expend table
    $createExpendTable = "
        CREATE TABLE IF NOT EXISTS expend (
            ExpendID INTEGER PRIMARY KEY AUTOINCREMENT,
            Date TEXT DEFAULT NULL,
            CustomerID INTEGER DEFAULT NULL,
            Description TEXT,
            Amount REAL DEFAULT 0.00,
            Type INTEGER DEFAULT 1,
            IsPosted INTEGER DEFAULT 0,
            BusinessID INTEGER DEFAULT 1
        )
    ";
    
    $pdo->exec($createExpendTable);
    echo "<p style='color: green;'>✅ Expend table created successfully</p>";
    
    // Insert sample data
    $insertCategories = "
        INSERT OR IGNORE INTO categories (CatID, CatName, Description) VALUES
        (1, 'Cement', 'Cement products'),
        (2, 'Steel', 'Steel products'),
        (3, 'Construction', 'Construction materials')
    ";
    $pdo->exec($insertCategories);
    
    $insertProducts = "
        INSERT OR IGNORE INTO products (ProductID, ProductName, Category, PPrice, SPrice) VALUES
        (1, 'Cement Bag 40kg', 1, 800, 900),
        (2, 'Cement Bag 25kg', 1, 500, 600),
        (3, 'Steel Rod 12mm', 2, 1200, 1350),
        (4, 'Steel Rod 16mm', 2, 1800, 2000),
        (5, 'Concrete Mix', 3, 300, 350)
    ";
    $pdo->exec($insertProducts);
    
    $insertCustomers = "
        INSERT OR IGNORE INTO customers (CustomerID, CustomerName, Address, Phone, Balance) VALUES
        (1, 'ABC Company Ltd', '123 Main Street, Lahore', '042-111-222', 15000.00),
        (2, 'XYZ Suppliers', '456 Market Road, Karachi', '021-333-444', -5000.00),
        (3, 'DEF Trading', '789 Business Center, Islamabad', '051-555-666', 0.00),
        (4, 'GHI Construction', '321 Industrial Area, Faisalabad', '041-777-888', 25000.00)
    ";
    $pdo->exec($insertCustomers);
    
    $insertBookings = "
        INSERT OR IGNORE INTO booking (BookingID, Date, CustomerID, InvoiceNo, VehicleNo, BuiltyNo, Amount, Discount, Carriage, NetAmount, IsPosted) VALUES
        (1, '2025-09-23', 1, 'INV-001', 'LHR-1234', 'BLT-001', 50000.00, 2000.00, 1500.00, 49500.00, 0),
        (2, '2025-09-22', 2, 'INV-002', 'ISB-5678', 'BLT-002', 75000.00, 3000.00, 2000.00, 74000.00, 1)
    ";
    $pdo->exec($insertBookings);
    
    $insertExpenses = "
        INSERT OR IGNORE INTO expend (ExpendID, Date, CustomerID, Description, Amount, Type, IsPosted) VALUES
        (1, '2025-09-23', 1, 'Vehicle fuel expense', 8000.00, 1, 0),
        (2, '2025-09-22', 2, 'Vehicle maintenance', 15000.00, 1, 1),
        (3, '2025-09-21', 3, 'Office supplies', 3000.00, 2, 0)
    ";
    $pdo->exec($insertExpenses);
    
    $insertVouchers = "
        INSERT OR IGNORE INTO vouchers (VoucherID, Date, CustomerID, Description, Debit, Credit, IsPosted) VALUES
        (1, '2025-09-23', 1, 'Payment received from ABC Company', 0.00, 15000.00, 0),
        (2, '2025-09-23', 2, 'Payment made to XYZ Suppliers', 8000.00, 0.00, 1),
        (3, '2025-09-22', 3, 'Cash sale to DEF Trading', 0.00, 12000.00, 0),
        (4, '2025-09-21', 4, 'Advance payment to GHI Construction', 5000.00, 0.00, 1)
    ";
    $pdo->exec($insertVouchers);
    
    echo "<p style='color: green;'>✅ Sample data inserted successfully</p>";
    
    echo "<h3>✅ SQLite setup complete!</h3>";
    echo "<p><strong>To use SQLite instead of MySQL:</strong></p>";
    echo "<ol>";
    echo "<li>Backup your current database.php: <code>cp application/config/database.php application/config/database_mysql_backup.php</code></li>";
    echo "<li>Copy SQLite config: <code>cp application/config/database_sqlite.php application/config/database.php</code></li>";
    echo "<li>Restart your PHP server</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ SQLite setup failed: " . $e->getMessage() . "</p>";
}

echo "<br><a href='test_db_connection.php'>← Test Database Connection</a>";
echo " | <a href='index.php'>← Back to API</a>";
?>