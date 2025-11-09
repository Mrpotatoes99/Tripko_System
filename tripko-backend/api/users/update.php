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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get user ID and validate
    $user_id = $_POST['user_id'] ?? null;
    if (!$user_id) {
        throw new Exception('User ID is required');
    }

    // Start transaction
    $conn->begin_transaction();

    // Update user table
    $update_user_sql = "UPDATE user SET user_type_id = ?, user_status_id = ?";
    $params = [$_POST['user_type'], $_POST['status']];
    $types = "ii";

    // Only update password if provided
    if (!empty($_POST['password'])) {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update_user_sql .= ", password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    // Add WHERE clause
    $update_user_sql .= " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= "i";

    $stmt = $conn->prepare($update_user_sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update user: " . $stmt->error);
    }

    // Update user profile
    $profile_sql = "UPDATE user_profile SET 
                    first_name = ?,
                    last_name = ?,
                    user_profile_dob = ?,
                    email = ?,
                    contact_number = ?
                    WHERE user_id = ?";
    
    $stmt = $conn->prepare($profile_sql);
    $stmt->bind_param("sssssi", 
        $_POST['first_name'] ?? null,
        $_POST['last_name'] ?? null,
        $_POST['user_profile_dob'] ?? null,
        $_POST['email'] ?? null,
        $_POST['contact_number'] ?? null,
        $user_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update user profile: " . $stmt->error);
    }

    // Handle profile photo if uploaded
    if (isset($_FILES['user_profile_photo']) && $_FILES['user_profile_photo']['size'] > 0) {
        $file = $_FILES['user_profile_photo'];
        $fileName = time() . '_' . basename($file['name']);
        $targetDir = __DIR__ . '/../../../../uploads/';
        $targetPath = $targetDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to upload profile photo");
        }

        // Update profile photo in database
        $photo_sql = "UPDATE user_profile SET user_profile_photo = ? WHERE user_id = ?";
        $stmt = $conn->prepare($photo_sql);
        $stmt->bind_param("si", $fileName, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update profile photo in database: " . $stmt->error);
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "User updated successfully"
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