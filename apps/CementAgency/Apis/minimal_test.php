<?php
// Minimal test to debug voucher saving
$conn = new mysqli('localhost', 'root', '', 'db_cement');

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . PHP_EOL;
    exit;
}

echo "=== MINIMAL VOUCHER INSERT TEST ===" . PHP_EOL;

// Test with absolute minimal data - only required fields
$testData = [
    'VoucherID' => 100000010, // Use a high ID to avoid conflicts
    'CustomerID' => 1,
    'Date' => '2026-02-17',
    'Description' => 'Minimal Test',
    'Debit' => 100
];

$fields = implode(', ', array_keys($testData));
$placeholders = str_repeat('?,', count($testData) - 1) . '?';
$values = array_values($testData);

$sql = "INSERT INTO vouchers ($fields) VALUES ($placeholders)";

echo "SQL: $sql" . PHP_EOL;
echo "Values: " . json_encode($values) . PHP_EOL;

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('sissi', $values[0], $values[1], $values[2], $values[3], $values[4]);
    
    if ($stmt->execute()) {
        echo "✅ SUCCESS! Voucher inserted with ID: " . $testData['VoucherID'] . PHP_EOL;
        echo "Affected rows: " . $stmt->affected_rows . PHP_EOL;
        
        // Verify it's in the database
        $check = $conn->query("SELECT * FROM vouchers WHERE VoucherID = " . $testData['VoucherID']);
        if ($check && $check->num_rows > 0) {
            echo "✅ Verification: Record found in database" . PHP_EOL;
            $row = $check->fetch_assoc();
            echo "Saved data: " . json_encode($row) . PHP_EOL;
        } else {
            echo "❌ Verification: Record NOT found in database" . PHP_EOL;
        }
    } else {
        echo "❌ FAILED: " . $stmt->error . PHP_EOL;
    }
    $stmt->close();
} else {
    echo "❌ Prepare failed: " . $conn->error . PHP_EOL;
}

// Check final count
$result = $conn->query("SELECT COUNT(*) as count FROM vouchers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "\nFinal total vouchers: " . $row['count'] . PHP_EOL;
}

$conn->close();
?>