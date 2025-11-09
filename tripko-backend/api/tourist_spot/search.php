<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/Database.php';
include_once '../../models/TouristSpot.php';

$database = new Database();
$db = $database->getConnection();
$spot = new TouristSpot($db);

// Get search query
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $search = isset($data->search) ? $data->search : '';
} else {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
}

// Query tourist spots based on search
$result = $spot->search($search);

if($result && $result->num_rows > 0) {
    $spots_arr = array();
    $spots_arr['records'] = array();

    while ($row = $result->fetch_assoc()) {
        $spot_item = array(
            'spot_id' => $row['spot_id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'town_id' => $row['town_id'],
            'town_name' => $row['town_name'],
            'category' => $row['category'],
            'contact_info' => $row['contact_info'],
            'image_path' => $row['image_path'],
            'status' => $row['status']
        );

        array_push($spots_arr['records'], $spot_item);
    }

    header('HTTP/1.1 200 OK');
    echo json_encode($spots_arr);
} else {
    header('HTTP/1.1 200 OK');
    echo json_encode(array('message' => 'No tourist spots found.'));
}
