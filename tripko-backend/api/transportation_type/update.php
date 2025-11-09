<?php
require_once(__DIR__ . '/../../config/db.php');
header("Content-Type: application/json; charset=UTF-8");

// Block Super Admin (user_type_id == 1) from performing write operations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Super Admin accounts cannot update transportation types.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$transport_type_id = $data['transport_type_id'] ?? null;
$transportation = $data['transportation'] ?? '';
$type = $data['type'] ?? '';

if (!$transport_type_id) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE transportation_type SET transportation=?, type=? WHERE transport_type_id=?");
$stmt->bind_param("ssi", $transportation, $type, $transport_type_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed or no changes']);
}