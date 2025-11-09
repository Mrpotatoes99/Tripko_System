<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/Database.php';

try {
    error_log("[get_recent_spots.php] Starting execution");
    
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        error_log("[get_recent_spots.php] Database connection failed");
        throw new Exception("Database connection failed");
    }
    
    error_log("[get_recent_spots.php] Database connection successful");

    // Verify table existence first
    $tables = array('tourist_spots', 'towns');
    foreach ($tables as $table) {
        error_log("[get_recent_spots.php] Checking table: $table");
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check === false) {
            error_log("[get_recent_spots.php] Error checking table $table: " . $conn->error);
            throw new Exception("Error checking table $table: " . $conn->error);
        }
        if ($check->num_rows === 0) {
            error_log("[get_recent_spots.php] Table '$table' does not exist");
            throw new Exception("Table '$table' does not exist");
        }
    }

    error_log("[get_recent_spots.php] All required tables exist");

    // Verify table structure
    $structureQuery = "SHOW COLUMNS FROM tourist_spots WHERE Field IN ('name', 'town_id', 'status', 'created_at')";
    $structureCheck = $conn->query($structureQuery);
    if ($structureCheck === false) {
        error_log("[get_recent_spots.php] Error checking tourist_spots structure: " . $conn->error);
        throw new Exception("Error checking tourist_spots structure: " . $conn->error);
    }

    if ($structureCheck->num_rows < 4) {
        error_log("[get_recent_spots.php] tourist_spots table is missing required columns");
        throw new Exception("tourist_spots table is missing required columns");
    }

    error_log("[get_recent_spots.php] Table structure verified");

    // Get the most recently added tourist spots with LEFT JOIN to be safe
    $query = "SELECT ts.name, t.name as town_name, ts.created_at 
              FROM tourist_spots ts 
              LEFT JOIN towns t ON ts.town_id = t.town_id 
              WHERE ts.status = 'active' OR ts.status IS NULL
              ORDER BY ts.created_at DESC 
              LIMIT 4";
    
    error_log("[get_recent_spots.php] Executing query: " . $query);
    
    if (!($stmt = $conn->prepare($query))) {
        error_log("[get_recent_spots.php] Prepare failed: " . $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        error_log("[get_recent_spots.php] Execute failed: " . $stmt->error);
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result === false) {
        error_log("[get_recent_spots.php] Error getting result set: " . $stmt->error);
        throw new Exception("Error getting result set: " . $stmt->error);
    }
    
    $records = [];
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $count++;
        $records[] = [
            'name' => $row['name'] ?? 'Unnamed Spot',
            'town_name' => $row['town_name'] ?? 'Location unavailable',
            'created_at' => $row['created_at']
        ];
    }

    error_log("[get_recent_spots.php] Found $count spots");

    echo json_encode([
        'success' => true,
        'records' => $records
    ]);

} catch (Exception $e) {
    error_log("[get_recent_spots.php] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch recent spots: ' . $e->getMessage(),
        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
    ]);
}
