<?php
require_once('../../config/init_session.php');
require_once('../../config/Database.php');
require_once('../../check_session.php');
header('Content-Type: application/json');

// Add detailed session debugging
error_log("Tourist Spots API - Full Session Data: " . print_r($_SESSION, true));
error_log("Tourist Spots API - Request Method: " . $_SERVER['REQUEST_METHOD']);

// Add debug logs for town_id
error_log("Initial town_id check: " . (isset($_SESSION['town_id']) ? $_SESSION['town_id'] : 'not set'));

// Ensure proper session data
if (!isset($_SESSION['user_id'])) {
    error_log("Tourist Spots API - Missing user_id in session");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

// Check for tourism officer type (type_id = 3) with improved logging
if (!isset($_SESSION['user_type_id'])) {
    error_log("Tourist Spots API - user_type_id is not set in session");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User type not found. Please login again.']);
    exit;
}

if ($_SESSION['user_type_id'] != 3) {
    error_log("Tourist Spots API - Invalid user type: " . $_SESSION['user_type_id']);
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Tourism officer access only.']);
    exit;
}

// Initialize database connection with error handling
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    error_log("Tourist Spots API - Database connection failed");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get town_id if not set in session with improved error handling
if (!isset($_SESSION['town_id'])) {
    try {
        error_log("Tourist Spots API - Fetching town_id for user_id: " . $_SESSION['user_id']);
        
        // First verify the user exists and is a tourism officer
        $verifyQuery = "SELECT user_id, user_type_id, town_id FROM user WHERE user_id = ?";
        $verifyStmt = $conn->prepare($verifyQuery);
        if (!$verifyStmt) {
            throw new Exception("Failed to prepare verification statement: " . $conn->error);
        }
        
        $verifyStmt->bind_param("i", $_SESSION['user_id']);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        $userData = $verifyResult->fetch_assoc();
        
        if (!$userData) {
            error_log("Tourist Spots API - User not found in database");
            throw new Exception("User not found in database");
        }
        
        if ($userData['user_type_id'] != 3) {
            error_log("Tourist Spots API - User is not a tourism officer. Type: " . $userData['user_type_id']);
            throw new Exception("Invalid user type");
        }
        
        if (!$userData['town_id']) {
            error_log("Tourist Spots API - No town assigned to user");
            throw new Exception("No town assigned to this tourism officer");
        }
        
        $_SESSION['town_id'] = $userData['town_id'];
        error_log("Tourist Spots API - Successfully retrieved town_id: " . $userData['town_id']);
        
    } catch (Exception $e) {
        error_log("Tourist Spots API - Error getting town_id: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

$town_id = $_SESSION['town_id'];
error_log("Tourist Spots API - Operating with town_id: " . $town_id);

// GET request - List or single tourist spot for the town
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (isset($_GET['id'])) {
            // Single spot fetch
            $id = (int)$_GET['id'];
            $query = "SELECT ts.*, gp.latitude, gp.longitude, gp.accuracy 
                      FROM tourist_spots ts 
                      LEFT JOIN geo_points gp ON gp.entity_type = 'tourist_spot' AND gp.entity_id = ts.spot_id
                      WHERE ts.spot_id = ? AND ts.town_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $id, $town_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Tourist spot not found']);
                exit;
            }
            // Return all fields expected by frontend
            $spot = [
                'spot_id' => (int)$row['spot_id'],
                'name' => htmlspecialchars($row['name']),
                'description' => htmlspecialchars($row['description']),
                'category' => htmlspecialchars($row['category']),
                'location' => htmlspecialchars($row['location'] ?? ''),
                'contact_info' => htmlspecialchars($row['contact_info'] ?? ''),
                'operating_hours' => htmlspecialchars($row['operating_hours'] ?? ''),
                'entrance_fee' => htmlspecialchars($row['entrance_fee'] ?? ''),
                'image_path' => htmlspecialchars($row['image_path'] ?? ''),
                'status' => htmlspecialchars($row['status']),
                'town_id' => (int)$row['town_id'],
                'created_at' => $row['created_at'] ?? '',
                'updated_at' => $row['updated_at'] ?? '',
                'latitude' => $row['latitude'] ?? '',
                'longitude' => $row['longitude'] ?? '',
                'accuracy' => $row['accuracy'] ?? 'exact'
            ];
            echo json_encode(['success' => true, 'spot' => $spot]);
            exit;
        }
        // List all spots for this town
        $query = "SELECT * FROM tourist_spots WHERE town_id = ? ORDER BY name ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $town_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $spots = [];
        while ($row = $result->fetch_assoc()) {
            $spots[] = [
                'spot_id' => (int)$row['spot_id'],
                'name' => htmlspecialchars($row['name']),
                'description' => htmlspecialchars($row['description']),
                'category' => htmlspecialchars($row['category']),
                'location' => htmlspecialchars($row['location'] ?? ''),
                'contact_info' => htmlspecialchars($row['contact_info'] ?? ''),
                'operating_hours' => htmlspecialchars($row['operating_hours'] ?? ''),
                'entrance_fee' => htmlspecialchars($row['entrance_fee'] ?? ''),
                'image_path' => htmlspecialchars($row['image_path'] ?? ''),
                'status' => htmlspecialchars($row['status']),
                'town_id' => (int)$row['town_id'],
                'created_at' => $row['created_at'] ?? '',
                'updated_at' => $row['updated_at'] ?? ''
            ];
        }
        echo json_encode([
            'success' => true,
            'spots' => $spots,
            'count' => count($spots)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving tourist spots: ' . $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Accept ID from query or body (FormData or JSON)
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$id && isset($_POST['spot_id'])) {
            $id = (int)$_POST['spot_id'];
        }
        if (!$id) {
            // Try JSON body
            $putData = json_decode(file_get_contents("php://input"), true);
            if (isset($putData['spot_id'])) {
                $id = (int)$putData['spot_id'];
            }
        }
        if (!$id) {
            throw new Exception('Tourist spot ID is required');
        }

        // Check if spot belongs to this tourism officer's town
        $checkQuery = "SELECT town_id FROM tourist_spots WHERE spot_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $spotData = $checkResult->fetch_assoc();
        if (!$spotData || $spotData['town_id'] != $town_id) {
            throw new Exception('You do not have permission to edit this tourist spot');
        }

        // Handle FormData (multipart) or JSON
        $data = $_POST;
        if (empty($data)) {
            $data = json_decode(file_get_contents("php://input"), true);
        }
        if (!$data) {
            throw new Exception('Invalid request data');
        }

        $updateQuery = "UPDATE tourist_spots SET 
                       name = ?,
                       description = ?,
                       category = ?,
                       location = ?,
                       contact_info = ?,
                       operating_hours = ?,
                       entrance_fee = ?,
                       status = ?
                       WHERE spot_id = ? AND town_id = ?";

        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param(
            "ssssssssii",
            $data['name'],
            $data['description'],
            $data['category'],
            $data['location'],
            $data['contact_info'],
            $data['operating_hours'],
            $data['entrance_fee'],
            $data['status'],
            $id,
            $town_id
        );
        if (!$stmt->execute()) {
            throw new Exception("Failed to update tourist spot: " . $stmt->error);
        }

        // Handle GPS coordinates update if provided
        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $latitude = floatval($data['latitude']);
            $longitude = floatval($data['longitude']);
            $accuracy = isset($data['accuracy']) && in_array($data['accuracy'], ['exact', 'approximate', 'centroid', 'imported']) 
                        ? $data['accuracy'] 
                        : 'approximate';

            // Validate coordinate ranges
            if ($latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
                $coordQuery = "INSERT INTO geo_points (entity_type, entity_id, latitude, longitude, accuracy, created_at) 
                              VALUES ('tourist_spot', ?, ?, ?, ?, NOW())
                              ON DUPLICATE KEY UPDATE 
                              latitude = VALUES(latitude), 
                              longitude = VALUES(longitude), 
                              accuracy = VALUES(accuracy),
                              updated_at = NOW()";
                
                $coordStmt = $conn->prepare($coordQuery);
                if ($coordStmt) {
                    $coordStmt->bind_param("idds", $id, $latitude, $longitude, $accuracy);
                    if (!$coordStmt->execute()) {
                        error_log("Failed to update coordinates for spot {$id}: " . $coordStmt->error);
                    } else {
                        error_log("Successfully updated coordinates for spot {$id}: ({$latitude}, {$longitude})");
                    }
                } else {
                    error_log("Failed to prepare coordinate update statement: " . $conn->error);
                }
            } else {
                error_log("Invalid coordinate ranges for spot {$id}: lat={$latitude}, lng={$longitude}");
            }
        }

        // Handle image upload if provided (FormData)
        $uploadDir = __DIR__ . '/../../../uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        if (isset($_FILES['images']) && is_array($_FILES['images'])) {
            $files = [];
            foreach ($_FILES['images']['name'] as $i => $name) {
                $files[] = [
                    'name' => $_FILES['images']['name'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i]
                ];
            }
            foreach ($files as $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid() . '_' . time() . '.' . $fileExt;
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $updateImageQuery = "UPDATE tourist_spots SET image_path = ? WHERE spot_id = ?";
                        $imgStmt = $conn->prepare($updateImageQuery);
                        if ($imgStmt) {
                            $imgStmt->bind_param("si", $fileName, $id);
                            $imgStmt->execute();
                        }
                        break;
                    }
                }
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $updateImageQuery = "UPDATE tourist_spots SET image_path = ? WHERE spot_id = ?";
                $imgStmt = $conn->prepare($updateImageQuery);
                if ($imgStmt) {
                    $imgStmt->bind_param("si", $fileName, $id);
                    $imgStmt->execute();
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Tourist spot updated successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,            'message' => $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $requiredFields = ['name', 'description', 'category', 'location'];
        $data = $_POST;

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Insert new tourist spot
        $insertQuery = "INSERT INTO tourist_spots (
            name, description, category, location, 
            contact_info, operating_hours, entrance_fee, 
            town_id, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";

        $stmt = $conn->prepare($insertQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare insert statement: " . $conn->error);
        }

        $stmt->bind_param(
            "sssssssi",
            $data['name'],
            $data['description'],
            $data['category'],
            $data['location'],
            $data['contact_info'],
            $data['operating_hours'],
            $data['entrance_fee'],
            $town_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create tourist spot: " . $stmt->error);
        }

        $newSpotId = $stmt->insert_id;

        // Handle GPS coordinates if provided
        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $latitude = floatval($data['latitude']);
            $longitude = floatval($data['longitude']);
            $accuracy = isset($data['accuracy']) && in_array($data['accuracy'], ['exact', 'approximate', 'centroid', 'imported']) 
                        ? $data['accuracy'] 
                        : 'approximate';

            // Validate coordinate ranges
            if ($latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
                $coordQuery = "INSERT INTO geo_points (entity_type, entity_id, latitude, longitude, accuracy, created_at) 
                              VALUES ('tourist_spot', ?, ?, ?, ?, NOW())
                              ON DUPLICATE KEY UPDATE 
                              latitude = VALUES(latitude), 
                              longitude = VALUES(longitude), 
                              accuracy = VALUES(accuracy),
                              updated_at = NOW()";
                
                $coordStmt = $conn->prepare($coordQuery);
                if ($coordStmt) {
                    $coordStmt->bind_param("idds", $newSpotId, $latitude, $longitude, $accuracy);
                    if (!$coordStmt->execute()) {
                        error_log("Failed to save coordinates for spot {$newSpotId}: " . $coordStmt->error);
                    } else {
                        error_log("Successfully saved coordinates for spot {$newSpotId}: ({$latitude}, {$longitude})");
                    }
                } else {
                    error_log("Failed to prepare coordinate insert statement: " . $conn->error);
                }
            } else {
                error_log("Invalid coordinate ranges for spot {$newSpotId}: lat={$latitude}, lng={$longitude}");
            }
        }

        // Handle image upload if provided
        // Handle image upload if provided (accept single 'image' or multiple 'images[]')
        $uploadDir = __DIR__ . '/../../../uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        // prefer first valid image from images[] if present
        if (isset($_FILES['images']) && is_array($_FILES['images'])) {
            // PHP represents multiple files as arrays inside $_FILES['images']
            $files = [];
            foreach ($_FILES['images']['name'] as $i => $name) {
                $files[] = [
                    'name' => $_FILES['images']['name'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i]
                ];
            }

            foreach ($files as $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid() . '_' . time() . '.' . $fileExt;
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $updateImageQuery = "UPDATE tourist_spots SET image_path = ? WHERE spot_id = ?";
                        $imgStmt = $conn->prepare($updateImageQuery);
                        if ($imgStmt) {
                            $imgStmt->bind_param("si", $fileName, $newSpotId);
                            $imgStmt->execute();
                        }
                        // only save the first successful image for now
                        break;
                    }
                }
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $updateImageQuery = "UPDATE tourist_spots SET image_path = ? WHERE spot_id = ?";
                $imgStmt = $conn->prepare($updateImageQuery);
                if ($imgStmt) {
                    $imgStmt->bind_param("si", $fileName, $newSpotId);
                    $imgStmt->execute();
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Tourist spot created successfully',
            'spot_id' => $newSpotId
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        // Try to get from JSON body if not in query
        if (!$id) {
            $deleteData = json_decode(file_get_contents("php://input"), true);
            if (isset($deleteData['spot_id'])) {
                $id = (int)$deleteData['spot_id'];
            }
        }
        if (!$id) {
            throw new Exception('Tourist spot ID is required');
        }

        // Check if spot belongs to this tourism officer's town
        $checkQuery = "SELECT town_id FROM tourist_spots WHERE spot_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $spotData = $result->fetch_assoc();

        if (!$spotData || $spotData['town_id'] != $town_id) {
            throw new Exception('You do not have permission to delete this tourist spot');
        }

        // Delete the tourist spot
        $deleteQuery = "DELETE FROM tourist_spots WHERE spot_id = ? AND town_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("ii", $id, $town_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete tourist spot");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Tourist spot deleted successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
