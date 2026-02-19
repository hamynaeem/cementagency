<?php
// Debug script to check qrysalereport table
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Try SQLite first
    if (file_exists('database.db')) {
        $pdo = new PDO('sqlite:database.db');
        echo "Connected to SQLite database\n";
        
        // Check if qrysalereport table exists
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='qrysalereport'");
        $result = $stmt->fetchAll();
        
        if (count($result) > 0) {
            echo "qrysalereport table exists\n";
            
            // Show table structure
            $stmt = $pdo->query("PRAGMA table_info(qrysalereport)");
            $columns = $stmt->fetchAll();
            echo "Table structure:\n";
            foreach ($columns as $col) {
                echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
            }
            
            // Show sample data
            $stmt = $pdo->query("SELECT * FROM qrysalereport LIMIT 3");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\nSample data:\n";
            print_r($data);
        } else {
            echo "qrysalereport table does not exist\n";
            
            // Check for alternative tables
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND (name LIKE '%sale%' OR name LIKE '%invoice%' OR name LIKE '%customer%')");
            $tables = $stmt->fetchAll();
            echo "Related tables found:\n";
            foreach ($tables as $table) {
                echo "- " . $table['name'] . "\n";
            }
        }
    } else {
        echo "SQLite database not found\n";
        
        // Try MySQL connection
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=cement', 'root', '');
            echo "Connected to MySQL database\n";
            
            // Check if qrysalereport exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'qrysalereport'");
            $result = $stmt->fetchAll();
            
            if (count($result) > 0) {
                echo "qrysalereport table exists in MySQL\n";
                
                // Show table structure
                $stmt = $pdo->query("DESCRIBE qrysalereport");
                $columns = $stmt->fetchAll();
                echo "Table structure:\n";
                foreach ($columns as $col) {
                    echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
                }
                
                // Show sample data
                $stmt = $pdo->query("SELECT * FROM qrysalereport LIMIT 3");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "\nSample data:\n";
                print_r($data);
            } else {
                echo "qrysalereport table does not exist in MySQL\n";
                
                // Check for alternative tables
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll();
                echo "Available tables:\n";
                foreach ($tables as $table) {
                    echo "- " . $table[0] . "\n";
                }
            }
        } catch (Exception $e) {
            echo "MySQL connection failed: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>