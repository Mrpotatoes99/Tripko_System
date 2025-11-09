<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Correct path to db.php
require_once(__DIR__ . '/../../config/db.php');

// Block Super Admin (user_type_id == 1) from performing write operations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Super Admin accounts cannot create festivals.'
    ]);
    exit();
}

// If Tourism Officer, ensure town_id matches their municipality
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3) {
    // Auto-assign officer's town if not provided; validate if provided
    $officer_town = $_SESSION['town_id'] ?? null;
    if (!$officer_town) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: Your account is not associated with a municipality.']);
        exit();
    }
    $posted_town = $_POST['town_id'] ?? ($_POST['municipality'] ?? null);
    if (isset($posted_town) && $posted_town !== '') {
        if (strval($posted_town) !== strval($officer_town)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: You can only create festivals for your municipality.']);
            exit();
        }
        $town_id = $officer_town;
    } else {
        $town_id = $officer_town;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get POST data
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $date = $_POST['date'] ?? '';
    if (!isset($town_id)) {
        $town_id = $_POST['municipality'] ?? ($_POST['town_id'] ?? '');
    }

    // Handle file upload if present
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/TripKo-System/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_path = $filename;
        }
    }

    // Insert into database using MySQLi prepared statement
    $stmt = $conn->prepare("INSERT INTO festivals (name, description, date, town_id, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $name, $description, $date, $town_id, $image_path);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
