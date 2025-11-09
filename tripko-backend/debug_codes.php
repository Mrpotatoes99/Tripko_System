<?php
// Debug verification codes
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config/Database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get all verification codes
    $result = $conn->query('SELECT * FROM email_verification_codes ORDER BY created_at DESC LIMIT 5');
    $codes = [];
    while ($row = $result->fetch_assoc()) {
        $codes[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'codes' => $codes
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>