<?php
// Direct test of qrybooking endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Direct API Endpoint Test</h2>";

// Test endpoint URL
$testUrl = 'http://localhost:8000/index.php/apis/qrybooking?orderby=BookingID%20&filter=Date%20between%20%272025-09-23%27%20and%20%272025-09-23%27%20';

echo "<p>Testing URL: " . htmlspecialchars($testUrl) . "</p>";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Direct Test/1.0');

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ cURL Error: " . $error . "</p>";
} else {
    echo "<p>HTTP Status Code: <strong>" . $httpCode . "</strong></p>";
    
    if ($httpCode == 200) {
        echo "<p style='color: green;'>✅ Request successful!</p>";
    } else {
        echo "<p style='color: red;'>❌ Request failed with HTTP " . $httpCode . "</p>";
    }
    
    // Split headers and body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo "<h3>Response Headers:</h3>";
    echo "<pre>" . htmlspecialchars($headers) . "</pre>";
    
    echo "<h3>Response Body:</h3>";
    echo "<pre>" . htmlspecialchars($body) . "</pre>";
}

// Additional test - simple GET to base index
echo "<hr>";
echo "<h3>Testing Base Index:</h3>";

$baseUrl = 'http://localhost:8000/index.php';
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $baseUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "<p>Base index HTTP code: <strong>" . $httpCode2 . "</strong></p>";
if ($httpCode2 == 200) {
    echo "<p style='color: green;'>✅ Base CodeIgniter is working!</p>";
} else {
    echo "<p style='color: red;'>❌ Base CodeIgniter failed</p>";
}
?>