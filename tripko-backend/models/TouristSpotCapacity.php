<?php
class TouristSpotCapacity {
    private $conn;
    private $table = 'tourist_spot_capacity';
    private $spotsTable = 'tourist_spots';
    public $lastError;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all capacities for a town
    public function read($town_id = null) {
        try {
            $query = "SELECT c.*, s.name, s.category, t.name as town_name, c.current_capacity, c.max_capacity, 
                        (c.current_capacity / NULLIF(c.max_capacity,0)) * 100 as capacity_percentage, 
                        c.updated_at as last_updated, u.username as updated_by_user
                    FROM {$this->table} c
                    JOIN {$this->spotsTable} s ON c.spot_id = s.spot_id
                    JOIN towns t ON s.town_id = t.town_id
                    LEFT JOIN user u ON c.updated_by = u.user_id
                    ";
            $params = [];
            if ($town_id) {
                $query .= " WHERE s.town_id = ?";
                $params[] = $town_id;
            }
            $query .= " ORDER BY s.name ASC";
            $stmt = $this->conn->prepare($query);
            if ($town_id) {
                $stmt->bind_param('i', $town_id);
            }
            $stmt->execute();
            return $stmt->get_result();
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getLastError() {
        return $this->lastError;
    }
}
