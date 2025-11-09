<?php
/**
 * Railway Database Connection Test
 * Tests the Database.php configuration with Railway credentials
 */

echo "=== Railway Database Connection Test ===\n\n";

// Simulate Railway environment variables
putenv('MYSQLHOST=shortline.proxy.rlwy.net');
putenv('MYSQLPORT=34658');
putenv('MYSQLUSER=root');
putenv('MYSQLPASSWORD=RRbIYHutzzBeDTcEOwvIWqmDSjRorSDh');
putenv('MYSQLDATABASE=railway');

echo "Testing with Railway credentials:\n";
echo "Host: " . getenv('MYSQLHOST') . "\n";
echo "Port: " . getenv('MYSQLPORT') . "\n";
echo "User: " . getenv('MYSQLUSER') . "\n";
echo "Database: " . getenv('MYSQLDATABASE') . "\n\n";

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
    
    // Check if tripko tables exist
    echo "Checking for tripko_db tables:\n";
    $tables = $conn->query("SHOW TABLES");
    if ($tables && $tables->num_rows > 0) {
        echo "Found " . $tables->num_rows . " tables:\n";
        $count = 0;
        while ($row = $tables->fetch_array()) {
            echo "  - " . $row[0] . "\n";
            $count++;
            if ($count >= 10) {
                echo "  ... and " . ($tables->num_rows - 10) . " more\n";
                break;
            }
        }
    } else {
        echo "⚠ No tables found (database is empty - you'll need to import your schema)\n";
    }
    
    echo "\n✅ SUCCESS: Railway connection working!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check if Railway MySQL service is running\n";
    echo "2. Verify the credentials are correct in Railway dashboard\n";
    echo "3. Make sure your IP is allowed (Railway uses public network)\n";
    echo "4. Check if the database 'railway' exists\n";
}
?>
