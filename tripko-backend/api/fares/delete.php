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
		'message' => 'Forbidden: Super Admin accounts cannot delete fares.'
	]);
	exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$stmt = $conn->prepare("DELETE FROM fares WHERE fare_id=?");
$stmt->bind_param("i", $data['fare_id']);
$stmt->execute();

echo json_encode(['success' => true]);