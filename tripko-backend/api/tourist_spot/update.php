<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Super Admin accounts cannot update tourist spots.']);
    exit();
}

// If Tourism Officer, ensure the spot belongs to their municipality before allowing update
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3) {
    $officer_town = $_SESSION['town_id'] ?? null;
    $spot_id_check = isset($_POST['spot_id']) ? $_POST['spot_id'] : null;
    if ($spot_id_check && $officer_town) {
        $chk = $conn->prepare("SELECT town_id FROM tourist_spots WHERE spot_id = ? LIMIT 1");
        $chk->bind_param('i', $spot_id_check);
        $chk->execute();
        $res = $chk->get_result();
        $row = $res->fetch_assoc();
        if (!$row || strval($row['town_id']) !== strval($officer_town)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to edit this tourist spot.']);
            exit();
        }
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $spot_id = isset($_POST['spot_id']) ? $_POST['spot_id'] : null;
    
    if (!$spot_id) {
        throw new Exception('Missing spot_id parameter');
    }

    // First verify the spot exists
    $check = $conn->prepare("SELECT spot_id FROM tourist_spots WHERE spot_id = ?");
    $check->bind_param("i", $spot_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        throw new Exception("No tourist spot found with ID: {$spot_id}");
    }

    // Validate required fields
    $required_fields = ['name', 'description', 'town_id', 'category'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $town_id = $_POST['town_id'];
    $category = trim($_POST['category']);
    $contact_info = isset($_POST['contact_info']) ? trim($_POST['contact_info']) : '';
    
    // Start transaction
    $conn->begin_transaction();

    // Update spot details
    $stmt = $conn->prepare("UPDATE tourist_spots SET name = ?, description = ?, town_id = ?, category = ?, contact_info = ? WHERE spot_id = ?");
    $stmt->bind_param("ssissi", $name, $description, $town_id, $category, $contact_info, $spot_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update tourist spot details: " . $stmt->error);
    }

    // Note: We don't check affected_rows here since the data might be the same

    // Handle image upload if a new image is provided
    if (!empty($_FILES['images']['name'][0])) {
        // Get old image path
        $stmt = $conn->prepare("SELECT image_path FROM tourist_spots WHERE spot_id = ?");
        $stmt->bind_param("i", $spot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_image = $result->fetch_assoc();

        // Upload new image
        $uploadDir = "../../../uploads/";
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }

        $filename = uniqid() . '_' . basename($_FILES['images']['name'][0]);
        $targetFile = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['images']['tmp_name'][0], $targetFile)) {
            throw new Exception("Failed to upload new image");
        }

        // Update image path in database
        $stmt = $conn->prepare("UPDATE tourist_spots SET image_path = ? WHERE spot_id = ?");
        $stmt->bind_param("si", $filename, $spot_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update image path: " . $stmt->error);
        }

        // Delete old image if it exists
        if ($old_image && $old_image['image_path']) {
            $old_image_path = $uploadDir . $old_image['image_path'];
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Tourist spot updated successfully"
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