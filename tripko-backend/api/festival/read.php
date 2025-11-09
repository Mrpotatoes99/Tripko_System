<?php
// Prevent any output before headers
ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


session_start();
require_once(__DIR__ . '/../../config/check_session.php');
// Require user session (user_type_id = 2)
checkUserSession();

// Fix path case sensitivity
include_once __DIR__ . '/../../config/Database.php';
include_once __DIR__ . '/../../models/Festival.php';

// Initialize database and handle connection errors
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $festival = new Festival($db);

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('HTTP/1.1 405 Method Not Allowed');
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get festivals with error checking
    $result = $festival->read();
    
    if (!$result) {
        throw new Exception("Failed to execute query");
    }
    
    if($result->num_rows > 0) {
        $festivals_arr = array();
        $festivals_arr['records'] = array();

        while($row = $result->fetch_assoc()) {
            $festival_item = array(
                'festival_id' => $row['festival_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'date' => $row['date'],
                'town_id' => $row['town_id'],
                'town_name' => $row['town_name'],
                'status' => $row['status'],
                'image_path' => $row['image_path']
            );

            array_push($festivals_arr['records'], $festival_item);
        }

        header('HTTP/1.1 200 OK');
        ob_end_clean();
        echo json_encode($festivals_arr);
    } else {
        header('HTTP/1.1 404 Not Found');
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'No festivals found']);
    }
} catch(Exception $e) {
    error_log("Error in festival read.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch festivals',
        'error' => $e->getMessage()
    ]);
}
