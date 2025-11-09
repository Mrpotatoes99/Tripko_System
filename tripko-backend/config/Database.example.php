<?php
// Copy this file to Database.php and fill in real credentials. Do NOT commit Database.php
class Database {
    private $host = "localhost";     // e.g., localhost or your DB host
    private $db_name = "tripko_db";  // database name
    private $username = "root";      // DB user
    private $password = "";          // DB password
    private $port = 3307;             // MySQL/MariaDB port
    public $conn;

    public function getConnection() {
        try {
            $testConn = new mysqli($this->host, $this->username, $this->password, null, $this->port);
            if ($testConn->connect_error) {
                throw new Exception("MySQL server connection failed: " . $testConn->connect_error);
            }
            $result = $testConn->query("SHOW DATABASES LIKE '{$this->db_name}'");
            if ($result->num_rows === 0) {
                if (!$testConn->query("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                    throw new Exception("Failed to create database: " . $testConn->error);
                }
            }
            $testConn->close();
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name, $this->port);
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
