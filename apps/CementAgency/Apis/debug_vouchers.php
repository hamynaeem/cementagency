<?php
// Database test script to debug voucher table issues
require_once 'application/config/database.php';

// Function to test database connection
function testDatabaseConnection() {
    global $db;
    
    try {
        $connection = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
        
        if ($connection->connect_error) {
            echo "Database connection failed: " . $connection->connect_error . "\n";
            return false;
        }
        
        echo "✓ Database connection successful\n";
        return $connection;
    } catch (Exception $e) {
        echo "Database connection error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to check vouchers table structure
function checkVouchersTable($connection) {
    try {
        $result = $connection->query("DESCRIBE vouchers");
        
        if (!$result) {
            echo "❌ Error describing vouchers table: " . $connection->error . "\n";
            return false;
        }
        
        echo "✓ Vouchers table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['Field']}: {$row['Type']} " . ($row['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . "\n";
        }
        
        return true;
    } catch (Exception $e) {
        echo "❌ Error checking vouchers table: " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to test a sample voucher insert
function testVoucherInsert($connection) {
    try {
        // Test data
        $testData = [
            'Date' => date('Y-m-d'),
            'CustomerID' => 1,
            'Description' => 'Test voucher',
            'Debit' => 100,
            'Credit' => 0,
            'RefID' => 0,
            'RefType' => 1,
            'FinYearID' => 0,
            'IsPosted' => 0,
            'BusinessID' => 1,
            'AcctType' => '1'
        ];
        
        // Get next VoucherID
        $result = $connection->query("SELECT COALESCE(MAX(VoucherID), 0) + 1 as next_id FROM vouchers");
        if ($result) {
            $row = $result->fetch_assoc();
            $testData['VoucherID'] = $row['next_id'];
        }
        
        // Build insert query
        $fields = implode(', ', array_keys($testData));
        $values = "'" . implode("', '", array_values($testData)) . "'";
        $sql = "INSERT INTO vouchers ($fields) VALUES ($values)";
        
        echo "Testing voucher insert with SQL: $sql\n";
        
        if ($connection->query($sql)) {
            echo "✓ Test voucher insert successful (ID: {$testData['VoucherID']})\n";
            
            // Clean up - delete the test record
            $connection->query("DELETE FROM vouchers WHERE VoucherID = {$testData['VoucherID']}");
            echo "✓ Test record cleaned up\n";
            
            return true;
        } else {
            echo "❌ Test voucher insert failed: " . $connection->error . "\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "❌ Error testing voucher insert: " . $e->getMessage() . "\n";
        return false;
    }
}

// Main execution
echo "=== Voucher Database Debug Script ===\n\n";

$connection = testDatabaseConnection();
if ($connection) {
    checkVouchersTable($connection);
    echo "\n";
    testVoucherInsert($connection);
    $connection->close();
} else {
    echo "❌ Cannot proceed without database connection\n";
}

echo "\n=== Debug Complete ===\n";
?>