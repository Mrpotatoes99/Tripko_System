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
        'message' => 'Forbidden: Super Admin accounts cannot update terminal routes.'
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['route_id'] ?? '';
$origin_terminal_id = $data['origin_terminal_id'] ?? '';
$destination_terminal_id = $data['destination_terminal_id'] ?? '';
$type_ids = $data['type_ids'] ?? [];

if (!$id || !$origin_terminal_id || !$destination_terminal_id || empty($type_ids)) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE transport_routes SET from_terminal_id=?, to_terminal_id=? WHERE route_id=?");
    $stmt->bind_param("iii", $origin_terminal_id, $destination_terminal_id, $id);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM route_transport_types WHERE route_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt = $conn->prepare("INSERT INTO route_transport_types (route_id, type_id) VALUES (?, ?)");
    foreach ($type_ids as $type_id) {
        $stmt->bind_param("ii", $id, $type_id);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}