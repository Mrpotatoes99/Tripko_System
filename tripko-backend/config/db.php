<?php
// db.php
// Flexible database connection supporting local XAMPP and Railway deployment.
// Priority order:
// 1. Explicit env vars: MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE
// 2. Railway provided RAILWAY_DATABASE_URL (mysql://user:pass@host:port/dbname)
// 3. Fallback to local development (localhost:3307 tripko_db root/no password)

// Helper: parse RAILWAY_DATABASE_URL if present and no explicit MYSQL* overrides.
function parseDatabaseUrl($url) {
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme']) || $parts['scheme'] !== 'mysql') {
        return null;
    }
    return [
        'host' => $parts['host'] ?? 'localhost',
        'port' => $parts['port'] ?? 3306,
        'user' => $parts['user'] ?? 'root',
        'pass' => $parts['pass'] ?? '',
        // path usually starts with '/'
        'db'   => isset($parts['path']) ? ltrim($parts['path'], '/') : 'railway'
    ];
}

// 1. Try explicit env vars
$envHost = getenv('MYSQLHOST');
$envPort = getenv('MYSQLPORT');
$envUser = getenv('MYSQLUSER');
$envPass = getenv('MYSQLPASSWORD');
$envDb   = getenv('MYSQLDATABASE');

$config = null;
if ($envHost && $envUser && $envDb) {
    $config = [
        'host' => $envHost,
        'port' => $envPort ?: 3306,
        'user' => $envUser,
        'pass' => $envPass ?: '',
        'db'   => $envDb,
    ];
} else {
    // 2. Try Railway URL
    $railwayUrl = getenv('RAILWAY_DATABASE_URL');
    if ($railwayUrl) {
        $parsed = parseDatabaseUrl($railwayUrl);
        if ($parsed) {
            $config = $parsed;
        }
    }
}

// 3. Fallback local dev
if (!$config) {
    $config = [
        'host' => 'localhost',
        'port' => 3307, // XAMPP custom port (adjust if yours differs)
        'user' => 'root',
        'pass' => '',
        'db'   => 'tripko_db',
    ];
}

$servername = $config['host'];
$username   = $config['user'];
$password   = $config['pass'];
$dbname     = $config['db'];
$port       = (int)$config['port'];

// Create connection with improved error handling
try {
    mysqli_report(MYSQLI_REPORT_OFF); // We'll handle errors manually
    $conn = @new mysqli($servername, $username, $password, $dbname, $port);

    if ($conn->connect_errno) {
        throw new Exception('Connection failed (' . $conn->connect_errno . '): ' . $conn->connect_error);
    }

    if (!$conn->set_charset('utf8mb4')) {
        throw new Exception('Error setting charset utf8mb4: ' . $conn->error);
    }

    // Optional lightweight connectivity check
    if (!$conn->ping()) {
        throw new Exception('Error: Lost connection to MySQL server');
    }
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    // Re-throw so calling scripts can handle or fail fast
    throw $e;
}
?>
