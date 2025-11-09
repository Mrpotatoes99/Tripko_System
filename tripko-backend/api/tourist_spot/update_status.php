<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
header('HTTP/1.1 200 OK');
    exit;
}

require_once(__DIR__ . '/../../config/db.php');

// Block Super Admin (user_type_id == 1) from performing write operations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Super Admin accounts cannot update tourist spot status.'
    ]);
    exit();
}

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->spot_id) || !isset($data->status)) {
        throw new Exception("Missing required fields");
    }

    $sql = "UPDATE tourist_spots SET status = ? WHERE spot_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $data->status, $data->spot_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Tourist spot status updated successfully'
        ]);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error updating tourist spot status: ' . $e->getMessage()
    ]);
}
?>
