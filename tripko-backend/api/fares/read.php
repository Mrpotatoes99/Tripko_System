<?php
require_once(__DIR__ . '/../../config/db.php');
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

$sql = "SELECT 
    f.fare_id,
    f.from_terminal_id,
    rt1.name AS from_terminal_name,
    rt1.town AS from_town,
    f.to_terminal_id,
    rt2.name AS to_terminal_name,
    rt2.town AS to_town,    f.transport_type_id,
    tt.type AS transport_type_name,
    f.category,
    f.amount,
    f.status
FROM fares f
LEFT JOIN route_terminals rt1 ON f.from_terminal_id = rt1.terminal_id
LEFT JOIN route_terminals rt2 ON f.to_terminal_id = rt2.terminal_id
LEFT JOIN transportation_type tt ON f.transport_type_id = tt.transport_type_id
ORDER BY f.fare_id DESC";

try {
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception($conn->error);
    }
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    echo json_encode([
        'records' => $records,
        'debug_sql' => $sql // Add this for debugging
    ]);
} catch (Exception $e) {
    echo json_encode([
        'records' => [],
        'error' => $e->getMessage(),
        'debug_sql' => $sql // Add this for debugging
    ]);
}

$conn->close();