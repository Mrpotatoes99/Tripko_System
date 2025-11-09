<?php
// Send a 6-digit verification code via SMS (development: logs to file instead of real SMS)
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
    if ($rawPhone === '') { echo json_encode(['ok'=>false,'error'=>'missing_phone']); exit; }

    // Normalize phone to E.164 for PH: +639XXXXXXXXX
    $norm = preg_replace('/[^\d+]/','', $rawPhone);
    if (preg_match('/^\+?63(9\d{9})$/', $norm, $m)) {
        $phone = '+63' . $m[1];
    } elseif (preg_match('/^0(9\d{9})$/', $norm, $m)) {
        $phone = '+63' . $m[1];
    } else {
        echo json_encode(['ok'=>false,'error'=>'bad_phone']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if phone already in use (user_profile.contact_number)
    $stmt = $conn->prepare('SELECT user_id FROM user_profile WHERE contact_number=? LIMIT 1');
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['ok'=>false,'error'=>'phone_exists']);
        exit;
    }
    $stmt->close();

    // Rate limit: max 3 per hour
    $stmt = $conn->prepare('SELECT COUNT(*) c FROM sms_verification_codes WHERE phone=? AND created_at >= (NOW() - INTERVAL 1 HOUR)');
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    if ($count >= 3) { echo json_encode(['ok'=>false,'error'=>'rate_limit']); exit; }

    $code = sprintf('%06d', mt_rand(0, 999999));

    // Insert or update active code (verified=0 uniqueness)
    $stmt = $conn->prepare('INSERT INTO sms_verification_codes (phone, code, expires_at) VALUES (?, ?, NOW() + INTERVAL 10 MINUTE)
                             ON DUPLICATE KEY UPDATE code=VALUES(code), expires_at=NOW() + INTERVAL 10 MINUTE, created_at=NOW(), verified=0, verified_at=NULL');
    $stmt->bind_param('ss', $phone, $code);
    $stmt->execute();
    $stmt->close();

    // Attempt real SMS delivery if configured
    require_once __DIR__ . '/tools/SMS.php';
    $smsMessage = "Your TripKo verification code is: $code\nThis code will expire in 10 minutes.";
    $sendResult = SMS::send($phone, $smsMessage);

    if ($sendResult['ok'] ?? false) {
        echo json_encode(['ok'=>true,'message'=>'Verification code sent to your phone','phone'=>$phone]);
        exit;
    }

    // Fallback: log to file if provider not configured or failed
    $cfg = file_exists(__DIR__ . '/config/sms_config.php') ? (require __DIR__ . '/config/sms_config.php') : [];
    $fallback = (bool)($cfg['FALLBACK_TO_LOG'] ?? true);
    $logFile = __DIR__ . '/sms_codes.log';
    $logEntry = date('Y-m-d H:i:s') . " - Phone: $phone, Code: $code\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    if ($fallback) {
        // Report generic success to avoid leaking details
        echo json_encode(['ok'=>true,'message'=>'Verification code sent to your phone','phone'=>$phone]);
    } else {
        $err = $sendResult['error'] ?? 'sms_failed';
        echo json_encode(['ok'=>false,'error'=>$err]);
    }
} catch (Throwable $e) {
    error_log('send_sms_code error: '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'system']);
}
