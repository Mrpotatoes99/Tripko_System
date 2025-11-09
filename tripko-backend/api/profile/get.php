<?php
// Returns logged-in user's profile info
ini_set('display_errors',1);error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    // Detect if bio column exists to avoid 500 errors on older schema
    $bioExists = false;
    $colRes = $conn->query("SHOW COLUMNS FROM user_profile LIKE 'bio'");
    if($colRes && $colRes->num_rows === 1) { $bioExists = true; }
    $bioSelect = $bioExists ? 'up.bio' : "NULL AS bio";
    $sql = "SELECT u.user_id, u.username, u.user_type_id, up.first_name, up.last_name, up.email, up.contact_number, up.user_profile_dob, up.user_profile_photo, $bioSelect FROM user u LEFT JOIN user_profile up ON u.user_id = up.user_id WHERE u.user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$row = $res->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Profile not found']);
        exit;
    }
    // Derive initials
    $initials = '';
    if (!empty($row['first_name'])) $initials .= strtoupper($row['first_name'][0]);
    if (!empty($row['last_name'])) $initials .= strtoupper($row['last_name'][0]);
    $row['initials'] = $initials ?: strtoupper(substr($row['username'],0,2));
    // Normalize invalid MySQL zero date to null for frontend compatibility
    if(isset($row['user_profile_dob']) && $row['user_profile_dob']==='0000-00-00') {
        $row['user_profile_dob'] = null;
    }
    echo json_encode(['success'=>true,'profile'=>$row]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
