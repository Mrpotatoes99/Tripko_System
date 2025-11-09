<?php
// GET: show reset form; POST: perform reset after validating token
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/config/Database.php';

function render_form($error = '', $ok = false, $require2fa = false, $notice = '') {
    $token = htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES);
    $uid = htmlspecialchars($_GET['uid'] ?? '', ENT_QUOTES);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<title>Reset Password - TripKo</title>'
        .'<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f6f8fa;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px;margin:0}.'
        .'card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 6px 18px -7px rgba(16,50,60,.15);padding:22px;max-width:460px;width:100%}.'
        .'title{margin:0 0 6px 0;color:#10323c}label{font-weight:600;color:#365057}input{width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px}.'
        .'btn{background:#00a6b8;color:#fff;border:none;padding:10px 14px;border-radius:10px;font-weight:700;cursor:pointer}.'
        .'muted{color:#6b7280;font-size:.9rem}.err{color:#b91c1c;font-weight:600;margin-bottom:8px}.ok{color:#0f766e;font-weight:600;margin-bottom:8px}</style></head><body>'
        .'<div class="card">'
        .'<h2 class="title">Reset Password</h2>'
        .($error ? '<div class="err">'.htmlspecialchars($error,ENT_QUOTES).'</div>' : '')
        .($ok ? '<div class="ok">Password updated. You can now close this tab and log in.</div>' : '')
        .($notice && !$ok ? '<div class="ok">'.htmlspecialchars($notice,ENT_QUOTES).'</div>' : '')
        .'<form method="POST" action="">'
        .'<input type="hidden" name="token" value="'.$token.'">'
        .'<input type="hidden" name="uid" value="'.$uid.'">'
        .'<div style="display:grid;gap:10px;margin:12px 0 16px 0">'
        .($require2fa ? '<div><label>Verification Code (sent to your email)</label><br><input type="text" name="otp" placeholder="6-digit code" maxlength="6"></div>' : '')
        .'<div><label>New Password</label><br><input type="password" name="new_password" required></div>'
        .'<div><label>Confirm Password</label><br><input type="password" name="confirm_password" required></div>'
        .'</div>'
        .'<button class="btn" type="submit">Set New Password</button>'
        .($require2fa ? '<button class="btn" type="submit" name="resend" value="1" style="margin-left:8px;background:#365057">Resend code</button>' : '')
        .'<p class="muted" style="margin-top:10px">It must be at least 8 characters and include letters and numbers.</p>'
        .'</form>'
        .'</div></body></html>';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // If tourism officer, send a 2FA code to email
    $uid = $_GET['uid'] ?? '';
    $need2fa = false;
    $notice = '';
    if (ctype_digit((string)$uid)) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $q = $conn->prepare('SELECT email, user_type_id FROM user WHERE user_id=? LIMIT 1');
            $q->bind_param('i', $uid);
            $q->execute();
            $res = $q->get_result();
            if ($row = $res->fetch_assoc()) {
                if ((int)$row['user_type_id'] === 3) {
                    $need2fa = true;
                    // Table for reset 2FA codes
                    $conn->query("CREATE TABLE IF NOT EXISTS reset_password_2fa_codes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        code VARCHAR(6) NOT NULL,
                        expires_at DATETIME NOT NULL,
                        verified TINYINT(1) DEFAULT 0,
                        verified_at DATETIME NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX(user_id), INDEX(code)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    // Opportunistic cleanup
                    $conn->query("DELETE FROM reset_password_2fa_codes WHERE expires_at < NOW() - INTERVAL 1 DAY");
                    // Only send if no active unverified code exists
                    $ck = $conn->prepare('SELECT code FROM reset_password_2fa_codes WHERE user_id=? AND verified=0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
                    $ck->bind_param('i', $uid);
                    $ck->execute();
                    $active = $ck->get_result();
                    $hasActive = ($active->num_rows === 1);
                    $ck->close();
                    if (!$hasActive) {
                        $code = sprintf('%06d', mt_rand(0,999999));
                        $s = $conn->prepare('INSERT INTO reset_password_2fa_codes (user_id, code, expires_at, verified) VALUES (?, ?, NOW() + INTERVAL 10 MINUTE, 0)');
                        $s->bind_param('is', $uid, $code);
                        $s->execute();
                        $s->close();
                        if ($row['email'] && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                            require_once __DIR__ . '/tools/Mailer.php';
                            @Mailer::send($row['email'], 'TripKo: Reset password verification code', "Your code is: $code\n\nThis code expires in 10 minutes.");
                            $notice = 'We sent a verification code to your email.';
                        }
                    } else {
                        $notice = 'Enter the verification code we sent to your email.';
                    }
                }
            }
        } catch (Throwable $e) { /* ignore and render without 2FA */ }
    }
    render_form('', false, $need2fa, $notice);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$token = $_POST['token'] ?? '';
