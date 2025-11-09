<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/Database.php';

try {
    error_log("[get_recent_routes.php] Starting execution");
    
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        error_log("[get_recent_routes.php] Database connection failed");
        throw new Exception("Database connection failed");
    }
    
    error_log("[get_recent_routes.php] Database connection successful");
    
    // Check if necessary tables exist
    $tables = ['transport_route', 'terminal', 'transportation_type', 'route_transport_types'];
    $missing = [];
    
    foreach ($tables as $table) {
        error_log("[get_recent_routes.php] Checking table: $table");
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result === false) {
            error_log("[get_recent_routes.php] Error checking table $table: " . $conn->error);
            throw new Exception("Error checking table $table: " . $conn->error);
        }
        if ($result->num_rows === 0) {
            error_log("[get_recent_routes.php] Table '$table' does not exist");
            $missing[] = $table;
        }
    }
    
    if (!empty($missing)) {
        error_log("[get_recent_routes.php] Missing tables: " . implode(', ', $missing));
        echo json_encode([
            'success' => true,
            'records' => [],
            'message' => 'Transport routes feature is being set up. Missing tables: ' . implode(', ', $missing)
        ]);
        exit;
    }
    
    error_log("[get_recent_routes.php] All required tables exist");
    
    // Get recent routes with all necessary joins    
    $query = "SELECT 
        tr.route_id,
        tr.status,
        t1.terminal_name as from_terminal,
        t2.terminal_name as to_terminal,
        GROUP_CONCAT(DISTINCT tt.type_name ORDER BY tt.type_name SEPARATOR ', ') as transportation_types,
        tr.created_at
    FROM transport_route tr
    LEFT JOIN terminal t1 ON tr.origin_terminal_id = t1.terminal_id
    LEFT JOIN terminal t2 ON tr.destination_terminal_id = t2.terminal_id
    LEFT JOIN route_transport_types rtt ON tr.route_id = rtt.route_id
    LEFT JOIN transportation_type tt ON rtt.type_id = tt.type_id
    WHERE tr.status = 'active'
    GROUP BY tr.route_id, tr.status, t1.terminal_name, t2.terminal_name, tr.created_at
    ORDER BY tr.created_at DESC
    LIMIT 10";

    error_log("[get_recent_routes.php] Executing query: " . $query);
    
    if (!($stmt = $conn->prepare($query))) {
        error_log("[get_recent_routes.php] Prepare failed: " . $conn->error);
        throw new Exception("Failed to prepare routes query: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        error_log("[get_recent_routes.php] Execute failed: " . $stmt->error);
        throw new Exception("Failed to execute routes query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result === false) {
        error_log("[get_recent_routes.php] Error getting result set: " . $stmt->error);
        throw new Exception("Failed to get result set: " . $stmt->error);
    }
    
    $records = [];
    $count = 0;
    
    if ($result->num_rows === 0) {
        error_log("[get_recent_routes.php] No routes found");
        echo json_encode([
            'success' => true,
            'records' => [],
            'message' => 'No routes available yet'
        ]);
        exit;
    }
    
    while ($row = $result->fetch_assoc()) {
        $count++;
        if (empty($row['from_terminal']) || empty($row['to_terminal'])) {
            error_log("[get_recent_routes.php] Warning: Route with missing terminal data: " . json_encode($row));
        }
        $records[] = [
            'from_terminal' => $row['from_terminal'] ?? 'Unknown Terminal',
            'to_terminal' => $row['to_terminal'] ?? 'Unknown Terminal',
            'transportation_types' => $row['transportation_types'] ?? 'Not specified',
            'created_at' => $row['created_at']
        ];
    }

    error_log("[get_recent_routes.php] Found $count routes");

    echo json_encode([
        'success' => true,
        'records' => $records
    ]);

} catch (Exception $e) {
    error_log("[get_recent_routes.php] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch recent routes: ' . $e->getMessage()
    ]);
    exit;
}