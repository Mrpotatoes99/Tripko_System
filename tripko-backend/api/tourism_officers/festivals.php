
<?php
require_once('../../config/init_session.php');
require_once('../../config/Database.php');
require_once('../../check_session.php');
header('Content-Type: application/json');

checkTourismOfficerSession();
$database = new Database();
$conn = $database->getConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
    exit;
}
$town_id = $_SESSION['town_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $query = "SELECT * FROM festivals WHERE festival_id = ? AND town_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $id, $town_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Festival not found']);
                exit;
            }
            $festival = [
                'festival_id' => (int)$row['festival_id'],
                'name' => htmlspecialchars($row['name']),
                'description' => htmlspecialchars($row['description']),
                'date' => htmlspecialchars($row['date']),
                'image_path' => htmlspecialchars($row['image_path'] ?? ''),
                'status' => htmlspecialchars($row['status']),
                'town_id' => (int)$row['town_id'],
                'created_at' => $row['created_at'] ?? '',
                'updated_at' => $row['updated_at'] ?? ''
            ];
            echo json_encode(['success' => true, 'festival' => $festival]);
            exit;
        }
        // List all festivals for this town
        $query = "SELECT * FROM festivals WHERE town_id = ? ORDER BY date ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $town_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $festivals = [];
        while ($row = $result->fetch_assoc()) {
            $festivals[] = [
                'festival_id' => (int)$row['festival_id'],
                'name' => htmlspecialchars($row['name']),
                'description' => htmlspecialchars($row['description']),
                'date' => htmlspecialchars($row['date']),
                'image_path' => htmlspecialchars($row['image_path'] ?? ''),
                'status' => htmlspecialchars($row['status']),
                'town_id' => (int)$row['town_id'],
                'created_at' => $row['created_at'] ?? '',
                'updated_at' => $row['updated_at'] ?? ''
            ];
        }
        echo json_encode(['success' => true, 'festivals' => $festivals, 'count' => count($festivals)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error retrieving festivals: ' . $e->getMessage()]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log('DEBUG: POST /festivals.php - POST data: ' . print_r($_POST, true));
        error_log('DEBUG: POST /festivals.php - FILES: ' . print_r($_FILES, true));
        $requiredFields = ['name', 'description', 'date'];
        $data = $_POST;
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                error_log("DEBUG: Missing required field: $field");
                throw new Exception("Missing required field: {$field}");
            }
        }
        $insertQuery = "INSERT INTO festivals (name, description, date, town_id, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())";
        $stmt = $conn->prepare($insertQuery);
        if (!$stmt) {
            error_log('DEBUG: Failed to prepare insert statement: ' . $conn->error);
            throw new Exception("Failed to prepare insert statement: " . $conn->error);
        }
        $stmt->bind_param("sssi", $data['name'], $data['description'], $data['date'], $town_id);
        if (!$stmt->execute()) {
            error_log('DEBUG: Failed to execute insert: ' . $stmt->error);
            throw new Exception("Failed to create festival: " . $stmt->error);
        }
        $newFestivalId = $stmt->insert_id;
        // Handle image upload if provided
        $uploadDir = __DIR__ . '/../../../uploads/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $updateImageQuery = "UPDATE festivals SET image_path = ? WHERE festival_id = ?";
                $imgStmt = $conn->prepare($updateImageQuery);
                if ($imgStmt) {
                    $imgStmt->bind_param("si", $fileName, $newFestivalId);
                    $imgStmt->execute();
                } else {
                    error_log('DEBUG: Failed to prepare image update: ' . $conn->error);
                }
            } else {
                error_log('DEBUG: Failed to move uploaded file: ' . $_FILES['image']['tmp_name'] . ' to ' . $targetPath);
            }
        } else if (isset($_FILES['image'])) {
            error_log('DEBUG: Image upload error code: ' . $_FILES['image']['error']);
        }
        echo json_encode([
            'success' => true,
            'message' => 'Festival created successfully',
            'festival_id' => $newFestivalId
        ]);
    } catch (Exception $e) {
        error_log('DEBUG: Exception in POST /festivals.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$id && isset($_POST['festival_id'])) {
            $id = (int)$_POST['festival_id'];
        }
        if (!$id) {
            $putData = json_decode(file_get_contents("php://input"), true);
            if (isset($putData['festival_id'])) {
                $id = (int)$putData['festival_id'];
            }
        }
        if (!$id) {
            throw new Exception('Festival ID is required');
        }
        // Check if festival belongs to this tourism officer's town
        $checkQuery = "SELECT town_id FROM festivals WHERE festival_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $festivalData = $result->fetch_assoc();
        if (!$festivalData || $festivalData['town_id'] != $town_id) {
            throw new Exception('You do not have permission to edit this festival');
        }
        $data = $_POST;
        if (empty($data)) {
            $data = json_decode(file_get_contents("php://input"), true);
        }
        if (!$data) {
            throw new Exception('Invalid request data');
        }
        $updateQuery = "UPDATE festivals SET name = ?, description = ?, date = ?, status = ? WHERE festival_id = ? AND town_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssii", $data['name'], $data['description'], $data['date'], $data['status'], $id, $town_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update festival: " . $stmt->error);
        }
        // Handle image upload if provided
        $uploadDir = __DIR__ . '/../../../uploads/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $updateImageQuery = "UPDATE festivals SET image_path = ? WHERE festival_id = ?";
                $imgStmt = $conn->prepare($updateImageQuery);
                if ($imgStmt) {
                    $imgStmt->bind_param("si", $fileName, $id);
                    $imgStmt->execute();
                }
            }
        }
        echo json_encode([
            'success' => true,
            'message' => 'Festival updated successfully'
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
        if (!$id) {
            $deleteData = json_decode(file_get_contents("php://input"), true);
            if (isset($deleteData['festival_id'])) {
                $id = (int)$deleteData['festival_id'];
            }
        }
        if (!$id) {
            throw new Exception('Festival ID is required');
        }
        $checkQuery = "SELECT town_id FROM festivals WHERE festival_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $festivalData = $result->fetch_assoc();
        if (!$festivalData || $festivalData['town_id'] != $town_id) {
            throw new Exception('You do not have permission to delete this festival');
        }
        $deleteQuery = "DELETE FROM festivals WHERE festival_id = ? AND town_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("ii", $id, $town_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete festival");
        }
        echo json_encode([
            'success' => true,
            'message' => 'Festival deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
