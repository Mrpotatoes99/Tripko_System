<?php
// Quick diagnostic: check which DB this app connects to and show a user's key login flags.
// Usage: /tripko-system/tripko-backend/tools/diagnose_login.php?u=<username-or-email>
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../config/Database.php';

$u = trim($_GET['u'] ?? '');
if ($u === '') {
    echo "Provide ?u=<username-or-email>\n";
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "Connected to MySQL host on port: ".$conn->host_info."\n";
    echo "Selected DB: tripko_db\n\n";

    $isEmail = filter_var($u, FILTER_VALIDATE_EMAIL);
    $sql = $isEmail ?
        'SELECT user_id, username, email, user_type_id, user_status_id, is_email_verified, town_id, last_login_at FROM user WHERE email=? LIMIT 1'
        : 'SELECT user_id, username, email, user_type_id, user_status_id, is_email_verified, town_id, last_login_at FROM user WHERE username=? LIMIT 1';
    $st = $conn->prepare($sql);
    $st->bind_param('s', $u);
    $st->execute();
    $res = $st->get_result();
    if ($res->num_rows !== 1) {
        echo "User not found in this database.\n";
        exit;
    }
    $row = $res->fetch_assoc();
    $st->close();

    echo "user_id:           ".$row['user_id']."\n";
    echo "username:          ".$row['username']."\n";
    echo "email:             ".$row['email']."\n";
    echo "user_type_id:      ".$row['user_type_id']."\n";
    echo "user_status_id:    ".$row['user_status_id']." (1 means active)\n";
    echo "is_email_verified: ".$row['is_email_verified']." (1 means verified)\n";
    echo "town_id:           ".$row['town_id']."\n";
    echo "last_login_at:     ".$row['last_login_at']."\n\n";

    echo "Login blockers if any:\n";
    if ((int)$row['user_status_id'] !== 1) echo "- Account is inactive (user_status_id != 1)\n";
    if ((int)$row['is_email_verified'] !== 1) echo "- Email is not verified (is_email_verified != 1)\n";
    if ((int)$row['user_type_id'] === 3 && empty($row['town_id'])) echo "- Tourism officer has no town_id assigned (optional, but some features depend on it)\n";

    echo "\nIf these values don't match what you see in phpMyAdmin, you're probably editing a different MySQL port or database.\n";
    echo "This app connects using Database.php (default port 3307).\n";
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage();
}
