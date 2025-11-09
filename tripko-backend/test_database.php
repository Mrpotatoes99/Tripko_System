<?php
// Test database connection specifically
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Test the Database class
    require_once 'config/Database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        // Test if we can execute a simple query
        $result = $conn->query("SELECT 1 as test");
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Database connection successful',
                'database' => 'Connected to tripko_db',
                'test_query' => 'Working'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Query execution failed',
                'error' => $conn->error
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database test failed',
        'error' => $e->getMessage(),
        'type' => get_class($e)
    ]);
}
?>