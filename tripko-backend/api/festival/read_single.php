<?php
// Prevent any output before headers
ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

session_start();
require_once(__DIR__ . '/../../config/check_session.php');

// Check admin session
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once(__DIR__ . '/../../config/db.php');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Missing festival ID');
    }

    $festival_id = intval($_GET['id']);
    if ($festival_id <= 0) {
        throw new Exception('Invalid festival ID');
    }

    $sql = "
        SELECT
            f.festival_id,
            f.name,
            f.description,
            f.date,
            f.image_path,
            f.town_id,
            t.name as town_name,
            COALESCE(f.status, 'active') as status
        FROM festivals f
        LEFT JOIN towns t ON f.town_id = t.town_id
        WHERE f.festival_id = ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }    $stmt->bind_param("i", $festival_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }

    $row = $result->fetch_assoc();
    if ($row) {
        header('HTTP/1.1 200 OK');
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'festival' => $row
        ]);
    } else {
        header('HTTP/1.1 404 Not Found');
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Festival not found'
        ]);
    }

} catch (Exception $e) {
    error_log("Error in festival read_single.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
