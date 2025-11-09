<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once(__DIR__ . '/../../config/db.php');

try {
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $sql = "
        SELECT
            u.user_id,
            u.username,
            u.user_type_id,
            u.user_status_id,
            ut.type_name as user_type,
            us.status_name as status,
            up.first_name,
            up.last_name,
            up.user_profile_dob,
            up.email,
            up.contact_number,
            up.user_profile_photo
        FROM user u
        JOIN user_type ut ON u.user_type_id = ut.user_type_id
        JOIN user_status us ON u.user_status_id = us.user_status_id
        LEFT JOIN user_profile up ON u.user_id = up.user_id
        ORDER BY u.user_id DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    echo json_encode(['records' => $records]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>