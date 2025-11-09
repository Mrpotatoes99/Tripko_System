<?php
require_once('../config/Database.php');
header('Content-Type: application/json');

error_log("Testing tourist spots data retrieval");

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    error_log("Database connection failed");
    die(json_encode(['error' => 'Database connection failed']));
}

$town_id = 1; // Agno's town_id
try {
    // First check if the tourist_spots table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'tourist_spots'");
    if ($tableCheck->num_rows === 0) {
        throw new Exception("tourist_spots table does not exist");
    }
    
    // Check table structure
    $columns = $conn->query("SHOW COLUMNS FROM tourist_spots");
    $columnNames = [];
    while($col = $columns->fetch_assoc()) {
        $columnNames[] = $col['Field'];
    }
    
    // Get tourist spots for Agno
    $query = "SELECT 
                ts.spot_id,
                ts.name,
                ts.description,
                ts.category,
                ts.location,
                ts.contact_info,
                ts.operating_hours,
                ts.entrance_fee,
                ts.image_path,
                COALESCE(ts.status, 'active') as status,
                t.name as town_name,
                t.town_id
              FROM tourist_spots ts
              INNER JOIN towns t ON ts.town_id = t.town_id
              WHERE ts.town_id = ?";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $town_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $spots = [];
    
    while ($row = $result->fetch_assoc()) {
        $spots[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'columns' => $columnNames,
        'spots_count' => count($spots),
        'spots' => $spots
    ]);

} catch (Exception $e) {
    error_log("Error in test script: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'table_exists' => isset($tableCheck) && $tableCheck->num_rows > 0,
        'columns' => $columnNames ?? []
    ]);
}
?>
