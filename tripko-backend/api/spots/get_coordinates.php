<?php
/**
 * Get Coordinates API
 * Returns GPS coordinates for a tourist spot
 * Used by Google Maps "How to get there?" button
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../config/database.php';

// Get spot_id from query parameter
$spot_id = isset($_GET['spot_id']) ? (int)$_GET['spot_id'] : 0;

if ($spot_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid spot ID'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Query to get coordinates from geo_points table
    $query = "
        SELECT 
            gp.latitude,
            gp.longitude,
            gp.accuracy,
            ts.name AS spot_name
        FROM geo_points gp
        INNER JOIN tourist_spots ts ON ts.spot_id = gp.entity_id
        WHERE gp.entity_type = 'tourist_spot'
        AND gp.entity_id = ?
        AND ts.status = 'active'
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $spot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'spot_id' => $spot_id,
            'spot_name' => $row['spot_name'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'accuracy' => $row['accuracy'],
            'google_maps_url' => "https://www.google.com/maps/dir/?api=1&destination={$row['latitude']},{$row['longitude']}"
        ]);
    } else {
        // No coordinates found
        echo json_encode([
            'success' => false,
            'message' => 'Coordinates not available for this spot',
            'spot_id' => $spot_id
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Error in get_coordinates.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
}
?>
