<?php
function setupApiHeaders() {
    header("Access-Control-Allow-Origin: http://localhost");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=UTF-8");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('HTTP/1.1 200 OK');
        exit;
    }
}

function requireAdminAuth() {
    session_start();
    require_once(__DIR__ . '/check_session.php');

    if (!isset($_SESSION['user_id'])) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}
?>
