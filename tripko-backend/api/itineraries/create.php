<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once(__DIR__ . '/../../config/db.php');

// Block Super Admin (user_type_id == 1) from performing write operations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Super Admin accounts cannot create itineraries.'
    ]);
    exit();
}

// If Tourism Officer, ensure town_id (destination) matches their municipality
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3) {
    // Auto-assign officer's town as destination if not provided; or validate provided value
    $officer_town = $_SESSION['town_id'] ?? null;
    if (!$officer_town) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: Your account is not associated with a municipality.']);
        exit();
    }
    $posted_town = $_POST['town_id'] ?? ($_POST['destination'] ?? null);
    if (isset($posted_town) && $posted_town !== '') {
        if (strval($posted_town) !== strval($officer_town)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: You can only create itineraries for your municipality.']);
            exit();
        }
        $destination_id = $officer_town;
    } else {
        $destination_id = $officer_town;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('HTTP/1.1 405 Method Not Allowed');
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $destination_id = $_POST['destination'] ?? '';
    $environmental_fee = $_POST['environmental_fee'] ?? '';

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

    $stmt = $conn->prepare("INSERT INTO itineraries (name, description, destination_id, environmental_fee, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $name, $description, $destination_id, $environmental_fee, $image_path);
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