<?php
// Check if a 6-digit email verification code is valid (without consuming it)
// This is used to gate the password setup UI before final registration.
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    echo json_encode(['ok'=>true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'error'=>'method']);
    exit;
}

try {
    require_once __DIR__ . '/config/Database.php';

    $email = trim($_POST['email'] ?? '');
    $code  = trim($_POST['code'] ?? '');

    if ($email === '' || $code === '') {
        echo json_encode(['ok'=>false,'error'=>'missing_fields']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok'=>false,'error'=>'bad_email']);
        exit;
    }
    if (!preg_match('/^\d{6}$/', $code)) {
        echo json_encode(['ok'=>false,'error'=>'invalid_code']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Ensure email not already registered
    $stmt = $conn->prepare('SELECT user_id FROM user WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['ok'=>false,'error'=>'email_exists']);
        exit;
    }
    $stmt->close();

    // Check code validity (exists, unverified, not expired)
    $stmt = $conn->prepare('SELECT id FROM email_verification_codes WHERE email=? AND code=? AND verified=0 AND expires_at > NOW() LIMIT 1');
    $stmt->bind_param('ss', $email, $code);
    $stmt->execute();
    $res = $stmt->get_result();
    $valid = $res->num_rows > 0;
    $stmt->close();

    if ($valid) {
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'invalid_or_expired_code']);
    }
} catch (Throwable $e) {
    error_log('check_verification_code error: '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'system']);
}
