<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once(__DIR__ . '/../../config/db.php');

function logError($message) {
    error_log(date('Y-m-d H:i:s') . " [towns/update.php] " . $message);
}

try {
    logError("Request received - POST: " . json_encode($_POST));
    logError("Files received: " . json_encode($_FILES));    // Validate input
    $town_id = isset($_POST['town_id']) ? intval($_POST['town_id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';

    if (!$town_id) {
        throw new Exception("Town ID is required");
    }
    
    if (empty($name)) {
        throw new Exception("Town name is required");
    }

    // Start transaction
    $conn->begin_transaction();

    // Handle image upload if present
    $image_path = null;    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/uploads/';
        logError("Upload directory path: " . $uploadDir);
        
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                logError("Failed to create directory: " . $uploadDir);
                throw new Exception('Failed to create upload directory');
            }
        }

        // Generate unique filename
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;
        
        // Validate file type
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $_FILES['image']['tmp_name']);
        finfo_close($fileInfo);
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed.');
        }

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            logError("Failed to move uploaded file");
            throw new Exception('Failed to upload image');
        }
        
        $image_path = $filename;
        logError("File uploaded successfully: " . $filename);
        
        // Update with new image
        $stmt = $conn->prepare("UPDATE towns SET name = ?, image_path = ? WHERE town_id = ?");
        $stmt->bind_param("ssi", $name, $image_path, $town_id);    } else {
        // Update without changing image
        $stmt = $conn->prepare("UPDATE towns SET name = ? WHERE town_id = ?");
        $stmt->bind_param("si", $name, $town_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to update town: " . $stmt->error);
    }

    $conn->commit();
    logError("Successfully updated town ID: " . $town_id);
    
    echo json_encode([
        "success" => true,
        "message" => "Town updated successfully",
        "town_id" => $town_id
    ]);

} catch(Exception $e) {
    logError("Error: " . $e->getMessage());
    
    if (isset($conn) && $conn->connect_error === null) {
        $conn->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
