<?php
// Database setup and table creation
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config/Database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Create email verification codes table
    $createTable = "
        CREATE TABLE IF NOT EXISTS email_verification_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(150) NOT NULL,
            code CHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified TINYINT(1) NOT NULL DEFAULT 0,
            verified_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_code (code),
            INDEX idx_expires (expires_at),
            UNIQUE KEY unique_active_email (email, verified)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if (!$conn->query($createTable)) {
        throw new Exception("Failed to create email_verification_codes table: " . $conn->error);
    }
    
    // Check if user table has required columns
    $userColumns = $conn->query("DESCRIBE user");
    $existingColumns = [];
    while ($row = $userColumns->fetch_assoc()) {
        $existingColumns[] = $row['Field'];
    }
    
    // Add missing columns to user table
    $requiredColumns = [
        'email' => 'VARCHAR(150) UNIQUE',
        'is_email_verified' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'email_verified_at' => 'DATETIME NULL',
        'last_login_at' => 'DATETIME NULL'
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            $alterQuery = "ALTER TABLE user ADD COLUMN $column $definition";
            if (!$conn->query($alterQuery)) {
                // Log but don't fail if column already exists
                error_log("Note: Could not add column $column: " . $conn->error);
            }
        }
    }
    
    echo json_encode([
        'ok' => true,
        'message' => 'Database and tables ready',
        'tables_created' => ['email_verification_codes'],
        'columns_checked' => array_keys($requiredColumns)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'setup_failed',
        'message' => $e->getMessage()
    ]);
}
?>