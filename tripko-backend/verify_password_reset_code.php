<?php
// Verify password reset code and update password
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
    $code = trim($_POST['code'] ?? '');
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new Exception('Invalid email'); }
    if (!preg_match('/^\d{6}$/', $code)) { throw new Exception('Invalid code'); }
    if ($new === '' || $confirm === '') { throw new Exception('Please fill all fields'); }
    if ($new !== $confirm) { throw new Exception('Passwords do not match'); }
    if (strlen($new) < 8 || !preg_match('/[A-Za-z]/',$new) || !preg_match('/\d/',$new)) { throw new Exception('Weak password'); }

    // Find user
    $q = $conn->prepare('SELECT user_id, username FROM user WHERE email=? LIMIT 1');
    $q->bind_param('s', $email);
    $q->execute();
    $res = $q->get_result();
    if ($res->num_rows !== 1) { throw new Exception('Invalid email or code'); }
    $user = $res->fetch_assoc();
    $q->close();

    // Verify code
    $v = $conn->prepare('SELECT id FROM password_reset_codes WHERE user_id=? AND code=? AND used=0 AND expires_at > NOW() LIMIT 1');
    $v->bind_param('is', $user['user_id'], $code);
    $v->execute();
    $vres = $v->get_result();
    if ($vres->num_rows !== 1) { throw new Exception('Invalid or expired code'); }
    $row = $vres->fetch_assoc();
    $v->close();

    // Update password
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $u = $conn->prepare('UPDATE user SET password=? WHERE user_id=?');
    $u->bind_param('si', $hash, $user['user_id']);
    if (!$u->execute()) { throw new Exception('Failed to update password'); }
    $u->close();

    // Mark code as used
    $m = $conn->prepare('UPDATE password_reset_codes SET used=1, used_at=NOW() WHERE id=?');
    $m->bind_param('i', $row['id']);
    $m->execute();
    $m->close();

    // Notify user
    $subject = 'TripKo Security Notice: Your password was reset';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown IP';
    $time = date('Y-m-d H:i:s');
    $body = "Hello ".($user['username'] ?: 'user').",\n\n".
            "This is a confirmation that your TripKo account password was reset on $time from IP $ip.\n\n".
            "If you didn’t make this change, please contact support immediately.";
    @Mailer::send($email, $subject, $body);

    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
