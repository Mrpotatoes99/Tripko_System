<?php
// Verify email code and complete registration
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
$code = trim($_POST['code'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $code === '' || $username === '' || $password === '') {
    echo json_encode(['ok'=>false,'error'=>'missing_fields']);
    exit;
}

// Validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok'=>false,'error'=>'bad_email']);
    exit;
}
if (!preg_match('/^[A-Za-z0-9._%+-]+@gmail\.com$/', $email)) {
    echo json_encode(['ok'=>false,'error'=>'gmail_only']);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
    echo json_encode(['ok'=>false,'error'=>'invalid_username']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['ok'=>false,'error'=>'weak_password']);
    exit;
}
if (!preg_match('/^\d{6}$/', $code)) {
    echo json_encode(['ok'=>false,'error'=>'invalid_code']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->begin_transaction();

    // Verify code
    $stmt = $conn->prepare('SELECT id FROM email_verification_codes WHERE email=? AND code=? AND expires_at > NOW() AND verified=0 LIMIT 1');
    $stmt->bind_param('ss', $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['ok'=>false,'error'=>'invalid_or_expired_code']);
        exit;
    }
    $codeId = $result->fetch_assoc()['id'];
    $stmt->close();

    // Check username availability
    $stmt = $conn->prepare('SELECT user_id FROM user WHERE username=? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $conn->rollback();
        echo json_encode(['ok'=>false,'error'=>'username_exists']);
        exit;
    }
    $stmt->close();

    // Check email availability (double check)
    $stmt = $conn->prepare('SELECT user_id FROM user WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $conn->rollback();
        echo json_encode(['ok'=>false,'error'=>'email_exists']);
        exit;
    }
    $stmt->close();

    // Create user (already verified since they provided the email code)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO user (username, email, password, user_type_id, user_status_id, is_email_verified, email_verified_at) VALUES (?, ?, ?, 2, 1, 1, NOW())');
    $stmt->bind_param('sss', $username, $email, $hashedPassword);
    if (!$stmt->execute()) {
        throw new Exception('Failed to create user');
    }
    $userId = $conn->insert_id;
    $stmt->close();

    // Create user profile
    $stmt = $conn->prepare('INSERT INTO user_profile (user_id, email) VALUES (?, ?)');
    $stmt->bind_param('is', $userId, $email);
    $stmt->execute();
    $stmt->close();

    // Mark verification code as used
    $stmt = $conn->prepare('UPDATE email_verification_codes SET verified=1, verified_at=NOW() WHERE id=?');
    $stmt->bind_param('i', $codeId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode(['ok'=>true,'message'=>'Registration successful! You can now login.']);

} catch (Exception $e) {
    if (isset($conn) && $conn && $conn->in_transaction) {
        $conn->rollback();
    }
    error_log('Verify code registration error: ' . $e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'system','debug'=>$e->getMessage()]);
} finally {
    if (isset($conn) && $conn) $conn->close();
}
} catch (Exception $e) {
    // Catch any require/include errors
    echo json_encode(['ok'=>false,'error'=>'config_error','debug'=>$e->getMessage()]);
}
?>