<?php
require_once 'config/Database.php';

header("Content-Type: text/plain");

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Database connection successful\n\n";
    
    // Test tables existence and structure
    $requiredTables = [
        'tourist_spots' => ['name', 'town_id', 'status', 'created_at'],
        'towns' => ['town_id', 'name'],
        'transport_routes' => ['route_id', 'from_terminal_id', 'to_terminal_id', 'status', 'created_at'],
        'terminal_locations' => ['terminal_id', 'location_name'],
        'route_transport_types' => ['route_id', 'type_id'],
        'transport_types' => ['type_id', 'type_name']
    ];
    
    foreach ($requiredTables as $table => $columns) {
        echo "\nChecking table '$table':\n";
        
        // Check if table exists
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows === 0) {
            echo "- Table '$table' does not exist!\n";
            continue;
        }
        echo "- Table exists\n";
        
        // Check columns
        $columnResult = $conn->query("SHOW COLUMNS FROM $table");
        $existingColumns = [];
        while ($row = $columnResult->fetch_assoc()) {
            $existingColumns[] = $row['Field'];
        }
        
        echo "- Checking required columns:\n";
        foreach ($columns as $column) {
            if (in_array($column, $existingColumns)) {
                echo "  ✓ $column exists\n";
            } else {
                echo "  ✗ $column is missing!\n";
            }
        }
        
        // Check for sample data
        $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $countResult->fetch_assoc()['count'];
        echo "- Row count: $count\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
