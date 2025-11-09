<?php
// Send a 6-digit password reset code to the user's email (no links)
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { echo json_encode(['ok'=>true]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'method']); exit; }

try {
    require_once __DIR__ . '/config/Database.php';
    require_once __DIR__ . '/tools/Mailer.php';
    $db = new Database();
    $conn = $db->getConnection();

    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['ok'=>true]); exit; }

    // Find user silently
    $q = $conn->prepare('SELECT user_id, username FROM user WHERE email=? LIMIT 1');
    $q->bind_param('s', $email);
    $q->execute();
    $res = $q->get_result();
    if ($res->num_rows !== 1) { echo json_encode(['ok'=>true]); exit; }
    $user = $res->fetch_assoc();
    $q->close();

    // Table for codes
    $conn->query("CREATE TABLE IF NOT EXISTS password_reset_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        code VARCHAR(6) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id), INDEX(code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Rate limit per user: 1 per 60 seconds
    $rl = $conn->prepare('SELECT created_at FROM password_reset_codes WHERE user_id=? ORDER BY id DESC LIMIT 1');
    $rl->bind_param('i', $user['user_id']);
    $rl->execute();
    $rres = $rl->get_result();
    if ($last = $rres->fetch_assoc()) {
        $ts = strtotime($last['created_at']);
        if ($ts && time() - $ts < 60) { echo json_encode(['ok'=>true]); exit; }
    }
    $rl->close();

    // Invalidate old unused codes to avoid confusion
    $conn->query('UPDATE password_reset_codes SET used=2 WHERE user_id='.(int)$user['user_id'].' AND used=0');

    $code = sprintf('%06d', mt_rand(0,999999));
    $ins = $conn->prepare('INSERT INTO password_reset_codes (user_id, code, expires_at, used) VALUES (?,?, NOW() + INTERVAL 15 MINUTE, 0)');
    $ins->bind_param('is', $user['user_id'], $code);
    $ins->execute();
    $ins->close();

    $subject = 'TripKo Password Reset Code';
    $body = "Hello ".($user['username'] ?: 'user').",\n\n".
            "Use this 6-digit code to reset your password: $code\n".
            "This code expires in 15 minutes. If you didn't request this, you can ignore this email.";
    @Mailer::send($email, $subject, $body);

    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    error_log('request_password_reset_code error: '.$e->getMessage());
    echo json_encode(['ok'=>true]);
}
