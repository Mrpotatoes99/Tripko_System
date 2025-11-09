<?php
// Returns active tourist spots with coordinates for the map and autocomplete
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = "
        SELECT 
            ts.spot_id AS id,
            ts.name,
            ts.description,
            ts.category,
            ts.town_id,
            t.name AS town_name,
            ts.contact_info,
            ts.image_path,
            gp.latitude,
            gp.longitude,
            gp.accuracy,
            ts.status
        FROM tourist_spots ts
        LEFT JOIN towns t ON t.town_id = ts.town_id
        LEFT JOIN geo_points gp 
            ON gp.entity_type = 'tourist_spot' AND gp.entity_id = ts.spot_id
        WHERE ts.status = 'active'
        ORDER BY ts.name ASC
    ";

    $result = $conn->query($sql);
    $spots = [];
    while ($row = $result->fetch_assoc()) {
        $spots[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'category' => $row['category'],
            'town_id' => isset($row['town_id']) ? (int)$row['town_id'] : null,
            'town_name' => $row['town_name'],
            'contact_info' => $row['contact_info'],
            'image_path' => $row['image_path'],
            'latitude' => isset($row['latitude']) ? (float)$row['latitude'] : null,
            'longitude' => isset($row['longitude']) ? (float)$row['longitude'] : null,
            'has_coordinates' => $row['latitude'] !== null && $row['longitude'] !== null,
            'status' => $row['status']
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($spots),
        'data' => $spots
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error retrieving tourist spots',
        'error' => $e->getMessage()
    ]);
}
