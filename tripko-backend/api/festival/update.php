<?php
ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

session_start();
require_once(__DIR__ . '/../../config/check_session.php');

// Check admin session
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Block Super Admin (user_type_id == 1) from performing write operations
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    header('HTTP/1.1 403 Forbidden');
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Forbidden: Super Admin accounts cannot modify festivals.']);
    exit;
}

// If Tourism Officer, ensure the festival belongs to their municipality when updating
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3) {
    $officer_town = $_SESSION['town_id'] ?? null;
    // If festival_id supplied, check town ownership
    $maybe_id = null;
    if (isset($_POST['festival_id'])) $maybe_id = $_POST['festival_id'];
    else {
        $json_in = json_decode(file_get_contents('php://input'), true);
        if ($json_in && isset($json_in['festival_id'])) $maybe_id = $json_in['festival_id'];
    }
    if ($maybe_id && $officer_town) {
        $database = new Database();
        $db = $database->getConnection();
        $chk = $db->prepare("SELECT town_id FROM festivals WHERE festival_id = ? LIMIT 1");
        $chk->bind_param('i', $maybe_id);
        $chk->execute();
        $res = $chk->get_result();
        $row = $res->fetch_assoc();
        if (!$row || strval($row['town_id']) !== strval($officer_town)) {
            header('HTTP/1.1 403 Forbidden');
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to edit this festival.']);
            exit;
        }
    }
}

include_once __DIR__ . '/../../config/Database.php';
include_once __DIR__ . '/../../models/Festival.php';

try {
    $data = [];
    
    // Handle both POST form data and JSON input
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
        
        // Convert municipality to town_id if present
        if (isset($data['municipality']) && !isset($data['town_id'])) {
            $data['town_id'] = $data['municipality'];
        }
        
        // Handle file upload (support both single and multiple files)
        if (isset($_FILES) && !empty($_FILES)) {
            $uploadDir = __DIR__ . '/../../../../uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Handle single image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $fileInfo = pathinfo($_FILES['image']['name']);
                $filename = uniqid() . '_' . time() . '.' . $fileInfo['extension'];
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $data['image_path'] = $filename;
                } else {
                    throw new Exception('Failed to upload image file');
                }
            }
            // Handle multiple images upload
            elseif (isset($_FILES['images'])) {
                foreach ($_FILES['images']['error'] as $key => $error) {
                    if ($error === UPLOAD_ERR_OK) {
                        $fileInfo = pathinfo($_FILES['images']['name'][$key]);
                        $filename = uniqid() . '_' . time() . '_' . $key . '.' . $fileInfo['extension'];
                        $targetFile = $uploadDir . $filename;
                        
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $targetFile)) {
                            $data['image_path'] = $filename; // Store the last uploaded image
                        } else {
                            throw new Exception('Failed to upload image file');
                        }
                    }
                }
            }
        }
    } else {
        $json_data = json_decode(file_get_contents("php://input"), true);
        if ($json_data) {
            $data = $json_data;
        }
    }

    if (empty($data)) {
        throw new Exception('No data provided');
    }

    error_log('Received data for festival update: ' . print_r($data, true));
    
    // Validate required fields
    $required_fields = ['festival_id', 'name', 'description', 'date', 'town_id'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing_fields));
    }

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Start transaction
    $db->begin_transaction();

    try {
        // Main festival update query
        $sql = "UPDATE festivals SET 
                name = ?, 
                description = ?, 
                date = ?, 
                town_id = ?,
                status = ?" .
                (isset($data['image_path']) ? ", image_path = ?" : "") .
                " WHERE festival_id = ?";

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement: ' . $db->error);
        }

        // Status handling - default to current status or 'active' if not provided
        $status = isset($data['status']) ? $data['status'] : 'active';

        if (isset($data['image_path'])) {
            $stmt->bind_param(
                "sssissi",
                $data['name'],
                $data['description'],
                $data['date'],
                $data['town_id'],
                $status,
                $data['image_path'],
                $data['festival_id']
            );
        } else {
            $stmt->bind_param(
                "sssisi",
                $data['name'],
                $data['description'],
                $data['date'],
                $data['town_id'],
                $status,
                $data['festival_id']
            );
        }

        if (!$stmt->execute()) {
            throw new Exception('Failed to update festival: ' . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception('No festival found with ID: ' . $data['festival_id']);
        }

        // Commit transaction
        $db->commit();

        // Success response
        header('HTTP/1.1 200 OK');
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Festival updated successfully',
            'festival_id' => $data['festival_id']
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error in festival update.php: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>