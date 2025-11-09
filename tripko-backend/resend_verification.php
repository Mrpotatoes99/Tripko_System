<?php
header('Content-Type: application/json');
require_once __DIR__.'/config/Database.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'){ echo json_encode(['ok'=>false,'error'=>'method']); exit; }
$email = trim($_POST['email'] ?? '');
if ($email===''||!filter_var($email,FILTER_VALIDATE_EMAIL)){ echo json_encode(['ok'=>false,'error'=>'bad_email']); exit; }

$db = new Database(); $c = $db->getConnection();
$st = $c->prepare('SELECT user_id,username,is_email_verified FROM user WHERE email=? LIMIT 1');
$st->bind_param('s',$email); $st->execute(); $res=$st->get_result();
if ($res->num_rows===0){ echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }
$u = $res->fetch_assoc(); $st->close();
if ((int)$u['is_email_verified']===1){ echo json_encode(['ok'=>true,'already'=>true]); exit; }

$st = $c->prepare('SELECT COUNT(*) c FROM verification_tokens WHERE user_id=? AND created_at >= (NOW() - INTERVAL 1 HOUR)');
$st->bind_param('i',$u['user_id']); $st->execute(); $count=$st->get_result()->fetch_assoc()['c']; $st->close();
if ($count >= 3){ echo json_encode(['ok'=>false,'error'=>'rate']); exit; }

$raw = bin2hex(random_bytes(32));
$hash = hash('sha256',$raw);
$expiry = date('Y-m-d H:i:s', time()+1800);
$st = $c->prepare('INSERT INTO verification_tokens (user_id, token, token_hash, expiry_time, verified) VALUES (?,?,?,?,0)');
$st->bind_param('isss',$u['user_id'],$raw,$hash,$expiry); $st->execute(); $st->close();
$base = getenv('PUBLIC_BASE_URL');
if (!$base || !preg_match('/^https?:\/\//i',$base)) {
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$base = $scheme.'://'.$host;
}
$verifyLink = rtrim($base,'/').'/tripko-system/tripko-backend/verify_email.php?token='.urlencode($raw).'&uid='.$u['user_id'];
require_once __DIR__.'/tools/Mailer.php';
@Mailer::send($email,'Verify your TripKo account','Please verify your account: '.$verifyLink);
echo json_encode(['ok'=>true]);