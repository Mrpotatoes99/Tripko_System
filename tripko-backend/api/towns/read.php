<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Clear any output buffering
while (ob_get_level()) ob_end_clean();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Debug log file
$logFile = __DIR__ . '/towns_api.log';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    writeLog("API request started");
    
    // Database configuration
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "tripko_db";
    
    // Create connection
    $conn = new mysqli($host, $username, $password, $database, 3307);
    
    // Check connection
    if ($conn->connect_error) {
        writeLog("Connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        writeLog("Error setting charset: " . $conn->error);
        throw new Exception("Error setting character set");
    }
    
    writeLog("Database connected successfully");    // Execute query
    $query = "SELECT town_id, name, image_path, status FROM towns ORDER BY name ASC";
    $result = $conn->query($query);
    
    if ($result === false) {
        writeLog("Query failed: " . $conn->error);
        throw new Exception("Failed to fetch towns data");
    }
    
    // Process results
    $records = [];
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Validate town_id
        $town_id = filter_var($row['town_id'], FILTER_VALIDATE_INT);
        if ($town_id === false) {
            writeLog("Invalid town_id found: " . print_r($row['town_id'], true));
            continue;
        }
        
        // Validate and sanitize name
        $name = trim($row['name'] ?? '');
        if (empty($name)) {
            writeLog("Empty town name found for ID: " . $town_id);
            continue;
        }          $records[] = [
            'town_id' => $town_id,
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'image_path' => $row['image_path'] ?? '',
            'status' => $row['status'] ?? 'active'
        ];
        $count++;
    }
    
    writeLog("Processed $count records");
    
    if (empty($records)) {
        writeLog("No valid towns found");
        throw new Exception("No towns found in database");
    }
    
    // Sort records by name
    usort($records, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    // Prepare response
    $response = [
        'success' => true,
        'count' => count($records),
        'records' => $records
    ];
    
    // Set response code and encode response
    http_response_code(200);
    $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if ($json === false) {
        writeLog("JSON encoding failed: " . json_last_error_msg());
        throw new Exception("Failed to encode response data");
    }
    
    writeLog("Sending response with " . count($records) . " towns");
    echo $json;

} catch (Exception $e) {
    writeLog("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} finally {
    // Clean up
    if (isset($result) && $result instanceof mysqli_result) {
        $result->free();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
