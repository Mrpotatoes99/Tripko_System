<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once 'config/Database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed!");
}

try {
    $query = "DESCRIBE tourist_spots";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "Tourist Spots Table Structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }
    
    // Check for data
    $query = "SELECT COUNT(*) as count FROM tourist_spots";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal tourist spots: " . $row['count'] . "\n";
    
    // Check sample data
    $query = "SELECT * FROM tourist_spots LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo "\nSample tourist spot:\n";
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
