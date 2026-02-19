<?php
// Simple PHP error checker
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 PHP Backend Debug</h2>";

// Test basic PHP functionality
echo "<p style='color: green;'>✅ PHP is working (version: " . phpversion() . ")</p>";

// Test database connection
try {
    require_once __DIR__ . '/application/config/database.php';
    
    if ($db['default']['dbdriver'] === 'mysqli') {
        $connection = @mysqli_connect(
            $db['default']['hostname'],
            $db['default']['username'], 
            $db['default']['password'],
            $db['default']['database']
        );
        
        if ($connection) {
            echo "<p style='color: green;'>✅ MySQL Database connection: OK</p>";
            mysqli_close($connection);
        } else {
            echo "<p style='color: red;'>❌ MySQL Database connection failed: " . mysqli_connect_error() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Using database driver: " . $db['default']['dbdriver'] . "</p>";
        if ($db['default']['dbdriver'] === 'sqlite3') {
            $dbPath = $db['default']['database'];
            if (file_exists($dbPath)) {
                echo "<p style='color: green;'>✅ SQLite database file exists: " . $dbPath . "</p>";
            } else {
                echo "<p style='color: red;'>❌ SQLite database file not found: " . $dbPath . "</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database config error: " . $e->getMessage() . "</p>";
}

// Test if CodeIgniter is accessible 
echo "<h3>API Endpoint Tests:</h3>";

$endpoints = [
    '/index.php/apis/test',
    '/index.php/apis/qryvouchers',
    '/index.php/apis/qrybooking', 
    '/index.php/apis/qryexpense'
];

foreach ($endpoints as $endpoint) {
    $url = "http://localhost:8000" . $endpoint;
    echo "<p><strong>Testing:</strong> <a href='{$url}' target='_blank'>{$endpoint}</a></p>";
}

echo "<h3>🔄 Quick Actions:</h3>";
echo "<p><a href='setup_sqlite.php' style='background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>📀 Setup SQLite Database</a></p>";
echo "<p><a href='test_db_connection.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔗 Test MySQL Connection</a></p>";

echo "<br><p><em>Check the browser console and network tab for detailed error information.</em></p>";
?>