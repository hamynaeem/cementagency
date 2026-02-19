<?php
// Direct test of CodeIgniter controller
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Direct CodeIgniter Test</h2>";

// Try to load CodeIgniter manually
define('ENVIRONMENT', 'development');

try {
    // Set up paths
    $system_path = 'system';
    $application_folder = 'application';
    
    if (realpath($system_path) !== FALSE) {
        $system_path = realpath($system_path).'/';
    }
    
    $system_path = rtrim($system_path, '/').'/';
    
    if (!is_dir($system_path)) {
        echo "<p style='color: red;'>❌ System folder path does not appear to be set correctly: {$system_path}</p>";
        exit;
    }
    
    define('BASEPATH', $system_path);
    define('APPPATH', $application_folder.'/');
    define('VIEWPATH', $application_folder.'/views/');
    define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
    define('FCPATH', dirname(__FILE__).'/');
    define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));
    
    echo "<p style='color: green;'>✅ CodeIgniter paths defined</p>";
    
    // Mock the required environment for the test
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/apis/test';
    $_SERVER['HTTP_HOST'] = 'localhost:8000';
    
    // Include the CodeIgniter bootstrap
    require_once BASEPATH.'core/CodeIgniter.php';
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading CodeIgniter: " . $e->getMessage() . "</p>";
    
    // Let's just test database connection directly
    echo "<h3>Testing database directly:</h3>";
    
    try {
        require_once 'application/config/database.php';
        
        $dbPath = $db['default']['database'];
        echo "<p>Database path: {$dbPath}</p>";
        
        if (file_exists($dbPath)) {
            $pdo = new PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<p style='color: green;'>✅ Direct SQLite connection successful</p>";
            
            // Test booking query
            $sql = "SELECT COUNT(*) as count FROM booking";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ Booking table accessible, count: " . $result['count'] . "</p>";
            
            // Test with filter like the API does
            $stmt2 = $pdo->prepare("SELECT * FROM booking WHERE Date BETWEEN '2025-09-23' AND '2025-09-23' LIMIT 5");
            $stmt2->execute();
            $bookings = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p style='color: green;'>✅ Booking query with date filter successful, results: " . count($bookings) . "</p>";
            
            if (count($bookings) > 0) {
                echo "<pre>" . print_r($bookings[0], true) . "</pre>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Database file not found: {$dbPath}</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    }
}
?>