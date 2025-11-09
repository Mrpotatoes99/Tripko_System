<?php
// Update logged-in user's profile (basic fields + optional avatar upload)
ini_set('display_errors',1);error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$dob = trim($_POST['user_profile_dob'] ?? '');
$bio = trim($_POST['bio'] ?? '');

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->begin_transaction();

    // Fetch current account email to detect changes
    $curStmt = $conn->prepare('SELECT email, username FROM user WHERE user_id=? LIMIT 1');
    $curStmt->bind_param('i', $user_id);
    $curStmt->execute();
    $curRes = $curStmt->get_result();
    $currentUser = $curRes->fetch_assoc();
    $curStmt->close();

    // Basic update; ensure profile row exists
    $ensure = $conn->prepare('INSERT IGNORE INTO user_profile (user_id) VALUES (?)');
    $ensure->bind_param('i',$user_id);
    $ensure->execute();

    // Detect optional columns (e.g. bio) to avoid failures on older schema
    $bioExists = false;
    $colRes = $conn->query("SHOW COLUMNS FROM user_profile LIKE 'bio'");
    if($colRes && $colRes->num_rows === 1) $bioExists = true;

    if($bioExists) {
        $sql = 'UPDATE user_profile SET first_name=?, last_name=?, email=?, contact_number=?, user_profile_dob=?, bio=? WHERE user_id=?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssi',$first,$last,$email,$contact,$dob,$bio,$user_id);
    } else {
        $sql = 'UPDATE user_profile SET first_name=?, last_name=?, email=?, contact_number=?, user_profile_dob=? WHERE user_id=?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssi',$first,$last,$email,$contact,$dob,$user_id);
    }
    if(!$stmt->execute()) throw new Exception('Failed updating profile: '.$stmt->error);

    $emailChanged = false;
    $verificationSent = false;
    // If email provided and different from account email, sync to user.email with uniqueness check
    if ($email !== '' && isset($currentUser['email']) && strcasecmp($email, (string)$currentUser['email']) !== 0) {
        // ensure unique
        $chk = $conn->prepare('SELECT user_id FROM user WHERE email=? AND user_id<>? LIMIT 1');
        $chk->bind_param('si', $email, $user_id);
        $chk->execute();
        $dupe = $chk->get_result()->num_rows > 0;
        $chk->close();
        if ($dupe) {
            throw new Exception('Email address is already in use');
        }
        // update login email and mark unverified until user confirms
        $up = $conn->prepare('UPDATE user SET email=?, is_email_verified=0 WHERE user_id=?');
        $up->bind_param('si', $email, $user_id);
        if(!$up->execute()) throw new Exception('Failed syncing login email');
        $up->close();
        $emailChanged = true;

        // Generate verification token and send email
        $raw = bin2hex(random_bytes(16));
        $hash = hash('sha256', $raw);
        $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        // Ensure table exists (id, user_id, token, token_hash, expiry_time, verified, verified_at, created_at)
        $conn->query("CREATE TABLE IF NOT EXISTS verification_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NULL,
            token_hash VARCHAR(64) NOT NULL,
            expiry_time DATETIME NOT NULL,
            verified TINYINT(1) DEFAULT 0,
            verified_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id), INDEX(token_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $ins = $conn->prepare('INSERT INTO verification_tokens (user_id, token, token_hash, expiry_time, verified) VALUES (?,?,?,?,0)');
        $ins->bind_param('isss', $user_id, $raw, $hash, $expiry);
        $ins->execute();
        $ins->close();

                $base = getenv('PUBLIC_BASE_URL');
                if (!$base || !preg_match('/^https?:\/\//i',$base)) {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $base = $scheme.'://'.$host;
                }
                $verifyLink = rtrim($base,'/').'/tripko-system/tripko-backend/verify_email.php?token='.urlencode($raw).'&uid='.$user_id;
        // Send email via Mailer
        require_once __DIR__ . '/../../tools/Mailer.php';
        $subject = 'Confirm your new TripKo email address';
        $body = "Hello ".($currentUser['username'] ?? 'user').",\n\n".
                "We received a request to change the email for your TripKo account to this address.\n".
                "Please confirm by clicking the link below within 1 hour:\n\n".
                $verifyLink . "\n\nIf you didn’t request this change, please ignore this message.";
        @Mailer::send($email, $subject, $body);
        $verificationSent = true;
    }

    $photoFileName = null;
    if (!empty($_FILES['user_profile_photo']['name'])) {
        $file = $_FILES['user_profile_photo'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload error code '.$file['error']);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeExt = preg_replace('/[^a-zA-Z0-9]/','', $ext);
        $photoFileName = time().'_uid'.$user_id.'.'.($safeExt ?: 'jpg');
        // Save inside tripko-system/uploads (3 levels up from api/profile)
        $destDir = realpath(__DIR__ . '/../../..').DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
        if(!is_dir($destDir)) mkdir($destDir,0775,true);
        $destPath = $destDir.$photoFileName;
        if(!move_uploaded_file($file['tmp_name'],$destPath)) throw new Exception('Failed moving uploaded file');
        $pstmt = $conn->prepare('UPDATE user_profile SET user_profile_photo=? WHERE user_id=?');
        $pstmt->bind_param('si',$photoFileName,$user_id);
        $pstmt->execute();
    }

    $conn->commit();
    echo json_encode([
        'success'=>true,
        'message'=>'Profile updated',
        'photo'=>$photoFileName,
        'email_changed'=>$emailChanged,
        'verification_sent'=>$verificationSent,
        'debug'=>['bioColumn'=>$bioExists]
    ]);
} catch(Exception $e) {
    if(isset($conn) && $conn->errno === 0) { $conn->rollback(); }
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
