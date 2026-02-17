<?php
$conn = new mysqli('localhost', 'root', '', 'db_cement');

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . PHP_EOL;
    exit;
}

echo "=== DATABASE STRUCTURE ANALYSIS ===" . PHP_EOL;

// Check for triggers
echo "\n1. Checking for triggers on vouchers table:" . PHP_EOL;
$result = $conn->query("SHOW TRIGGERS LIKE 'vouchers'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Trigger: {$row['Trigger']}, Event: {$row['Event']}, Timing: {$row['Timing']}" . PHP_EOL;
        echo "Statement: {$row['Statement']}" . PHP_EOL . PHP_EOL;
    }
} else {
    echo "No triggers found on vouchers table" . PHP_EOL;
}

// Check table constraints  
echo "\n2. Checking table constraints:" . PHP_EOL;
$result = $conn->query("SHOW CREATE TABLE vouchers");
if ($result) {
    $row = $result->fetch_assoc();
    echo $row['Create Table'] . PHP_EOL;
}

// Try very simple insert without VoucherID (let auto-increment handle it)
echo "\n3. Testing simple insert without explicit VoucherID:" . PHP_EOL;
$sql = "INSERT INTO vouchers (CustomerID, Date, Description, Debit) VALUES (1, '2026-02-17', 'Simple Test', 50)";
echo "SQL: $sql" . PHP_EOL;

if ($conn->query($sql)) {
    echo "✅ SUCCESS! Insert ID: " . $conn->insert_id . PHP_EOL;
    
    // Check the record
    $check = $conn->query("SELECT * FROM vouchers WHERE VoucherID = " . $conn->insert_id);
    if ($check && $check->num_rows > 0) {
        echo "✅ Record verified in database" . PHP_EOL;
        $row = $check->fetch_assoc();
        echo "Data: " . json_encode($row) . PHP_EOL;
    }
} else {
    echo "❌ FAILED: " . $conn->error . PHP_EOL;
}

// Final count
$result = $conn->query("SELECT COUNT(*) as count FROM vouchers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "\nFinal voucher count: " . $row['count'] . PHP_EOL;
}

$conn->close();
?>