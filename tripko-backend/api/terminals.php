<?php
// Returns terminals (active) with coordinates for the map and autocomplete
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Prefer terminal_locations.lat/lng; fallback to geo_points if present
    $sql = "
        SELECT 
            tl.terminal_id AS id,
            tl.location_name AS name,
            tl.address,
            tl.status,
            COALESCE(tl.latitude, gp.latitude) AS latitude,
            COALESCE(tl.longitude, gp.longitude) AS longitude
        FROM terminal_locations tl
        LEFT JOIN geo_points gp 
            ON gp.entity_type = 'terminal' AND gp.entity_id = tl.terminal_id
        WHERE tl.status = 'active'
        ORDER BY tl.location_name ASC
    ";

    $result = $conn->query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $lat = $row['latitude'];
        $lng = $row['longitude'];
        $rows[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'address' => $row['address'],
            'status' => $row['status'],
            'latitude' => $lat !== null ? (float)$lat : null,
            'longitude' => $lng !== null ? (float)$lng : null,
            'has_coordinates' => $lat !== null && $lng !== null
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($rows),
        'data' => $rows
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error retrieving terminals',
        'error' => $e->getMessage()
    ]);
}
