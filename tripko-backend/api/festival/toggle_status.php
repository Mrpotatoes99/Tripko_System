<?php
// filepath: c:\xampp\htdocs\tripko-system\tripko-backend\api\festival\toggle_status.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
require_once(__DIR__ . '/../../config/db.php');

// Block Super Admin (user_type_id == 1) from performing write operations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Super Admin accounts cannot toggle festival status.'
    ]);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->festival_id) || !isset($data->status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
        // If Tourism Officer, ensure festival belongs to their municipality
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3) {
            $officer_town = $_SESSION['town_id'] ?? null;
            if ($officer_town) {
                $stmtChk = $conn->prepare("SELECT town_id FROM festivals WHERE festival_id = ? LIMIT 1");
                $stmtChk->bind_param('i', $data->festival_id);
                $stmtChk->execute();
                $res = $stmtChk->get_result();
                $row = $res->fetch_assoc();
                if (!$row || strval($row['town_id']) !== strval($officer_town)) {
                    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to change this festival status.']);
                    exit;
                }
            }
        }

    $sql = "UPDATE festivals SET status = ? WHERE festival_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $data->status, $data->festival_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Festival status updated']);
    } else {
        throw new Exception('Failed to update festival status');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not update festival status: ' . $e->getMessage()
    ]);
}
?>