$uid = $_POST['uid'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$otp = trim($_POST['otp'] ?? '');
$doResend = isset($_POST['resend']);

if ($token === '' || $uid === '' || !ctype_digit($uid)) { render_form('Invalid reset link'); exit; }
// When resending, we don't require password fields
if (!$doResend) {
    if ($new === '' || $confirm === '') { render_form('Please fill all fields'); exit; }
    if ($new !== $confirm) { render_form('Passwords do not match'); exit; }
    if (strlen($new) < 8 || !preg_match('/[A-Za-z]/',$new) || !preg_match('/\d/',$new)) { render_form('Password must be at least 8 chars and include letters and numbers'); exit; }
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $hash = hash('sha256', $token);
    $stmt = $conn->prepare('SELECT id, expiry_time, used FROM password_reset_tokens WHERE user_id=? AND token_hash=? LIMIT 1');
    $stmt->bind_param('is', $uid, $hash);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows !== 1) { render_form('Invalid or used link'); exit; }
    $row = $res->fetch_assoc();
    $stmt->close();
    if ((int)$row['used'] === 1) { render_form('This link was already used'); exit; }
    if ($row['expiry_time'] < date('Y-m-d H:i:s')) { render_form('This link has expired'); exit; }

    // Handle resend flow
    if ($doResend) {
        // Check if user is tourism officer
        $need2fa = false;
        $email = '';
        $q0 = $conn->prepare('SELECT email, user_type_id FROM user WHERE user_id=? LIMIT 1');
        $q0->bind_param('i', $uid);
        $q0->execute();
        $rq0 = $q0->get_result();
        if ($ru = $rq0->fetch_assoc()) { $need2fa = ((int)$ru['user_type_id'] === 3); $email = $ru['email']; }
        $q0->close();
        if (!$need2fa) { render_form('', false, false, 'No code needed for this account.'); exit; }

        // Rate limit: if a code was created in last 60 seconds, block resend
        $rl = $conn->prepare('SELECT created_at FROM reset_password_2fa_codes WHERE user_id=? AND verified=0 ORDER BY id DESC LIMIT 1');
        $rl->bind_param('i', $uid);
        $rl->execute();
        $rlr = $rl->get_result();
        if ($last = $rlr->fetch_assoc()) {
            $lastTs = strtotime($last['created_at']);
            if ($lastTs !== false && (time() - $lastTs) < 60) {
                render_form('', false, true, 'Please wait a minute before requesting another code.');
                exit;
            }
        }
        $rl->close();

        // Invalidate previous unverified codes (mark as verified=2)
        $conn->query('UPDATE reset_password_2fa_codes SET verified=2 WHERE user_id='.(int)$uid.' AND verified=0');

        // Create and send a new code
        $conn->query("CREATE TABLE IF NOT EXISTS reset_password_2fa_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            code VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified TINYINT(1) DEFAULT 0,
            verified_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id), INDEX(code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $code = sprintf('%06d', mt_rand(0,999999));
        $s = $conn->prepare('INSERT INTO reset_password_2fa_codes (user_id, code, expires_at, verified) VALUES (?, ?, NOW() + INTERVAL 10 MINUTE, 0)');
        $s->bind_param('is', $uid, $code);
        $s->execute();
        $s->close();
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            require_once __DIR__ . '/tools/Mailer.php';
            @Mailer::send($email, 'TripKo: Reset password verification code', "Your code is: $code\n\nThis code expires in 10 minutes.");
        }
        render_form('', false, true, 'We sent a new verification code to your email.');
        exit;
    }

    // If tourism officer, verify OTP
    $need2fa = false;
    $q = $conn->prepare('SELECT user_type_id FROM user WHERE user_id=? LIMIT 1');
    $q->bind_param('i', $uid);
    $q->execute();
    $qr = $q->get_result();
    if ($rr = $qr->fetch_assoc()) { $need2fa = ((int)$rr['user_type_id'] === 3); }
    $q->close();
    if ($need2fa) {
        if (!preg_match('/^\d{6}$/', $otp)) { render_form('Enter the 6-digit verification code sent to your email'); exit; }
        $v = $conn->prepare('SELECT id FROM reset_password_2fa_codes WHERE user_id=? AND code=? AND verified=0 AND expires_at > NOW() LIMIT 1');
        $v->bind_param('is', $uid, $otp);
        $v->execute();
        $rs = $v->get_result();
        if ($rs->num_rows !== 1) { render_form('Invalid or expired verification code'); exit; }
        $row2 = $rs->fetch_assoc();
        $v->close();
        $m = $conn->prepare('UPDATE reset_password_2fa_codes SET verified=1, verified_at=NOW() WHERE id=?');
        $m->bind_param('i', $row2['id']);
        $m->execute();
        $m->close();
    }

    $pwd = password_hash($new, PASSWORD_DEFAULT);
    $u = $conn->prepare('UPDATE user SET password=? WHERE user_id=?');
    $u->bind_param('si', $pwd, $uid);
    if (!$u->execute()) { render_form('Failed to update password, please try again'); exit; }
    $u->close();

    $m = $conn->prepare('UPDATE password_reset_tokens SET used=1, used_at=NOW() WHERE id=?');
    $m->bind_param('i', $row['id']);
    $m->execute();
    $m->close();

    render_form('', true);
} catch (Throwable $e) {
    error_log('reset_password error: '.$e->getMessage());
    render_form('Unexpected error. Please try again later.');
}
?>