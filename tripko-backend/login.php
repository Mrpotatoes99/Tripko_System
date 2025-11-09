<?php
session_start();
require_once __DIR__.'/config/Database.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location: ../tripko-frontend/file_html/SignUp_LogIn_Form.php'); exit; }

$id = trim($_POST['username'] ?? '');
$pw = $_POST['password'] ?? '';
if ($id===''||$pw===''){ header('Location: ../tripko-frontend/file_html/SignUp_LogIn_Form.php?error=empty'); exit; }

$db = new Database();
$c = $db->getConnection();
$isEmail = filter_var($id,FILTER_VALIDATE_EMAIL);
$sql = $isEmail
 ? 'SELECT user_id,username,email,password,user_type_id,user_status_id,is_email_verified,town_id FROM user WHERE email=? LIMIT 1'
 : 'SELECT user_id,username,email,password,user_type_id,user_status_id,is_email_verified,town_id FROM user WHERE username=? LIMIT 1';

$st = $c->prepare($sql);
$st->bind_param('s',$id);
$st->execute();
$r = $st->get_result();
if ($r->num_rows!==1){ header('Location: ../tripko-frontend/file_html/SignUp_LogIn_Form.php?error=notfound'); exit; }
$u = $r->fetch_assoc();
$st->close();

if (!password_verify($pw,$u['password'])){ header('Location: ../tripko-frontend/file_html/SignUp_LogIn_Form.php?error=invalid'); exit; }
if ((int)$u['user_status_id']!==1){ header('Location: ../tripko-frontend/file_html/SignUp_LogIn_Form.php?error=inactive'); exit; }
if ((int)$u['is_email_verified']!==1){ header('Location: ../tripko-frontend/file_html/SignUp_LogIn_Form.php?error=unverified&email='.urlencode($u['email'])); exit; }

// For Tourism Officer accounts (user_type_id=3)
if ((int)$u['user_type_id']===3) {
  // If no email is configured for this account, temporarily bypass 2FA so officers can log in
  // SECURITY NOTE: Encourage adding an email ASAP; this bypass is intended for local/dev use only.
  if (empty($u['email']) || !filter_var($u['email'], FILTER_VALIDATE_EMAIL)) {
    // Promote to full session directly
    $_SESSION['user_id']=$u['user_id'];
    $_SESSION['username']=$u['username'];
    $_SESSION['user_type_id']=$u['user_type_id'];
    if (!empty($u['town_id'])) { $_SESSION['town_id'] = $u['town_id']; }
    $_SESSION['expires'] = time() + (2 * 60 * 60);
    session_regenerate_id(true);
    try { $c->query('UPDATE user SET last_login_at=NOW() WHERE user_id='.(int)$u['user_id']); } catch(Throwable $e){}
    header('Location: ../tripko-frontend/file_html/tourism_offices/dashboard.php');
    exit;
  }

  // Otherwise, require 2FA via email OTP
  try {
    // Prepare table for 2FA codes (best effort)
    if (!$c->query("CREATE TABLE IF NOT EXISTS login_2fa_codes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      code VARCHAR(6) NOT NULL,
      expires_at DATETIME NOT NULL,
      verified TINYINT(1) DEFAULT 0,
      verified_at DATETIME NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX(user_id), INDEX(code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;")) {
      error_log('2FA table create error: '.$c->error);
    }

    // Reuse existing active code if present
    $code = null;
    if ($stmt0 = $c->prepare('SELECT code FROM login_2fa_codes WHERE user_id=? AND verified=0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1')) {
      $stmt0->bind_param('i', $u['user_id']);
      if ($stmt0->execute()) {
        $res0 = $stmt0->get_result();
        if ($row0 = $res0->fetch_assoc()) { $code = $row0['code']; }
      }
      $stmt0->close();
    }
    $usedSessionCode = false;
    if (!$code) {
      $code = sprintf('%06d', mt_rand(0,999999));
      if ($stmt = $c->prepare('INSERT INTO login_2fa_codes (user_id, code, expires_at, verified) VALUES (?, ?, NOW() + INTERVAL 10 MINUTE, 0)')) {
        $stmt->bind_param('is', $u['user_id'], $code);
        if (!$stmt->execute()) {
          error_log('2FA insert error: '.$c->error);
          $usedSessionCode = true; // fallback to session if insert fails
        }
        $stmt->close();
      } else {
        error_log('2FA prepare error: '.$c->error);
        $usedSessionCode = true;
      }
    }

    // Send code to user's email
    @require_once __DIR__ . '/tools/Mailer.php';
    if (class_exists('Mailer')) {
      $subject = 'TripKo Tourism Office Login Code';
      $body = "Your TripKo login verification code is: $code\n\nThis code will expire in 10 minutes.";
      @Mailer::send($u['email'], $subject, $body);
    }

    // Store pending session and redirect to 2FA page
    $_SESSION['2fa_pending'] = [
      'user_id' => $u['user_id'],
      'username' => $u['username'],
      'user_type_id' => $u['user_type_id'],
      'town_id' => $u['town_id'],
      'fallback_code' => $usedSessionCode ? $code : null
    ];
    header('Location: ../tripko-frontend/file_html/tourism_offices/verify_2fa.php');
    exit;
  } catch (Throwable $e) {
    error_log('2FA init error: '.$e->getMessage());
    header('Location: ../tripko-frontend/file_html/SignUp_LogIn_Form.php?error=system');
    exit;
  }
}

// Default path: set full session for other user types
$_SESSION['user_id']=$u['user_id'];
$_SESSION['username']=$u['username'];
$_SESSION['user_type_id']=$u['user_type_id'];
// Initialize session expiration (2 hours) and regenerate ID for fixation protection
$_SESSION['expires'] = time() + (2 * 60 * 60);
session_regenerate_id(true);

try { $c->query('UPDATE user SET last_login_at=NOW() WHERE user_id='.(int)$u['user_id']); } catch(Throwable $e){}

if ((int)$u['user_type_id']===1) {
  header('Location: ../tripko-frontend/file_html/admin/dashboard.php');
  exit;
}
if ((int)$u['user_type_id']===3) {
  // Store town_id if present
  if (!empty($u['town_id'])) { $_SESSION['town_id'] = $u['town_id']; }
  header('Location: ../tripko-frontend/file_html/tourism_offices/dashboard.php'); exit;
}
// Default regular user
header('Location: ../tripko-frontend/file_html/homepage.php');
exit;