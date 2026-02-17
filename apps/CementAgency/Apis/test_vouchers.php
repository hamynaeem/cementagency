<?php
// Simple database test for vouchers table
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_cement';

try {
    $connection = new mysqli($host, $username, $password, $database);
    
    if ($connection->connect_error) {
        echo "Connection failed: " . $connection->connect_error . "\n";
        exit;
    }
    
    echo "✓ Database connection successful\n\n";
    
    // Check vouchers table structure
    $result = $connection->query("DESCRIBE vouchers");
    if ($result) {
        echo "✓ Vouchers table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['Field']}: {$row['Type']} " . 
                 ($row['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . 
                 ($row['Key'] === 'PRI' ? ' PRIMARY KEY' : '') . "\n";
        }
    } else {
        echo "❌ Error describing vouchers table: " . $connection->error . "\n";
    }
    
    echo "\n";
    
    // Test simple select
    $result = $connection->query("SELECT COUNT(*) as count FROM vouchers");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ Current vouchers count: " . $row['count'] . "\n";
    }
    
    // Test getting next VoucherID
    $result = $connection->query("SELECT COALESCE(MAX(VoucherID), 0) + 1 as next_id FROM vouchers");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ Next VoucherID would be: " . $row['next_id'] . "\n";
    }
    
    $connection->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>