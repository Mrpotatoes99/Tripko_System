<?php
session_start();
error_log("Starting test_tourism_session.php");

// Set up test session data
$_SESSION['user_id'] = 1;  // Example tourism officer ID
$_SESSION['user_type_id'] = 3; // Tourism officer type
$_SESSION['town_id'] = 1;  // Example town ID

echo "Current session data:\n";
print_r($_SESSION);

// Test API call to tourist spots
$ch = curl_init('http://localhost/tripko-system/tripko-backend/api/tourism_officers/tourist_spots.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nAPI Response (HTTP $httpCode):\n";
echo $response;

// Also check error log
echo "\nPHP Error Log (last 10 lines):\n";
if (file_exists("C:/xampp/php/logs/php_error_log")) {
    $log = shell_exec('Get-Content "C:/xampp/php/logs/php_error_log" -Tail 10');
    echo $log;
}
?>
