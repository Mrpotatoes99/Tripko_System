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
        'message' => 'Forbidden: Super Admin accounts cannot update itinerary status.'
    ]);
    exit();
}

// If Tourism Officer, ensure the itinerary belongs to their municipality
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3) {
    $officer_town = $_SESSION['town_id'] ?? null;
    $payload = json_decode(file_get_contents('php://input'), true);
    $itinerary_id = $payload['itinerary_id'] ?? null;
    if ($itinerary_id && $officer_town) {
        $chk = $conn->prepare("SELECT town_id FROM itineraries WHERE itinerary_id = ? LIMIT 1");
        $chk->bind_param('i', $itinerary_id);
        $chk->execute();
        $res = $chk->get_result();
        $row = $res->fetch_assoc();
        if (!$row || strval($row['town_id']) !== strval($officer_town)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to update this itinerary.']);
            exit();
        }
    }
}

$data = json_decode(file_get_contents('php://input'), true);
$itinerary_id = $data['itinerary_id'] ?? '';
$status = $data['status'] ?? '';

if (!$itinerary_id || !$status || !in_array($status, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid itinerary ID or status']);
    exit;
}

$stmt = $conn->prepare("UPDATE itineraries SET status = ? WHERE itinerary_id = ?");
$stmt->bind_param("si", $status, $itinerary_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update itinerary status']);
}