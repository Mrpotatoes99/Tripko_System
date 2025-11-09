<?php
$require_once_line = true;
require_once(__DIR__ . '/../../config/db.php');
header("Content-Type: application/json; charset=UTF-8");

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Super Admin accounts cannot create terminal locations.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? '';
$town = $data['town'] ?? '';
$coordinates = $data['coordinates'] ?? '';

if (!$name || !$town || !$coordinates) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO route_terminals (name, town, coordinates) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $town, $coordinates);
$stmt->execute();

echo json_encode(['success' => $stmt->affected_rows > 0]);