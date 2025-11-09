<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once(__DIR__ . '/../../config/db.php');

try {
    // Log detailed request information
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content Type: " . $_SERVER['CONTENT_TYPE']);
    error_log("Raw POST data: " . file_get_contents("php://input"));
    error_log("Processed POST data: " . print_r($_POST, true));

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required_fields = ['username', 'password', 'user_type', 'status'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $user_type_id = $_POST['user_type'];
    $user_status_id = $_POST['status'];

    // Start transaction
    $conn->begin_transaction();

    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT user_id FROM user WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        throw new Exception("Username already exists");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO user (username, password, user_type_id, user_status_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $username, $hashed_password, $user_type_id, $user_status_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create user: " . $stmt->error);
    }
    
    // Get the new user's ID
    $user_id = $stmt->insert_id;
    error_log("New user ID: {$user_id}");
    
    // Create user profile
    $profile_stmt = $conn->prepare("INSERT INTO user_profile (user_id) VALUES (?)");
    $profile_stmt->bind_param("i", $user_id);
    $profile_stmt->execute();

    // If this is a tourism officer (user_type_id = 3), create tourism office entry
    if ($user_type_id == 3) {
        // Validate town_id is provided
        if (!isset($_POST['town_id']) || empty($_POST['town_id'])) {
            throw new Exception("Municipality selection is required for tourism officers");
        }

        $town_id = $_POST['town_id'];
        
        // Verify town exists
        $town_check = $conn->prepare("SELECT town_id FROM towns WHERE town_id = ?");
        $town_check->bind_param("i", $town_id);
        $town_check->execute();
        if ($town_check->get_result()->num_rows === 0) {
            throw new Exception("Selected municipality does not exist");
        }
        
        // Create tourism office record
        $office_stmt = $conn->prepare("INSERT INTO tourism_office (user_id, town_id, office_name) VALUES (?, ?, ?)");
        $office_name = "Tourism Office"; // Can be updated later with profile info
        $office_stmt->bind_param("iis", $user_id, $town_id, $office_name);
        
        if (!$office_stmt->execute()) {
            throw new Exception("Failed to create tourism office record: " . $office_stmt->error);
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "User created successfully"
    ]);

} catch (Exception $e) {
    if ($conn && $conn->connect_error === null) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>