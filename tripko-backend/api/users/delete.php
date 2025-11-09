<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        throw new Exception('Invalid request method');
    }

    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    
    if (!$user_id) {
        throw new Exception('Missing user_id parameter');
    }

    // Start transaction
    $conn->begin_transaction();

    // First delete from user_profile
    $stmt = $conn->prepare("DELETE FROM user_profile WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Then delete the user
    $stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        // Commit the transaction
        $conn->commit();
        
        echo json_encode([
            "success" => true,
            "message" => "User deleted successfully"
        ]);
    } else {
        throw new Exception("User not found");
    }

} catch (Exception $e) {
    // Rollback the transaction on error
    if ($conn && $conn->connect_error === null) {
        $conn->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>