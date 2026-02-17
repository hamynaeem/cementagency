<?php
$conn = new mysqli('localhost', 'root', '', 'db_cement');

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . PHP_EOL;
    exit;
}

echo "Checking vouchers table..." . PHP_EOL;

// Get count of vouchers
$result = $conn->query("SELECT COUNT(*) as count FROM vouchers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total vouchers: " . $row['count'] . PHP_EOL;
}

// Get recent vouchers
$result = $conn->query("SELECT VoucherID, Date, CustomerID, Description, Debit, Credit FROM vouchers ORDER BY VoucherID DESC LIMIT 5");
if ($result) {
    echo "\nRecent vouchers:" . PHP_EOL;
    while ($row = $result->fetch_assoc()) {
        echo "ID={$row['VoucherID']}, Date={$row['Date']}, Desc={$row['Description']}, Debit={$row['Debit']}" . PHP_EOL;
    }
} else {
    echo "Error: " . $conn->error . PHP_EOL;
}

// Check what the next VoucherID would be
$result = $conn->query("SELECT COALESCE(MAX(VoucherID), 0) + 1 as next_id FROM vouchers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "\nNext VoucherID would be: " . $row['next_id'] . PHP_EOL;
}

$conn->close();
?>