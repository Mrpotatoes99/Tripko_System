<?php
// One-time helper: create a Tourism Officer account (user_type_id=3) in the active DB
// Access: open in browser to use the simple form, or POST JSON/x-www-form-urlencoded
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config/Database.php';

function html($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Create Tourism Officer</title>';
    echo '<style>body{font-family:system-ui;padding:24px;background:#f8fafc;color:#0f172a}label{display:block;margin:.6rem 0 .2rem}input,select{padding:8px;border:1px solid #cbd5e1;border-radius:8px;width:320px}button{margin-top:12px;padding:10px 14px;border:none;border-radius:8px;background:#0ea5e9;color:#fff;font-weight:700;cursor:pointer}.msg{margin-top:10px;font-weight:600}</style></head><body>';
    echo '<h2>Create Tourism Officer (user_type_id=3)</h2>';
    echo '<form method="POST" action=""><label>Username</label><input name="username" required>';
    echo '<label>Email</label><input type="email" name="email" required>';
    echo '<label>Password</label><input type="password" name="password" required>';
    echo '<label>Town ID (optional)</label><input type="number" name="town_id" placeholder="e.g., 123">';
    echo '<div><button type="submit">Create</button></div></form>';
    echo '<p class="msg">This writes to the DB configured in config/Database.php (with environment overrides if set).</p>';
    echo '</body></html>';
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
try {
    $username = trim($_POST['username'] ?? ($_GET['username'] ?? ''));
    $email = trim($_POST['email'] ?? ($_GET['email'] ?? ''));
    $password = $_POST['password'] ?? ($_GET['password'] ?? '');
    $town_id = $_POST['town_id'] ?? ($_GET['town_id'] ?? null);

    if ($username === '' || $email === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'missing_fields']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'bad_email']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Ensure user/profile tables exist (defensive)
    $conn->query("CREATE TABLE IF NOT EXISTS user (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE,
        email VARCHAR(255) UNIQUE,
        password VARCHAR(255) NOT NULL,
        user_type_id INT NOT NULL DEFAULT 2,
        user_status_id INT NOT NULL DEFAULT 1,
        is_email_verified TINYINT(1) DEFAULT 0,
        email_verified_at DATETIME NULL,
        town_id INT NULL,
        last_login_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $conn->query("CREATE TABLE IF NOT EXISTS user_profile (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Unique checks
    $s1 = $conn->prepare('SELECT user_id FROM user WHERE username=? LIMIT 1');
    $s1->bind_param('s', $username);
    $s1->execute();
    if ($s1->get_result()->num_rows > 0) { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'username_exists']); exit; }
    $s1->close();

    $s2 = $conn->prepare('SELECT user_id FROM user WHERE email=? LIMIT 1');
    $s2->bind_param('s', $email);
    $s2->execute();
    if ($s2->get_result()->num_rows > 0) { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'email_exists']); exit; }
    $s2->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $town_id_val = ($town_id === null || $town_id === '') ? null : (int)$town_id;

    $ins = $conn->prepare('INSERT INTO user (username, email, password, user_type_id, user_status_id, is_email_verified, email_verified_at, town_id) VALUES (?, ?, ?, 3, 1, 1, NOW(), ?)');
    $ins->bind_param('sssi', $username, $email, $hash, $town_id_val);
    if (!$ins->execute()) { throw new Exception('Insert user failed: '.$conn->error); }
    $uid = $conn->insert_id;
    $ins->close();

    $p = $conn->prepare('INSERT INTO user_profile (user_id, email) VALUES (?, ?)');
    $p->bind_param('is', $uid, $email);
    $p->execute();
    $p->close();

    echo json_encode(['ok'=>true,'user_id'=>$uid]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'system','debug'=>$e->getMessage()]);
}
