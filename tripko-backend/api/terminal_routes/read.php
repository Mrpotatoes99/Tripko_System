<?php
require_once(__DIR__ . '/../../config/db.php');
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    $sql = "SELECT 
        r.route_id,
        r.status,
        t1.location_name AS from_terminal,
        t1.address AS from_town,
        t2.location_name AS to_terminal,
        t2.address AS to_town,
        GROUP_CONCAT(tt.type ORDER BY tt.type) AS transportation_types,
        GROUP_CONCAT(tt.type_id ORDER BY tt.type) AS transport_type_ids
    FROM transport_routes r
    LEFT JOIN terminal_locations t1 ON r.from_terminal_id = t1.terminal_id
    LEFT JOIN terminal_locations t2 ON r.to_terminal_id = t2.terminal_id
    LEFT JOIN route_transport_types rtt ON r.route_id = rtt.route_id
    LEFT JOIN transport_types tt ON rtt.type_id = tt.type_id
    GROUP BY r.route_id
    ORDER BY r.route_id DESC";

    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception($conn->error);
    }

    $records = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure status has a default value
        $row['status'] = $row['status'] ?? 'active';
        $records[] = $row;
    }

    echo json_encode(['records' => $records]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}