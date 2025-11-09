<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "tripko_db";

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "Database connection successful!\n";
    
    // Test towns table
    $result = $conn->query("SELECT * FROM towns LIMIT 1");
    if ($result) {
        echo "Towns table exists and is accessible.\n";
        while($row = $result->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Error accessing towns table: " . $conn->error . "\n";
    }
    
    // Test tourist_spots table
    $result = $conn->query("SELECT * FROM tourist_spots LIMIT 1");
    if ($result) {
        echo "Tourist spots table exists and is accessible.\n";
        while($row = $result->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Error accessing tourist_spots table: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    die("Connection error: " . $e->getMessage());
}
?>
