<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing towns API endpoint...\n\n";

$url = 'http://localhost/tripko-system/tripko-backend/api/towns/read.php';

// Make the request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

echo "Sending GET request to: $url\n";
$response = curl_exec($ch);

if ($response === false) {
    echo "Error: " . curl_error($ch) . "\n";
    exit(1);
}

// Get the response code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Status Code: $httpCode\n\n";

// Split header and body
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

// Print headers
echo "Response Headers:\n" . $header . "\n";

// Print body
echo "Response Body:\n" . $body . "\n";

// Try to decode JSON
$data = json_decode($body, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "\nJSON successfully decoded. Contents:\n";
    print_r($data);
} else {
    echo "\nError decoding JSON: " . json_last_error_msg() . "\n";
}
