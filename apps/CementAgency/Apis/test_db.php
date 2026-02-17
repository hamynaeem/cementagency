<?php
// Simple database connection test
echo "Testing database connection...\n";

try {
    // Load CodeIgniter configuration
    $config_path = __DIR__ . '/application/config/database.php';
    if (file_exists($config_path)) {
        include $config_path;
        
        if (isset($db['default'])) {
            $db_config = $db['default'];
            echo "Database config loaded successfully\n";
            
            // Test connection
            $pdo = new PDO(
                "mysql:host={$db_config['hostname']};dbname={$db_config['database']}", 
                $db_config['username'], 
                $db_config['password']
            );
            echo "Database connection successful!\n";
            
            // Test vouchers table existence and structure
            $stmt = $pdo->query("SHOW COLUMNS FROM vouchers");
            if ($stmt) {
                echo "\nVouchers table columns:\n";
                while ($row = $stmt->fetch()) {
                    echo "- {$row['Field']} ({$row['Type']})\n";
                }
            } else {
                echo "Vouchers table not found!\n";
            }
            
            // Test sample data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM vouchers LIMIT 1");
            if ($stmt) {
                $row = $stmt->fetch();
                echo "\nVouchers table record count: {$row['count']}\n";
            }
            
        } else {
            echo "Database configuration not found in config file\n";
        }
    } else {
        echo "Database config file not found: $config_path\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>