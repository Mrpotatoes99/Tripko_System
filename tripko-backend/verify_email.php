<?php
require_once __DIR__.'/config/Database.php';
$token = $_GET['token'] ?? '';
$uid = $_GET['uid'] ?? '';
if ($token===''||$uid===''||!ctype_digit($uid)){ echo 'Invalid link'; exit; }

$db = new Database(); $c = $db->getConnection();
$hash = hash('sha256',$token);
$now = date('Y-m-d H:i:s');

$st = $c->prepare('SELECT vt.id,vt.expiry_time,vt.verified,u.is_email_verified FROM verification_tokens vt JOIN user u ON vt.user_id=u.user_id WHERE vt.user_id=? AND vt.token_hash=? LIMIT 1');
$st->bind_param('is',$uid,$hash);
$st->execute();
$res = $st->get_result();
if ($res->num_rows===0){ echo 'Invalid or used token'; exit; }
$row = $res->fetch_assoc();
if ($row['verified'] || $row['is_email_verified']) { echo 'Already verified'; exit; }
if ($row['expiry_time'] < $now){ echo 'Token expired'; exit; }
$st->close();

$c->begin_transaction();
$st = $c->prepare('UPDATE user SET is_email_verified=1,email_verified_at=? WHERE user_id=?');
$st->bind_param('si',$now,$uid); $st->execute(); $st->close();
$st = $c->prepare('UPDATE verification_tokens SET verified=1, verified_at=? WHERE user_id=? AND token_hash=?');
$st->bind_param('sis',$now,$uid,$hash); $st->execute(); $st->close();
$c->commit();

echo 'Email verified! You may now <a href=\"../tripko-frontend/file_html/SignUp_LogIn_Form.php\">login</a>.';