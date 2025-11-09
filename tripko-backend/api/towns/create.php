<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once(__DIR__ . '/../../config/db.php');

function logError($message) {
    error_log(date('Y-m-d H:i:s') . " [towns/create.php] " . $message);
}

try {
    logError("Request received - POST: " . json_encode($_POST));
    logError("Files received: " . json_encode($_FILES));
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    // Get POST data
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        throw new Exception('Municipality name is required');
    }

    // Handle image upload
    $image_path = null;    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/uploads/';
        logError("Upload directory path: " . $uploadDir);
        
        // Ensure directory exists with correct permissions
        if (!file_exists($uploadDir)) {
            logError("Creating directory: " . $uploadDir);
            if (!mkdir($uploadDir, 0777, true)) {
                logError("Failed to create directory");
                throw new Exception('Failed to create upload directory');
            }
        }
        
        // Check directory permissions
        if (!is_writable($uploadDir)) {
            logError("Directory not writable: " . $uploadDir);
            throw new Exception('Upload directory is not writable');
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
            logError("Invalid file type: " . $mimeType);
            throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed.');
        }

        logError("Moving file from: " . $_FILES['image']['tmp_name'] . " to: " . $targetFile);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $error = error_get_last();
            logError("Move failed: " . ($error ? json_encode($error) : 'Unknown error'));
            throw new Exception('Failed to upload image');
        }
        
        $image_path = $filename;
        logError("File successfully moved to: " . $targetFile);
    }

    // Database insertion
    $conn->begin_transaction();
    
    logError("Preparing to insert - Name: $name, Image: " . ($image_path ?? 'NULL'));
    
    $stmt = $conn->prepare("INSERT INTO towns (name, image_path, status) VALUES (?, ?, 'active')");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $name, $image_path);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to save municipality: " . $stmt->error);
    }

    $newTownId = $stmt->insert_id;
    $conn->commit();
    
    logError("Successfully created town with ID: " . $newTownId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Municipality created successfully',
        'town_id' => $newTownId
    ]);

} catch (Exception $e) {
    logError("Error: " . $e->getMessage());
    
    if (isset($conn) && $conn->connect_error === null) {
        $conn->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
