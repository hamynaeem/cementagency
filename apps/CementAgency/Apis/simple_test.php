<?php
// Simple API test without CodeIgniter
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Simple API Test</h2>";

// Test basic PHP functionality
echo "<p style='color: green;'>✅ PHP is working (version: " . phpversion() . ")</p>";

// Test SQLite connection directly
$dbPath = __DIR__ . '/sqlite3/db_cement.db';
echo "<p>📁 SQLite database path: {$dbPath}</p>";

if (file_exists($dbPath)) {
    echo "<p style='color: green;'>✅ SQLite database file exists</p>";
    
    try {
        $pdo = new PDO("sqlite:{$dbPath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color: green;'>✅ SQLite connection successful</p>";
        
        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM vouchers");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Vouchers table accessible, record count: " . $result['count'] . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ SQLite connection error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ SQLite database file not found: {$dbPath}</p>";
}

// Test if system directory exists
$systemPath = __DIR__ . '/system';
echo "<p>📁 System directory: {$systemPath}</p>";
if (is_dir($systemPath)) {
    echo "<p style='color: green;'>✅ CodeIgniter system directory exists</p>";
} else {
    echo "<p style='color: red;'>❌ CodeIgniter system directory not found</p>";
}

// Test if application directory exists  
$appPath = __DIR__ . '/application';
echo "<p>📁 Application directory: {$appPath}</p>";
if (is_dir($appPath)) {
    echo "<p style='color: green;'>✅ CodeIgniter application directory exists</p>";
} else {
    echo "<p style='color: red;'>❌ CodeIgniter application directory not found</p>";
}
?>