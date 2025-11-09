<?php
/**
 * Local XAMPP Database Connection Test
 * Tests without Railway env vars (should use localhost:3307)
 */

echo "=== Local XAMPP Database Connection Test ===\n\n";

echo "Testing with local XAMPP config (no env vars set):\n";
echo "Expected: localhost:3307, tripko_db\n\n";

try {
    require_once __DIR__ . '/config/Database.php';
    
    echo "✓ Database.php loaded successfully\n";
    
    $database = new Database();
    echo "✓ Database object created\n";
    
    $conn = $database->getConnection();
    echo "✓ Connection established!\n\n";
    
    // Test query
    $result = $conn->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Connected to database: " . $row['current_db'] . "\n";
        echo "MySQL version: " . $row['mysql_version'] . "\n\n";
    }
    
    // Check for tripko tables
    echo "Checking for tables:\n";
    $tables = $conn->query("SHOW TABLES");
    if ($tables && $tables->num_rows > 0) {
        echo "Found " . $tables->num_rows . " tables:\n";
        $count = 0;
        while ($row = $tables->fetch_array()) {
            echo "  - " . $row[0] . "\n";
            $count++;
            if ($count >= 15) {
                echo "  ... and " . ($tables->num_rows - 15) . " more\n";
                break;
            }
        }
    } else {
        echo "⚠ No tables found\n";
    }
    
    echo "\n✅ SUCCESS: Local XAMPP connection working!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure XAMPP MySQL is running\n";
    echo "2. Check if port 3307 is correct (default XAMPP uses 3306)\n";
    echo "3. Verify database 'tripko_db' exists\n";
    echo "4. Check username/password (root with no password)\n";
}
?>
