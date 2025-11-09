<?php
// Verify phone code and complete registration (create user)
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
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($rawPhone === '' || $code === '' || $username === '' || $password === '') {
        echo json_encode(['ok'=>false,'error'=>'missing_fields']);
        exit;
    }

    // Normalize phone
    $norm = preg_replace('/[^\d+]/','', $rawPhone);
    if (preg_match('/^\+?63(9\d{9})$/', $norm, $m)) {
        $phone = '+63' . $m[1];
    } elseif (preg_match('/^0(9\d{9})$/', $norm, $m)) {
        $phone = '+63' . $m[1];
    } else { echo json_encode(['ok'=>false,'error'=>'bad_phone']); exit; }

    if (!preg_match('/^\d{6}$/', $code)) { echo json_encode(['ok'=>false,'error'=>'invalid_code']); exit; }
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) { echo json_encode(['ok'=>false,'error'=>'invalid_username']); exit; }
    if (strlen($password) < 6) { echo json_encode(['ok'=>false,'error'=>'weak_password']); exit; }

    $db = new Database();
    $conn = $db->getConnection();

    try {
        $conn->begin_transaction();

        // Verify code row
        $stmt = $conn->prepare('SELECT id FROM sms_verification_codes WHERE phone=? AND code=? AND verified=0 AND expires_at > NOW() LIMIT 1');
        $stmt->bind_param('ss', $phone, $code);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) { $conn->rollback(); echo json_encode(['ok'=>false,'error'=>'invalid_or_expired_code']); exit; }
        $codeId = (int)$res->fetch_assoc()['id'];
        $stmt->close();

        // Ensure username unique
        $stmt = $conn->prepare('SELECT user_id FROM user WHERE username=? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) { $conn->rollback(); echo json_encode(['ok'=>false,'error'=>'username_exists']); exit; }
        $stmt->close();

        // Ensure phone not already used
        $stmt = $conn->prepare('SELECT user_id FROM user_profile WHERE contact_number=? LIMIT 1');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) { $conn->rollback(); echo json_encode(['ok'=>false,'error'=>'phone_exists']); exit; }
        $stmt->close();

        // Create user (no email, phone verified counts as verified for login checks)
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO user (username, email, password, user_type_id, user_status_id, is_email_verified, email_verified_at) VALUES (?, NULL, ?, 2, 1, 1, NOW())');
        $stmt->bind_param('ss', $username, $hash);
        if (!$stmt->execute()) { throw new Exception('create user failed'); }
        $userId = $conn->insert_id;
        $stmt->close();

        // Create user_profile (store phone)
        $stmt = $conn->prepare('INSERT INTO user_profile (user_id, contact_number) VALUES (?, ?)');
        $stmt->bind_param('is', $userId, $phone);
        $stmt->execute();
        $stmt->close();

        // Consume code
        $stmt = $conn->prepare('UPDATE sms_verification_codes SET verified=1, verified_at=NOW() WHERE id=?');
        $stmt->bind_param('i', $codeId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['ok'=>true,'message'=>'Registration successful! You can now login.']);
    } catch (Throwable $e) {
        if ($conn && $conn->in_transaction) { $conn->rollback(); }
        error_log('verify_sms_registration_code error: '.$e->getMessage());
        echo json_encode(['ok'=>false,'error'=>'system']);
    } finally {
        if ($conn) { $conn->close(); }
    }
} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>'config_error']);
}
