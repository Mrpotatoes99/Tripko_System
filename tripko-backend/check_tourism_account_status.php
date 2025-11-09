<?php
require_once('config/Database.php');
header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

$username = 'Agno Tourism';
$query = "SELECT u.*, ut.type_name, us.status_name, t.name as town_name 
          FROM user u
          LEFT JOIN user_type ut ON u.user_type_id = ut.user_type_id
          LEFT JOIN user_status us ON u.user_status_id = us.user_status_id
          LEFT JOIN towns t ON u.town_id = t.town_id
          WHERE u.username = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Also check if the password would work
$test_password = 'agno123';
$password_valid = false;
if ($user) {
    $password_valid = password_verify($test_password, $user['password']);
}

echo json_encode([
    'user_exists' => $user !== null,
    'user_data' => [
        'user_id' => $user['user_id'] ?? 'not found',
        'username' => $user['username'] ?? 'not found',
        'user_type_id' => $user['user_type_id'] ?? 'not found',
        'user_type' => $user['type_name'] ?? 'not found',
        'status' => $user['status_name'] ?? 'not found',
        'town' => $user['town_name'] ?? 'not assigned',
        'password_valid' => $password_valid
    ]
]);
?>
