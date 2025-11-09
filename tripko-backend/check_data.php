<?php
require_once 'config/Database.php';

header("Content-Type: text/plain");

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check tourist spots data
    echo "\nChecking tourist spots data:\n";
    $spotsQuery = "SELECT ts.name, t.name as town_name, ts.created_at 
                   FROM tourist_spots ts 
                   LEFT JOIN towns t ON ts.town_id = t.town_id 
                   WHERE ts.status = 'active'
                   ORDER BY ts.created_at DESC 
                   LIMIT 4";
    
    $spotsResult = $conn->query($spotsQuery);
    if ($spotsResult) {
        echo "Found " . $spotsResult->num_rows . " active tourist spots:\n";
        while ($row = $spotsResult->fetch_assoc()) {
            echo "- {$row['name']} ({$row['town_name']}) - Created: {$row['created_at']}\n";
        }
    } else {
        echo "Error querying tourist spots: " . $conn->error . "\n";
    }
    
    // Check transport routes data
    echo "\nChecking transport routes data:\n";
    $routesQuery = "SELECT tr.route_id, 
                          tl1.location_name as from_terminal,
                          tl2.location_name as to_terminal,
                          tr.created_at
                   FROM transport_routes tr
                   LEFT JOIN terminal_locations tl1 ON tr.from_terminal_id = tl1.terminal_id
                   LEFT JOIN terminal_locations tl2 ON tr.to_terminal_id = tl2.terminal_id
                   WHERE tr.status = 'active'
                   ORDER BY tr.created_at DESC 
                   LIMIT 4";
                   
    $routesResult = $conn->query($routesQuery);
    if ($routesResult) {
        echo "Found " . $routesResult->num_rows . " active routes:\n";
        while ($row = $routesResult->fetch_assoc()) {
            echo "- From: {$row['from_terminal']} To: {$row['to_terminal']} - Created: {$row['created_at']}\n";
            
            // Check transport types for this route
            $typeQuery = "SELECT tt.type_name
                         FROM route_transport_types rtt
                         JOIN transport_types tt ON rtt.type_id = tt.type_id
                         WHERE rtt.route_id = {$row['route_id']}";
            $typeResult = $conn->query($typeQuery);
            if ($typeResult && $typeResult->num_rows > 0) {
                $types = [];
                while ($type = $typeResult->fetch_assoc()) {
                    $types[] = $type['type_name'];
                }
                echo "  Types: " . implode(', ', $types) . "\n";
            } else {
                echo "  No transport types found for this route\n";
            }
        }
    } else {
        echo "Error querying transport routes: " . $conn->error . "\n";
    }
    
    // Show any MySQL warnings
    $warningsResult = $conn->query("SHOW WARNINGS");
    if ($warningsResult && $warningsResult->num_rows > 0) {
        echo "\nMySQL Warnings:\n";
        while ($warning = $warningsResult->fetch_assoc()) {
            echo "- {$warning['Level']}: {$warning['Message']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
