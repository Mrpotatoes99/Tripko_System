<?php
// Enhanced email verification with 6-digit code during registration
// Suppress error display to prevent HTML in JSON response
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

try {
    require_once __DIR__ . '/config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'error'=>'method']);
    exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok'=>false,'error'=>'bad_email']);
    exit;
}
if (!preg_match('/^[A-Za-z0-9._%+-]+@gmail\.com$/', $email)) {
    echo json_encode(['ok'=>false,'error'=>'gmail_only']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Check if email already exists
    $stmt = $conn->prepare('SELECT user_id FROM user WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['ok'=>false,'error'=>'email_exists']);
        exit;
    }
    $stmt->close();

    // Rate limit: max 3 codes per hour per email
    $stmt = $conn->prepare('SELECT COUNT(*) as c FROM email_verification_codes WHERE email=? AND created_at >= (NOW() - INTERVAL 1 HOUR)');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    
    if ((int)$count >= 3) {
        echo json_encode(['ok'=>false,'error'=>'rate_limit']);
        exit;
    }

    // Generate 6-digit code
    $code = sprintf('%06d', mt_rand(0, 999999));
    // Use NOW() + INTERVAL for proper MySQL timezone handling
    $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now

    // Store code - use MySQL NOW() + INTERVAL for proper timezone handling
    $stmt = $conn->prepare('INSERT INTO email_verification_codes (email, code, expires_at) VALUES (?, ?, NOW() + INTERVAL 10 MINUTE) ON DUPLICATE KEY UPDATE code=VALUES(code), expires_at=NOW() + INTERVAL 10 MINUTE, created_at=NOW()');
    $stmt->bind_param('ss', $email, $code);
    $stmt->execute();
    $stmt->close();

    // Send email with code - Development mode: log to file
    $subject = 'TripKo Registration - Verification Code';
    $message = "Your TripKo verification code is: $code\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.";
    
    // For development: log verification codes to file
    $logFile = __DIR__ . '/verification_codes.log';
    $logEntry = date('Y-m-d H:i:s') . " - Email: $email, Code: $code, Expires: $expires\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Try to send email via Mailer (SMTP if configured), but don't fail if it doesn't work
    require_once __DIR__ . '/tools/Mailer.php';
    $emailSent = Mailer::send($email, $subject, $message);
    
    // Respond success but do NOT expose the code in JSON (avoid showing on the form)
    echo json_encode([
        'ok'=>true,
        'message'=> $emailSent ? 'Verification code sent to your email' : 'Verification code generated. Please check your email.'
    ]);

} catch (Exception $e) {
    error_log('Send verification code error: ' . $e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'system','debug'=>$e->getMessage()]);
} finally {
    if (isset($conn) && $conn) $conn->close();
}
} catch (Exception $e) {
    // Catch any require/include errors
    echo json_encode(['ok'=>false,'error'=>'config_error','debug'=>$e->getMessage()]);
}
?>