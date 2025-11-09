<?php
// Check phone verification code validity without consuming it
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { echo json_encode(['ok'=>true]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'error'=>'method']); exit; }

try {
    require_once __DIR__ . '/config/Database.php';

    $rawPhone = trim($_POST['phone'] ?? '');
    $code = trim($_POST['code'] ?? '');
    if ($rawPhone === '' || $code === '') { echo json_encode(['ok'=>false,'error'=>'missing_fields']); exit; }

    // Normalize to +639XXXXXXXXX
    $norm = preg_replace('/[^\d+]/','', $rawPhone);
    if (preg_match('/^\+?63(9\d{9})$/', $norm, $m)) {
        $phone = '+63' . $m[1];
    } elseif (preg_match('/^0(9\d{9})$/', $norm, $m)) {
        $phone = '+63' . $m[1];
    } else { echo json_encode(['ok'=>false,'error'=>'bad_phone']); exit; }

    if (!preg_match('/^\d{6}$/', $code)) { echo json_encode(['ok'=>false,'error'=>'invalid_code']); exit; }

    $db = new Database();
    $conn = $db->getConnection();

    // Ensure phone not already registered
    $stmt = $conn->prepare('SELECT user_id FROM user_profile WHERE contact_number=? LIMIT 1');
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) { echo json_encode(['ok'=>false,'error'=>'phone_exists']); exit; }
    $stmt->close();

    $stmt = $conn->prepare('SELECT id FROM sms_verification_codes WHERE phone=? AND code=? AND verified=0 AND expires_at > NOW() LIMIT 1');
    $stmt->bind_param('ss', $phone, $code);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    echo json_encode($ok ? ['ok'=>true] : ['ok'=>false,'error'=>'invalid_or_expired_code']);
} catch (Throwable $e) {
    error_log('check_sms_code error: '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'system']);
}
