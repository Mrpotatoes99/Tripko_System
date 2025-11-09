<?php
// Cleanup script: purge expired/old tokens and 2FA codes.
// Run manually or schedule via Windows Task Scheduler.
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Create tables if they don't exist (defensive)
    $conn->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expiry_time DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id), INDEX(token_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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

    // Purge expired password reset tokens older than 1 day or used over 30 days ago
    $res1 = $conn->query("DELETE FROM password_reset_tokens WHERE expiry_time < NOW() - INTERVAL 1 DAY OR (used = 1 AND used_at < NOW() - INTERVAL 30 DAY)");
    $cnt1 = $conn->affected_rows;

    // Purge 2FA codes that are expired or verified more than 1 day ago
    $res2 = $conn->query("DELETE FROM reset_password_2fa_codes WHERE expires_at < NOW() OR (verified = 1 AND verified_at < NOW() - INTERVAL 1 DAY)");
    $cnt2 = $conn->affected_rows;

    header('Content-Type: text/plain; charset=UTF-8');
    echo "Cleanup complete\n";
    echo "Deleted password_reset_tokens: $cnt1\n";
    echo "Deleted reset_password_2fa_codes: $cnt2\n";
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Cleanup error: ' . $e->getMessage();
}
