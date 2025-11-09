<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "tripko_db";

try {
    // Create connection with error reporting
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }
    
    // Test connection
    if (!$conn->ping()) {
        throw new Exception("Connection is not responding");
    }
    
    echo "Connected successfully to database.\n";
    
    // Test towns table
    $result = $conn->query("SELECT COUNT(*) as count FROM towns");
    if (!$result) {
        throw new Exception("Error querying towns table: " . $conn->error);
    }
    
    $row = $result->fetch_assoc();
    echo "Number of towns in database: " . $row['count'] . "\n";
    
    // Get sample town data
    $result = $conn->query("SELECT town_id, name FROM towns LIMIT 5");
    if (!$result) {
        throw new Exception("Error querying towns data: " . $conn->error);
    }
    
    echo "\nSample towns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['town_id'] . ", Name: " . $row['name'] . "\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
