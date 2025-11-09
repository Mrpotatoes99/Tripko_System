<?php
// Create a municipal-level transport mapping (terminal -> municipality with transport type)
// Permissions: Tourism Officer can create only for their own municipality; others read-only.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Auth: require logged in user
    $userId = $_SESSION['user_id'] ?? null;
    $userTypeId = $_SESSION['user_type_id'] ?? null; // 3 = Tourism Officer
    $sessionMunicipalityId = $_SESSION['municipality_id'] ?? null; // cached in auth_guard

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $originTerminalId = isset($input['origin_terminal_id']) ? (int)$input['origin_terminal_id'] : 0;
    $townId = isset($input['town_id']) ? (int)$input['town_id'] : 0;
    $typeId = isset($input['type_id']) ? (int)$input['type_id'] : 0;

    if ($originTerminalId <= 0 || $townId <= 0 || $typeId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'origin_terminal_id, town_id, and type_id are required']);
        exit;
    }

    // Authorization: Tourism Officers can only write for their own municipality
    if ((int)$userTypeId === 3) {
        if (!$sessionMunicipalityId || (int)$sessionMunicipalityId !== $townId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: You can only map routes for your municipality']);
            exit;
        }
    } else if ((int)$userTypeId === 1) {
        // Super Admin policy: keep read-only to match existing pattern unless changed intentionally
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: Super Admin accounts cannot create municipal transport routes']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Validate terminal exists and active
    $stmt = $conn->prepare("SELECT terminal_id FROM terminal_locations WHERE terminal_id = ? AND status='active' LIMIT 1");
    $stmt->bind_param('i', $originTerminalId);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || !$res->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Terminal not found or inactive']);
        exit;
    }

    // Validate town exists
    $stmt = $conn->prepare("SELECT town_id FROM towns WHERE town_id = ? LIMIT 1");
    $stmt->bind_param('i', $townId);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || !$res->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Municipality not found']);
        exit;
    }

    // Validate transport type exists
    $stmt = $conn->prepare("SELECT type_id FROM transport_types WHERE type_id = ? LIMIT 1");
    $stmt->bind_param('i', $typeId);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || !$res->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transport type not found']);
        exit;
    }

    // Insert or activate existing mapping
    $sql = "INSERT INTO municipal_transport_routes (origin_terminal_id, town_id, type_id, status)
            VALUES (?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE status = 'active', updated_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $originTerminalId, $townId, $typeId);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Mapping saved']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
