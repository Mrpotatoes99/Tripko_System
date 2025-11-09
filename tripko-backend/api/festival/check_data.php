<?php
header("Content-Type: text/plain");
require_once(__DIR__ . '/../../config/db.php');

echo "Checking database connection...\n";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Database connection successful!\n\n";

echo "Checking festivals table structure:\n";
$result = $conn->query("DESCRIBE festivals");
while ($row = $result->fetch_assoc()) {
    echo json_encode($row) . "\n";
}

echo "\nChecking festivals data:\n";
$result = $conn->query("SELECT f.*, t.town_name 
                       FROM festivals f 
                       LEFT JOIN towns t ON f.town_id = t.town_id");

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "\nFestival record:\n";
            echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "No festival records found!\n";
    }
} else {
    echo "Error querying festivals: " . $conn->error . "\n";
}

?>
