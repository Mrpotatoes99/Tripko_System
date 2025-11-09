<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once(__DIR__ . '/../../config/Database.php');
require_once(__DIR__ . '/../../models/Itinerary.php');

try {
    error_log("Starting itineraries read.php");
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('HTTP/1.1 405 Method Not Allowed');
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Initialize database and model
    error_log("Initializing database connection...");
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        error_log("Database connection failed in itineraries/read.php");
        throw new Exception("Database connection failed");
    }
    
    error_log("Database connection successful, initializing Itinerary model");
    $itinerary = new Itinerary($db);
    error_log("Itinerary model initialized");
    
    // Get itineraries
    $result = $itinerary->read();
    
    if ($result && $result->num_rows > 0) {
        $itineraries_arr = array();
        $itineraries_arr['success'] = true;
        $itineraries_arr['records'] = array();

        while ($row = $result->fetch_assoc()) {
            // Format environmental fee to ensure it's a numeric value
            $environmental_fee = !empty($row['environmental_fee']) ? $row['environmental_fee'] : '';            $itinerary_item = array(                'itinerary_id' => intval($row['itinerary_id']),
                'name' => $row['name'],
                'description' => $row['description'],
                'town_name' => $row['town_name'],
                'town_id' => intval($row['town_id']),
                'environmental_fee' => $environmental_fee,
                'image_path' => $row['image_path'] ?? null,
                'status' => $row['status'] ?? 'active',
                'created_at' => $row['created_at'] ?? null
            );
            array_push($itineraries_arr['records'], $itinerary_item);
        }

        header('HTTP/1.1 200 OK');
        ob_end_clean();
        echo json_encode($itineraries_arr);
    } else {
        header('HTTP/1.1 200 OK');
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'records' => []
        ]);
    }
} catch(Exception $e) {
    error_log("Error in read.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch itineraries',
        'error' => $e->getMessage()
    ]);
}

if (isset($db) && $db) {
    $db->close();
}