<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Start session so we can capture logged in user id (if any)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php'; // provides $conn (mysqli)

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection error');
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        throw new Exception('Invalid JSON payload');
    }

    $required = ['spot_id', 'reviewer_name', 'rating', 'review_text'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
            throw new Exception("Field '$field' is required");
        }
    }

    $spot_id = (int)$input['spot_id'];
    $reviewer_name = trim($input['reviewer_name']);
    $reviewer_email = isset($input['reviewer_email']) && trim($input['reviewer_email']) !== '' ? trim($input['reviewer_email']) : null;
    $rating = (int)$input['rating'];
    $review_text = trim($input['review_text']);
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null; // attach user if logged in

    if ($spot_id <= 0) throw new Exception('Invalid spot ID');
    if ($rating < 1 || $rating > 5) throw new Exception('Rating must be between 1 and 5');
    if (strlen($reviewer_name) < 2) throw new Exception('Name too short');
    if (strlen($reviewer_name) > 100) throw new Exception('Name too long');
    if ($reviewer_email && !filter_var($reviewer_email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email format');
    if (strlen($review_text) < 10) throw new Exception('Review must be at least 10 characters long');
    if (strlen($review_text) > 1000) throw new Exception('Review must be less than 1000 characters');

    // Spot exists?
    $spot_sql = "SELECT spot_id FROM tourist_spots WHERE spot_id = ? AND status = 'active' LIMIT 1";
    $spot_stmt = $conn->prepare($spot_sql);
    if (!$spot_stmt) throw new Exception('Prepare failed (spot check): ' . $conn->error);
    $spot_stmt->bind_param('i', $spot_id);
    $spot_stmt->execute();
    $spot_res = $spot_stmt->get_result();
    $spot_stmt->close();
    if (!$spot_res->num_rows) throw new Exception('Invalid tourist spot');

    // Duplicate check (24h) only if email present OR logged-in user
    if ($reviewer_email || $user_id) {
        if ($user_id) {
            $dup_sql = "SELECT review_id FROM reviews WHERE spot_id = ? AND user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1";
            $dup_stmt = $conn->prepare($dup_sql);
            if (!$dup_stmt) throw new Exception('Prepare failed (dup check user): ' . $conn->error);
            $dup_stmt->bind_param('ii', $spot_id, $user_id);
        } else {
            $dup_sql = "SELECT review_id FROM reviews WHERE spot_id = ? AND reviewer_email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1";
            $dup_stmt = $conn->prepare($dup_sql);
            if (!$dup_stmt) throw new Exception('Prepare failed (dup check email): ' . $conn->error);
            $dup_stmt->bind_param('is', $spot_id, $reviewer_email);
        }
        $dup_stmt->execute();
        $dup_res = $dup_stmt->get_result();
        $dup_stmt->close();
        if ($dup_res->num_rows) throw new Exception('You have already reviewed this place recently. Please wait 24 hours before submitting another review.');
    }

    // Insert review (handle nullable user/email)
    $insert_sql = "INSERT INTO reviews (spot_id, user_id, reviewer_name, reviewer_email, rating, review_text, status) VALUES (?, ?, ?, ?, ?, ?, 'active')";
    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) throw new Exception('Prepare failed (insert): ' . $conn->error);

    // For nullable ints/strings, bind_param requires proper types; use iissis (user_id null okay) 
    $insert_stmt->bind_param('iissis', $spot_id, $user_id, $reviewer_name, $reviewer_email, $rating, $review_text);
    $ok = $insert_stmt->execute();
    if (!$ok) {
        throw new Exception('Failed to save review: ' . $insert_stmt->error);
    }
    $new_id = $conn->insert_id;
    $insert_stmt->close();

    $response['success'] = true;
    $response['message'] = 'Review submitted successfully!';
    $response['review_id'] = (int)$new_id;
    $response['user_attached'] = (bool)$user_id;
} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
