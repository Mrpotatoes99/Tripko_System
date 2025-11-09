<?php
header('Content-Type: application/json');
require_once('../../../../tripko-backend/config/Database.php');
require_once('../../../../tripko-backend/config/check_session.php');

checkTourismOfficerSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    $user_id = $_SESSION['user_id'];
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Check if email is already used by another user
    $check_query = "SELECT user_id FROM user WHERE email = ? AND user_id != ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
        exit;
    }
    
    // Update user profile
    $update_query = "UPDATE user SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        throw new Exception("Failed to update profile");
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
