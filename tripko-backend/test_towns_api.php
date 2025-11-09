<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to make an HTTP request
function makeRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'response' => $result,
        'error' => $error
    ];
}

// Test the towns API
$url = 'http://localhost/tripko-system/tripko-backend/api/towns/read.php';
echo "Testing URL: $url\n\n";

$result = makeRequest($url);

echo "HTTP Status: " . $result['status'] . "\n";
if ($result['error']) {
    echo "Error: " . $result['error'] . "\n";
} else {
    echo "Response:\n" . $result['response'] . "\n";
    
    // Try to decode JSON
    $data = json_decode($result['response'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\nDecoded data:\n";
        print_r($data);
    } else {
        echo "\nJSON decode error: " . json_last_error_msg() . "\n";
    }
}
?>
