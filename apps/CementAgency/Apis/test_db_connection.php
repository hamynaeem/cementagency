<?php
// Simple database connection test
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'db_cement';

echo "<h2>Database Connection Test</h2>";

// Test MySQL connection
$connection = @mysqli_connect($host, $user, $pass);
if (!$connection) {
    echo "<p style='color: red;'>❌ MySQL Connection Failed: " . mysqli_connect_error() . "</p>";
    echo "<p>💡 Suggestions:</p>";
    echo "<ul>";
    echo "<li>Start XAMPP/WAMP MySQL service</li>";
    echo "<li>Or we can switch to SQLite for development</li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✅ MySQL Connection Successful</p>";
    
    // Test if database exists
    $db_selected = @mysqli_select_db($connection, $db);
    if (!$db_selected) {
        echo "<p style='color: orange;'>⚠️ Database '{$db}' not found</p>";
        echo "<p>Creating database...</p>";
        
        $create_db = "CREATE DATABASE IF NOT EXISTS {$db}";
        if (mysqli_query($connection, $create_db)) {
            echo "<p style='color: green;'>✅ Database '{$db}' created successfully</p>";
            
            // Create vouchers table
            mysqli_select_db($connection, $db);
            $create_table = "
                CREATE TABLE IF NOT EXISTS vouchers (
                    VoucherID int(11) NOT NULL AUTO_INCREMENT,
                    Date date DEFAULT NULL,
                    CustomerID int(11) DEFAULT NULL,
                    Description varchar(255) DEFAULT NULL,
                    Debit decimal(10,2) DEFAULT 0.00,
                    Credit decimal(10,2) DEFAULT 0.00,
                    RefID int(11) DEFAULT 0,
                    RefType int(11) DEFAULT 1,
                    FinYearID int(11) DEFAULT 0,
                    IsPosted int(11) DEFAULT 0,
                    AcctType int(11) DEFAULT NULL,
                    BusinessID int(11) DEFAULT 1,
                    PRIMARY KEY (VoucherID)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ";
            
            if (mysqli_query($connection, $create_table)) {
                echo "<p style='color: green;'>✅ Vouchers table created successfully</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create vouchers table: " . mysqli_error($connection) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to create database: " . mysqli_error($connection) . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Database '{$db}' exists and selected</p>";
        
        // Check if vouchers table exists
        $check_table = "SHOW TABLES LIKE 'vouchers'";
        $result = mysqli_query($connection, $check_table);
        if (mysqli_num_rows($result) > 0) {
            echo "<p style='color: green;'>✅ Vouchers table exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Vouchers table not found, creating...</p>";
            
            $create_table = "
                CREATE TABLE IF NOT EXISTS vouchers (
                    VoucherID int(11) NOT NULL AUTO_INCREMENT,
                    Date date DEFAULT NULL,
                    CustomerID int(11) DEFAULT NULL,
                    Description varchar(255) DEFAULT NULL,
                    Debit decimal(10,2) DEFAULT 0.00,
                    Credit decimal(10,2) DEFAULT 0.00,
                    RefID int(11) DEFAULT 0,
                    RefType int(11) DEFAULT 1,
                    FinYearID int(11) DEFAULT 0,
                    IsPosted int(11) DEFAULT 0,
                    AcctType int(11) DEFAULT NULL,
                    BusinessID int(11) DEFAULT 1,
                    PRIMARY KEY (VoucherID)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ";
            
            if (mysqli_query($connection, $create_table)) {
                echo "<p style='color: green;'>✅ Vouchers table created successfully</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create vouchers table: " . mysqli_error($connection) . "</p>";
            }
        }
    }
    
    mysqli_close($connection);
}
echo "<br><a href='index.php'>← Back to API</a>";
?>