<?php
require_once(__DIR__ . '/../../config/db.php');
header("Content-Type: application/json; charset=UTF-8");

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get total count
$totalRes = $conn->query("SELECT COUNT(*) as total FROM transportation_type");
$totalRow = $totalRes->fetch_assoc();
$total = $totalRow['total'];

// Get paginated data
$stmt = $conn->prepare("SELECT * FROM transportation_type ORDER BY transport_type_id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

echo json_encode([
    'records' => $records,
    'total' => $total,
    'page' => $page,
    'pages' => ceil($total / $limit)
]);