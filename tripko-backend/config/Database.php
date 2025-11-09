<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    private function parseDatabaseUrl($url) {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme']) || $parts['scheme'] !== 'mysql') {
            return null;
        }
        return [
            'host' => $parts['host'] ?? 'localhost',
            'port' => isset($parts['port']) ? (int)$parts['port'] : 3306,
            'user' => $parts['user'] ?? 'root',
            'pass' => $parts['pass'] ?? '',
            'db'   => isset($parts['path']) ? ltrim($parts['path'], '/') : 'railway'
        ];
    }

    public function __construct() {
        // Priority 1: Explicit MYSQL* env vars (Railway standard)
        $mysqlHost = getenv('MYSQLHOST');
        $mysqlPort = getenv('MYSQLPORT');
        $mysqlUser = getenv('MYSQLUSER');
        $mysqlPass = getenv('MYSQLPASSWORD');
        $mysqlDb   = getenv('MYSQLDATABASE');

        if ($mysqlHost && $mysqlUser && $mysqlDb) {
            $this->host     = $mysqlHost;
            $this->port     = (int)($mysqlPort ?: 3306);
            $this->username = $mysqlUser;
            $this->password = $mysqlPass ?: '';
            $this->db_name  = $mysqlDb;
            return;
        }

        // Priority 2: RAILWAY_DATABASE_URL (mysql://user:pass@host:port/db)
        $railwayUrl = getenv('RAILWAY_DATABASE_URL');
        if ($railwayUrl) {
            $cfg = $this->parseDatabaseUrl($railwayUrl);
            if ($cfg) {
                $this->host     = $cfg['host'];
                $this->port     = (int)$cfg['port'];
                $this->username = $cfg['user'];
                $this->password = $cfg['pass'];
                $this->db_name  = $cfg['db'];
                return;
            }
        }

        // Priority 3: Legacy envs DB_* (local dev/defaults)
        $this->host     = getenv('DB_HOST') ?: 'localhost';
        $this->db_name  = getenv('DB_NAME') ?: 'tripko_db';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->port     = (int)(getenv('DB_PORT') ?: 3307); // default local XAMPP port
    }

    public function getConnection() {
        try {
            // First try to connect without selecting a database (server reachability)
            $testConn = @new mysqli($this->host, $this->username, $this->password, null, $this->port);

            if ($testConn->connect_error) {
                throw new Exception("MySQL server connection failed: " . $testConn->connect_error);
            }

            // Check if database exists (skip create if user lacks privileges)
            if ($this->db_name) {
                $dbNameEsc = $testConn->real_escape_string($this->db_name);
                $result = $testConn->query("SHOW DATABASES LIKE '{$dbNameEsc}'");
                if ($result && $result->num_rows === 0) {
                    @$testConn->query("CREATE DATABASE IF NOT EXISTS `{$dbNameEsc}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    // If creation fails on hosted DB (no rights), we'll proceed to connect and let it fail clearly.
                }
            }
            $testConn->close();

            // Now connect to the specific database
            $this->conn = @new mysqli($this->host, $this->username, $this->password, $this->db_name, $this->port);

            if ($this->conn->connect_error) {
                throw new Exception("Database connection failed: " . $this->conn->connect_error);
            }

            if (!$this->conn->set_charset("utf8mb4")) {
                throw new Exception("Error setting charset utf8mb4: " . $this->conn->error);
            }

            return $this->conn;
        } catch(Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
