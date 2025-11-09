<?php
// Request a password reset: accepts email, generates token, emails reset link via Mailer.
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
    $db = new Database();
    $conn = $db->getConnection();

    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Always respond ok to avoid user enumeration
        echo json_encode(['ok'=>true]);
        exit;
    }

    $stmt = $conn->prepare('SELECT user_id, username FROM user WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows !== 1) {
        echo json_encode(['ok'=>true]);
        exit;
    }
    $u = $res->fetch_assoc();
    $stmt->close();

    // Create table if needed
    $conn->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expiry_time DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id), INDEX(token_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $raw = bin2hex(random_bytes(16));
    $hash = hash('sha256', $raw);
    $expiry = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
    $ins = $conn->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expiry_time) VALUES (?,?,?)');
    $ins->bind_param('iss', $u['user_id'], $hash, $expiry);
    $ins->execute();
    $ins->close();

    $base = getenv('PUBLIC_BASE_URL');
    if (!$base || !preg_match('/^https?:\/\//i',$base)) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $scheme.'://'.$host;
    }
    $link = rtrim($base,'/').'/tripko-system/tripko-backend/reset_password.php?token='.urlencode($raw).'&uid='.$u['user_id'];

    require_once __DIR__ . '/tools/Mailer.php';
    $subject = 'TripKo Password Reset';
    $body = "Hello ".($u['username'] ?: 'user').",\n\n".
            "We received a request to reset your TripKo account password.\n".
            "Click the link below to set a new password (valid for 30 minutes):\n\n".
            $link . "\n\nIf you didn’t request this, please ignore this email.";
    @Mailer::send($email, $subject, $body);

    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    // Still return ok to avoid enumeration, but log the error
    error_log('request_password_reset error: '.$e->getMessage());
    echo json_encode(['ok'=>true]);
}
?>