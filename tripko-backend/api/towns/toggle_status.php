<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once(__DIR__ . '/../../config/db.php');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->town_id) || !isset($data->status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $sql = "UPDATE towns SET status = ? WHERE town_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $data->status, $data->town_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Town status updated']);
    } else {
        throw new Exception('Failed to update town status');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not update town status: ' . $e->getMessage()
    ]);
}
?>
