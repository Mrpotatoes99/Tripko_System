<?php
require_once(__DIR__ . '/../../config/db.php');
header("Content-Type: application/json; charset=UTF-8");

$sql = "SELECT terminal_id, name, town, coordinates, status FROM route_terminals ORDER BY terminal_id DESC";
$result = $conn->query($sql);
$records = [];
while ($row = $result->fetch_assoc()) {
    // Ensure status has a value
    $row['status'] = $row['status'] ?? 'active';
    $records[] = $row;
}
echo json_encode(['records' => $records]);