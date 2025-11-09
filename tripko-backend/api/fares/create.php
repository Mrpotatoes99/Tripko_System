<?php
require_once(__DIR__ . '/../../config/db.php');
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

// Session guard: super admin (user_type_id == 1) must not perform write operations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Super Admin accounts cannot create fares.'
    ]);
    exit();
}

try {
    // Accept JSON body or form POST
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data)) {
        // fallback to $_POST for form submissions
        $data = $_POST;
    }

    // Validate required fields
    $required = ['from_terminal_id', 'to_terminal_id', 'transport_type_id', 'category', 'amount'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            throw new Exception("Missing required field: $field");
        }
    }

    // Cast values
    $from_terminal_id = (int)$data['from_terminal_id'];
    $to_terminal_id = (int)$data['to_terminal_id'];
    $transport_type_id = (int)$data['transport_type_id'];
    $category = (string)$data['category'];
    $amount = (float)$data['amount'];

    $stmt = $conn->prepare("INSERT INTO fares (from_terminal_id, to_terminal_id, transport_type_id, category, amount) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    // types: int, int, int, string, double
    if (!$stmt->bind_param('iiisd', $from_terminal_id, $to_terminal_id, $transport_type_id, $category, $amount)) {
        throw new Exception('Failed to bind parameters: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Fare created successfully',
        'fare_id' => $conn->insert_id
    ]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // ensure resources are cleaned up
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}