<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['user_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields');
    }

    $user_id = $data['user_id'];    $status = $data['status'];

    // Convert status string to status_id
    $status_id = $status === 'active' ? 1 : 2;

    $stmt = $conn->prepare("UPDATE user SET user_status_id = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $status_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update status');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
