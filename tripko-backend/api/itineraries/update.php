<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/db.php';

// Block Super Admin (user_type_id == 1) from performing write operations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Super Admin accounts cannot update itineraries.'
    ]);
    exit();
}

// If Tourism Officer, ensure the itinerary belongs to their municipality before allowing update
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3) {
    $officer_town = $_SESSION['town_id'] ?? null;
    $itinerary_check = isset($_POST['itinerary_id']) ? $_POST['itinerary_id'] : null;
    if ($itinerary_check && $officer_town) {
        $chk = $conn->prepare("SELECT town_id FROM itineraries WHERE itinerary_id = ? LIMIT 1");
        $chk->bind_param('i', $itinerary_check);
        $chk->execute();
        $res = $chk->get_result();
        $row = $res->fetch_assoc();
        if (!$row || strval($row['town_id']) !== strval($officer_town)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to edit this itinerary.']);
            exit();
        }
    }
}

try {
    // Get posted data
    $itinerary_id = isset($_POST['itinerary_id']) ? $_POST['itinerary_id'] : die(json_encode(["success" => false, "message" => "Missing itinerary ID"]));
    
    // Accept either town_id or destination_id (for backward compatibility)
    if (isset($_POST['town_id'])) {
        $town_id = $_POST['town_id'];
    } else if (isset($_POST['destination_id'])) {
        $town_id = $_POST['destination_id'];
    } else {
        $town_id = null;
    }
    
    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $environmental_fee = isset($_POST['environmental_fee']) ? $_POST['environmental_fee'] : null;

    // Validate required fields
    if (!$name || !$description || !$town_id) {
        die(json_encode([
            "success" => false,
            "message" => "Missing required fields: Please provide a name, description and municipality"
        ]));
    }

    // Start transaction
    $conn->begin_transaction();

    // Update itinerary basic info
    $stmt = $conn->prepare("UPDATE itineraries SET 
        town_id = ?, 
        name = ?, 
        description = ?, 
        environmental_fee = ?
        WHERE itinerary_id = ?");

    $stmt->bind_param("issdi", 
        $town_id,
        $name,
        $description,
        $environmental_fee,
        $itinerary_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to update itinerary");
    }

    // Handle image uploads if any
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $uploadDir = "../../../uploads/";
        $uploadedFiles = [];
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Process each uploaded file
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['images']['name'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $file_tmp = $_FILES['images']['tmp_name'][$key];
            $file_type = $_FILES['images']['type'][$key];
            
            // Generate unique filename
            $uniqueName = uniqid() . '_' . $file_name;
            $targetFile = $uploadDir . $uniqueName;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $targetFile)) {
                $uploadedFiles[] = $uniqueName;
                
                // Insert image record
                $stmt = $conn->prepare("INSERT INTO itinerary_images (itinerary_id, image_path) VALUES (?, ?)");
                $stmt->bind_param("is", $itinerary_id, $uniqueName);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to save image record");
                }
            } else {
                throw new Exception("Failed to upload image: " . $file_name);
            }
        }
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        "success" => true,
        "message" => "Itinerary updated successfully"
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}