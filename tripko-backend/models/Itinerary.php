<?php
class Itinerary {
    private $conn;
    private $table_name = "itineraries";

    public function __construct($db) {
        $this->conn = $db;
    }    public function read() {
        try {            $query = "SELECT 
                i.itinerary_id,
                i.name,
                i.description,
                i.town_id,
                t.name as town_name,
                i.environmental_fee,
                i.image_path,
                i.status,
                i.created_at
            FROM " . $this->table_name . " i
            LEFT JOIN towns t ON i.town_id = t.town_id
            ORDER BY i.created_at DESC";

            $result = $this->conn->query($query);
            
            if (!$result) {
                throw new Exception($this->conn->error);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in Itinerary->read(): " . $e->getMessage());
            throw $e;
        }
    }

    public function readOne($id) {
        try {
            $query = "SELECT 
                i.itinerary_id,
                i.name,
                i.description,
                i.town_id,
                t.name as town_name,
                i.environmental_fee,
                i.image_path,
                i.status,
                i.created_at
            FROM " . $this->table_name . " i
            LEFT JOIN towns t ON i.town_id = t.town_id
            WHERE i.itinerary_id = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            
            return $stmt->get_result();
        } catch (Exception $e) {
            error_log("Error in Itinerary->readOne(): " . $e->getMessage());
            throw $e;
        }
    }
}
