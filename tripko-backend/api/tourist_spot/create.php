<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true"); 
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once(__DIR__ . '/../../config/db.php');

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Super Admin accounts cannot create tourist spots.']);
    exit();
}

// If Tourism Officer, ensure new spot is created for their own municipality only
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3) {
    // Auto-assign officer's town if not provided and ensure any provided town matches
    $officer_town = $_SESSION['town_id'] ?? null;
    if (!$officer_town) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: Your account is not associated with a municipality.']);
        exit();
    }
    // If a town_id was posted, ensure it matches officer town; otherwise set it to officer town
    if (isset($_POST['town_id']) && $_POST['town_id'] !== '') {
        if (strval($_POST['town_id']) !== strval($officer_town)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: You can only create tourist spots for your municipality.']);
            exit();
        }
        $town_id = $officer_town; // use officer's town to be explicit
    } else {
        $town_id = $officer_town;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $contact_info = $_POST['contact_info'] ?? '';

    // Handle image upload (single image for simplicity)
    $image_path = null;
    if (isset($_FILES['images']) && $_FILES['images']['error'][0] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/TripKo-System/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid() . '_' . basename($_FILES['images']['name'][0]);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['images']['tmp_name'][0], $targetFile)) {
            $image_path = $filename;
        }
    }

    // Include town_id in the INSERT query (auto-assigned for Tourism Officers)
    // If $town_id not set (non-officer flows), attempt to read posted town_id or set NULL
    if (!isset($town_id)) {
        $town_id = $_POST['town_id'] ?? null;
    }
    $stmt = $conn->prepare("INSERT INTO tourist_spots (name, description, town_id, category, contact_info, image_path) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $name, $description, $town_id, $category, $contact_info, $image_path);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}