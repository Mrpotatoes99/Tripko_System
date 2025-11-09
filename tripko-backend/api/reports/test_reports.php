<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

require_once(__DIR__ . '/../../config/db.php');

try {
    // Test database connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Test queries
    $queries = [
        "SELECT COUNT(*) as count FROM tourist_spots" => "Tourist spots",
        "SELECT COUNT(*) as count FROM visitors_tracking" => "Visitor records",
        "SELECT COUNT(*) as count FROM transport_route" => "Transport routes",
        "SELECT COUNT(*) as count FROM route_terminals" => "Terminals",
        "SELECT COUNT(*) as count FROM transportation_type" => "Transport types",
        "SELECT COUNT(*) as count FROM route_transport_types" => "Route-transport links",
        "SELECT COUNT(*) as count FROM user" => "Users",
        "SELECT COUNT(*) as count, created_at FROM user GROUP BY created_at" => "User creation dates"
    ];

    $results = [];
    foreach ($queries as $sql => $name) {
        $result = $conn->query($sql);
        if (!$result) {
            throw new Exception("Query failed for $name: " . $conn->error);
        }
        $row = $result->fetch_assoc();
        $results[$name] = $row;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Database connection and queries successful',
        'results' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>