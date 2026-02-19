<?php
// Direct test for qrysalereport method
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include CodeIgniter files to test the method
define('BASEPATH', true);
include_once 'application/controllers/Apis.php';

echo "Testing qrysalereport method directly...\n";

try {
    // Create a mock class to test the method
    class TestApis extends Apis {
        public function __construct() {
            // Mock the checkToken method
        }
        
        public function checkToken() {
            return true; // Always return true for testing
        }
        
        public function get($key) {
            // Mock GET parameters
            switch($key) {
                case 'filter': return '1 = 1';
                case 'orderby': return 'Date';
                case 'flds': return 'Date,InvoiceID,CustomerID,CustomerName,ProductName,Qty,SPrice,Amount';
                default: return null;
            }
        }
        
        public function response($data) {
            echo "Response data:\n";
            print_r($data);
        }
        
        public function load->database() {
            // Mock database loading
        }
    }
    
    $test = new TestApis();
    $test->qrysalereport_get();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
?>