<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/Database.php';
require_once '../../models/TouristSpot.php';

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Initialize tourist spot object
    $tourist_spot = new TouristSpot($conn);
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $view = isset($_GET['view']) ? $_GET['view'] : 'grid';
    $limit = $view === 'table' ? 10 : 6; // 10 for table view, 6 for grid view

    // Get filter parameters
    $filters = array();
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $filters['category'] = $_GET['category'];
    }
    if (isset($_GET['municipality']) && !empty($_GET['municipality'])) {
        $filters['municipality'] = $_GET['municipality'];
    }
    
    // Get the user type from the session if available
    session_start();
    $userType = $_SESSION['user_type'] ?? null;
    
    // Get tourist spots based on user type and view
    if ($userType === 'admin' || $userType === 'tourism_officer') {
        $result = $tourist_spot->readPaginated($page, $limit, $view, $filters);
    } else {
        // Regular users see active spots in grid view, but table view shows all spots
        if ($view === 'table') {
            $result = $tourist_spot->readPaginated($page, $limit, $view, $filters);
        } else {
            $result = $tourist_spot->readActivePaginated($page, $limit, $filters);
        }
    }
    
    if (!$result) {
        throw new Exception("Failed to fetch tourist spots");
    }

    $spots_arr = array();
    $spots_arr['records'] = array();

    while ($row = $result['records']->fetch_assoc()) {
        $spot_item = array(
            'spot_id' => $row['spot_id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'category' => $row['category'],
            'town_id' => $row['town_id'],
            'town_name' => $row['town_name'],
            'contact_info' => $row['contact_info'],
            'image_path' => $row['image_path'],
            'status' => $row['status'] ?? 'active',
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        );
        array_push($spots_arr['records'], $spot_item);
    }
    
    // Add pagination info to response
    $spots_arr['pagination'] = array(
        'page' => $result['page'],
        'pages' => $result['pages'],
        'total' => $result['total'],
        'limit' => $limit
    );
    
    http_response_code(200);
    $spots_arr['success'] = true;
    echo json_encode($spots_arr);

} catch (Exception $e) {
    error_log("Error in tourist spots API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'An error occurred while fetching tourist spots. Please try again later.',
        'error' => $e->getMessage()
    ));
}