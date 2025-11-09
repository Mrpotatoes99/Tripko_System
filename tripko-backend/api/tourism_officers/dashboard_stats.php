<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../check_session.php';
require_once __DIR__ . '/../../config/Database.php';

// Ensure the caller is a logged-in tourism officer
checkTourismOfficerSession();

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Determine town_id: prefer query param, fall back to session
    $town_id = isset($_GET['town_id']) ? (int)$_GET['town_id'] : ($_SESSION['town_id'] ?? null);
    if (!$town_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'town_id is required']);
        exit();
    }

    // Count tourist spots for town
    $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM tourist_spots WHERE town_id = ?');
    $stmt->bind_param('i', $town_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $tourist_spots = (int)($res['cnt'] ?? 0);
    $stmt->close();

    // Count festivals
    $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM festivals WHERE town_id = ?');
    $stmt->bind_param('i', $town_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $festivals = (int)($res['cnt'] ?? 0);
    $stmt->close();

    // Count itineraries
    $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM itineraries WHERE town_id = ?');
    $stmt->bind_param('i', $town_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $itineraries = (int)($res['cnt'] ?? 0);
    $stmt->close();

    // monthly visitors not tracked - return 0 as placeholder
    $monthly_visitors = 0;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'stats' => [
            'tourist_spots' => $tourist_spots,
            'festivals' => $festivals,
            'itineraries' => $itineraries,
            'monthly_visitors' => $monthly_visitors
        ]
    ]);

} catch (Exception $e) {
    error_log('dashboard_stats error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error', 'error' => $e->getMessage()]);
}
