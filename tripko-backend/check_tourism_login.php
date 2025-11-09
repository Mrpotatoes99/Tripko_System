<?php
require_once('config/Database.php');
header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

$username = 'Agno Tourism';
$query = "SELECT user_id, username, password, user_type_id, town_id FROM user WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo json_encode([
    'user_found' => $user !== null,
    'user_data' => [
        'user_id' => $user['user_id'] ?? 'not found',
        'username' => $user['username'] ?? 'not found',
        'user_type_id' => $user['user_type_id'] ?? 'not found',
        'town_id' => $user['town_id'] ?? 'not found'
    ]
]);
?>
