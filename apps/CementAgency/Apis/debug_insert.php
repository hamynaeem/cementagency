<?php
$conn = new mysqli('localhost', 'root', '', 'db_cement');

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . PHP_EOL;
    exit;
}

echo "=== DEBUGGING VOUCHER INSERT ISSUE ===" . PHP_EOL;

// Check exact table structure
echo "\n1. Vouchers table structure:" . PHP_EOL;
$result = $conn->query("DESCRIBE vouchers");
while ($row = $result->fetch_assoc()) {
    echo "  {$row['Field']}: {$row['Type']} " . 
         ($row['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . 
         ($row['Key'] === 'PRI' ? ' PRIMARY KEY' : '') .
         ($row['Default'] !== null ? " DEFAULT:{$row['Default']}" : '') . PHP_EOL;
}

// Test manual insert with exact data structure
echo "\n2. Testing manual insert..." . PHP_EOL;

$testData = [
    'VoucherID' => 100000003, // Specific ID
    'Date' => '2026-02-17',
    'AcctType' => 1,
    'CustomerID' => 1,
    'Description' => 'Manual Test Insert',
    'Debit' => 999,
    'Credit' => 0,
    'RefID' => 0,
    'RefType' => 1,
    'FinYearID' => 0,
    'IsPosted' => 0,
    'BusinessID' => 1
];

// Build insert statement manually for MySQLi (uses ? not :parameter)
$fields = implode(', ', array_keys($testData));
$placeholders = str_repeat('?,', count($testData) - 1) . '?';
$sql = "INSERT INTO vouchers ($fields) VALUES ($placeholders)";

echo "SQL: $sql" . PHP_EOL;
echo "Data: " . json_encode($testData) . PHP_EOL;

// Use prepared statement for safety
$stmt = $conn->prepare($sql);
if ($stmt) {
    // For MySQLi, we need to pass values in array order
    $values = array_values($testData);
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        echo "✅ Manual insert SUCCESS!" . PHP_EOL;
        echo "Affected rows: " . $stmt->affected_rows . PHP_EOL;
    } else {
        echo "❌ Manual insert FAILED: " . $stmt->error . PHP_EOL;
    }
    $stmt->close();
} else {
    echo "❌ Prepare failed: " . $conn->error . PHP_EOL;
}

// Check count again
$result = $conn->query("SELECT COUNT(*) as count FROM vouchers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "\n3. Total vouchers after test: " . $row['count'] . PHP_EOL;
}

$conn->close();
?>