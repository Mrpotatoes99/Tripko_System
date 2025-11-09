<?php
// Change password for the logged-in user and send notification email via Mailer (PHPMailer/Gmail)
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    echo json_encode(['success' => true]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$otp = trim($_POST['otp'] ?? '');
$init = isset($_POST['init']);

try {
    // Simple rate limiting: 5 attempts per 15 minutes per session
    $now = time();
    if (!isset($_SESSION['cpw_attempts']) || !is_array($_SESSION['cpw_attempts'])) {
        $_SESSION['cpw_attempts'] = ['count'=>0,'reset'=>$now + (15*60)];
    } elseif ($now > ($_SESSION['cpw_attempts']['reset'] ?? 0)) {
        $_SESSION['cpw_attempts'] = ['count'=>0,'reset'=>$now + (15*60)];
    }
    if (($_SESSION['cpw_attempts']['count'] ?? 0) >= 5) {
        http_response_code(429);
        echo json_encode(['success'=>false,'message'=>'Too many attempts. Please try again later.']);
        exit;
    }

    // Basic validation (unless just initiating OTP send)
    if (!$init && ($current === '' || $new === '' || $confirm === '')) {
        $_SESSION['cpw_attempts']['count']++;
        throw new Exception('All password fields are required');
    }
    if (!$init && $new !== $confirm) {
        $_SESSION['cpw_attempts']['count']++;
        throw new Exception('New password and confirmation do not match');
    }
    if (!$init && strlen($new) < 8) {
        $_SESSION['cpw_attempts']['count']++;
        throw new Exception('New password must be at least 8 characters');
    }
    // Optional strength checks: at least one letter and one number
    if (!$init && (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new))) {
        $_SESSION['cpw_attempts']['count']++;
        throw new Exception('New password must include letters and numbers');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Fetch current password hash, email, and user type
    $sql = 'SELECT u.password AS pwd, u.email AS user_email, up.email AS profile_email, u.username, u.user_type_id
            FROM user u LEFT JOIN user_profile up ON u.user_id = up.user_id
            WHERE u.user_id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows !== 1) {
        throw new Exception('User not found');
    }
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$init && !password_verify($current, $row['pwd'])) {
        $_SESSION['cpw_attempts']['count']++;
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Require OTP verification for all users
        // Create table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS change_password_2fa_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            code VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified TINYINT(1) DEFAULT 0,
            verified_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id), INDEX(code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        // Initiate: send code and return twofa required
        if ($init || !preg_match('/^\\d{6}$/', $otp)) {
            // Only one active code: check existing unverified
            $ck = $conn->prepare('SELECT id FROM change_password_2fa_codes WHERE user_id=? AND verified=0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
            $ck->bind_param('i', $userId);
            $ck->execute();
            $has = $ck->get_result()->num_rows === 1;
            $ck->close();
            if (!$has) {
                $code = sprintf('%06d', mt_rand(0, 999999));
                $s = $conn->prepare('INSERT INTO change_password_2fa_codes (user_id, code, expires_at, verified) VALUES (?, ?, NOW() + INTERVAL 10 MINUTE, 0)');
                $s->bind_param('is', $userId, $code);
                $s->execute();
                $s->close();
                $toEmail = $row['user_email'] ?: $row['profile_email'];
                if ($toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                    require_once __DIR__ . '/../../tools/Mailer.php';
                    @Mailer::send($toEmail, 'TripKo: Confirm password change', "Your verification code is: $code\n\nThis code expires in 10 minutes.");
                }
            }
            http_response_code(202);
            echo json_encode(['success'=>false,'twofa'=>'required','message'=>'A verification code was sent to your email. Include the 6-digit code as "otp" to continue.']);
            exit;
        }
        // Verify provided OTP
        $v = $conn->prepare('SELECT id FROM change_password_2fa_codes WHERE user_id=? AND code=? AND verified=0 AND expires_at > NOW() LIMIT 1');
        $v->bind_param('is', $userId, $otp);
        $v->execute();
        $rr = $v->get_result();
        if ($rr->num_rows !== 1) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Invalid or expired verification code']);
            exit;
        }
        $row2 = $rr->fetch_assoc();
        $v->close();
        $m = $conn->prepare('UPDATE change_password_2fa_codes SET verified=1, verified_at=NOW() WHERE id=?');
        $m->bind_param('i', $row2['id']);
        $m->execute();
        $m->close();
    // end require OTP for all
    

    $hash = password_hash($new, PASSWORD_DEFAULT);

    $ustmt = $conn->prepare('UPDATE user SET password=? WHERE user_id=?');
    $ustmt->bind_param('si', $hash, $userId);
    if (!$ustmt->execute()) {
        throw new Exception('Failed to update password');
    }
    $ustmt->close();

    // Send notification email (best-effort)
    $toEmail = $row['user_email'] ?: $row['profile_email'];
    if ($toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            require_once __DIR__ . '/../../tools/Mailer.php';
            $subject = 'TripKo Security Notice: Your password was changed';
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown IP';
            $time = date('Y-m-d H:i:s');
            $body = "Hello " . ($row['username'] ?: 'user') . ",\n\n" .
                    "This is a confirmation that your TripKo account password was changed on $time from IP $ip.\n\n" .
                    "If you didn’t make this change, please reset your password immediately and contact support.";
            // Fire-and-forget; we don't fail the password change if email delivery fails
            @Mailer::send($toEmail, $subject, $body);
        } catch (Throwable $e) {
            // Log silently
            error_log('change_password mail notice failed: '.$e->getMessage());
        }
    }

    // Reset attempt counter on success
    $_SESSION['cpw_attempts'] = ['count'=>0,'reset'=>time() + (15*60)];
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
