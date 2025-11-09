<?php
$require_once_line = true;
require_once(__DIR__ . '/../../config/db.php');
header("Content-Type: application/json; charset=UTF-8");

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Super Admin accounts cannot update terminal locations.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['terminal_id'] ?? '';
$name = $data['name'] ?? '';
$town = $data['town'] ?? '';
$coordinates = $data['coordinates'] ?? '';

if (!$id || !$name || !$town || !$coordinates) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

$stmt = $conn->prepare("UPDATE route_terminals SET name=?, town=?, coordinates=? WHERE terminal_id=?");
$stmt->bind_param("sssi", $name, $town, $coordinates, $id);
$stmt->execute();

echo json_encode(['success' => $stmt->affected_rows > 0]);