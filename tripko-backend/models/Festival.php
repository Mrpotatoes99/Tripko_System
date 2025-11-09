<?php
class Festival {
    private $conn;
    private $table_name = "festivals";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT f.*, t.name as town_name 
                 FROM " . $this->table_name . " f
                 LEFT JOIN towns t ON f.town_id = t.town_id
                 ORDER BY f.name";

        return $this->conn->query($query);
    }
}