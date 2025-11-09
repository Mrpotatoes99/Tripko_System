<?php
// Verify 2FA code for tourism officer login
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { echo json_encode(['ok'=>true]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'method']); exit; }

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/Database.php';

if (!isset($_SESSION['2fa_pending']['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'no_pending']); exit; }
$pending = $_SESSION['2fa_pending'];
$userId = (int)$pending['user_id'];
$code = trim($_POST['code'] ?? '');
if (!preg_match('/^\d{6}$/', $code)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad_code']); exit; }

try {
  $db = new Database();
  $c = $db->getConnection();
  $row = null;
  if ($stmt = $c->prepare('SELECT id FROM login_2fa_codes WHERE user_id=? AND code=? AND verified=0 AND expires_at > NOW() LIMIT 1')) {
    $stmt->bind_param('is', $userId, $code);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      if ($res && $res->num_rows === 1) { $row = $res->fetch_assoc(); }
    }
    $stmt->close();
  }
  if (!$row) {
    // Fallback: accept session-stored code if DB path failed
    if (!empty($_SESSION['2fa_pending']['fallback_code']) && $_SESSION['2fa_pending']['fallback_code'] === $code) {
      // ok without DB update
    } else {
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'invalid_or_expired']);
      exit;
    }
  } else {
    if ($u = $c->prepare('UPDATE login_2fa_codes SET verified=1, verified_at=NOW() WHERE id=?')) {
      $u->bind_param('i', $row['id']);
      $u->execute();
      $u->close();
    }
  }

  // Finalize full session
  $_SESSION['user_id'] = $pending['user_id'];
  $_SESSION['username'] = $pending['username'];
  $_SESSION['user_type_id'] = $pending['user_type_id'];
  if (!empty($pending['town_id'])) { $_SESSION['town_id'] = $pending['town_id']; }
  $_SESSION['expires'] = time() + (2*60*60);
  session_regenerate_id(true);
  unset($_SESSION['2fa_pending']);
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  error_log('verify_2fa error: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'system']);
}
?>