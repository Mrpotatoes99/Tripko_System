<?php
class TouristSpot {
    private $conn;
    private $table = 'tourist_spots';

    public $spot_id;
    public $name;
    public $description;
    public $town_id;
    public $category;
    public $contact_info;
    public $image_path;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all tourist spots with pagination
    public function readPaginated($page = 1, $limit = 6, $view = 'grid', $filters = array()) {
        try {
            // Base conditions
            $conditions = array();
            $params = array();
            $types = "";

            // Add filters if provided
            if (!empty($filters['category'])) {
                $conditions[] = "ts.category = ?";
                $params[] = $filters['category'];
                $types .= "s";
            }
            if (!empty($filters['municipality'])) {
                $conditions[] = "t.name = ?";
                $params[] = $filters['municipality'];
                $types .= "s";
            }

            // Only filter by active status for grid view and non-admin/tourism officer users
            if ($view === 'grid' && !isset($_SESSION['user_type'])) {
                $conditions[] = "(ts.status = 'active' OR ts.status IS NULL)";
            }

            // Build WHERE clause
            $whereClause = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);

            // Get total count first
            $totalQuery = "SELECT COUNT(*) as total 
                          FROM " . $this->table . " ts
                          LEFT JOIN towns t ON ts.town_id = t.town_id 
                          $whereClause";

            if (!empty($params)) {
                $totalStmt = $this->conn->prepare($totalQuery);
                $totalStmt->bind_param($types, ...$params);
                $totalStmt->execute();
                $totalResult = $totalStmt->get_result();
            } else {
                $totalResult = $this->conn->query($totalQuery);
            }

            $total = $totalResult->fetch_assoc()['total'];

            // Calculate offset
            $offset = ($page - 1) * $limit;

            // Main query with pagination
            $query = "SELECT 
                        ts.*, 
                        t.name as town_name,
                        COALESCE(ts.status, 'active') as status
                     FROM " . $this->table . " ts
                     LEFT JOIN towns t ON ts.town_id = t.town_id 
                     $whereClause
                     ORDER BY ts.name ASC
                     LIMIT ?, ?";

            // Add limit parameters
            $params[] = $offset;
            $params[] = $limit;
            $types .= "ii";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Query preparation failed: " . $this->conn->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Query execution failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            
            return [
                'records' => $result,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ];

        } catch (Exception $e) {
            error_log("Error in TouristSpot->readPaginated(): " . $e->getMessage());
            return false;
        }
    }

    // Read only active tourist spots with pagination
    public function readActivePaginated($page = 1, $limit = 6, $filters = array()) {
        try {
            // Base conditions
            $conditions = array("(ts.status = 'active' OR ts.status IS NULL)");
            $params = array();
            $types = "";

            // Add filters if provided
            if (!empty($filters['category'])) {
                $conditions[] = "ts.category = ?";
                $params[] = $filters['category'];
                $types .= "s";
            }
            if (!empty($filters['municipality'])) {
                $conditions[] = "t.name = ?";
                $params[] = $filters['municipality'];
                $types .= "s";
            }

            // Build WHERE clause
            $whereClause = "WHERE " . implode(" AND ", $conditions);

            // Calculate the offset
            $offset = ($page - 1) * $limit;

            // Get total count of active spots
            $totalQuery = "SELECT COUNT(*) as total 
                          FROM " . $this->table . " ts
                          LEFT JOIN towns t ON ts.town_id = t.town_id 
                          $whereClause";

            if (!empty($params)) {
                $totalStmt = $this->conn->prepare($totalQuery);
                $totalStmt->bind_param($types, ...$params);
                $totalStmt->execute();
                $totalResult = $totalStmt->get_result();
            } else {
                $totalResult = $this->conn->query($totalQuery);
            }
            
            $total = $totalResult->fetch_assoc()['total'];

            // Main query with pagination
            $query = "SELECT 
                ts.spot_id, 
                ts.name, 
                ts.description, 
                ts.category,
                ts.town_id,
                t.name as town_name,
                ts.contact_info,
                ts.image_path,
                ts.created_at,
                ts.updated_at,
                COALESCE(ts.status, 'active') as status
            FROM 
                " . $this->table . " ts
            LEFT JOIN
                towns t ON ts.town_id = t.town_id
            $whereClause
            ORDER BY
                ts.name ASC
            LIMIT ?, ?";

            // Add limit parameters
            $params[] = $offset;
            $params[] = $limit;
            $types .= "ii";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Query preparation failed: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Query execution failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            
            return [
                'records' => $result,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ];

        } catch (Exception $e) {
            error_log("Error in TouristSpot->readActivePaginated(): " . $e->getMessage());
            return false;
        }
    }

    public function read() {
        $query = "SELECT ts.*, t.name as town_name 
                 FROM " . $this->table . " ts 
                 LEFT JOIN towns t ON ts.town_id = t.town_id 
                 ORDER BY ts.name";

        $result = $this->conn->query($query);
        return $result;
    }

    public function search($keyword) {
        $query = "SELECT 
            ts.spot_id, 
            ts.name, 
            ts.description, 
            ts.town_id,
            t.name as town_name, 
            ts.category,
            ts.contact_info,
            ts.image_path,
            ts.status
        FROM 
            " . $this->table . " ts
        LEFT JOIN
            towns t ON ts.town_id = t.town_id
        WHERE
            ts.name LIKE ? OR
            t.town_name LIKE ? OR
            ts.category LIKE ? OR
            ts.description LIKE ?
        ORDER BY
            ts.name ASC";

        $stmt = $this->conn->prepare($query);
        
        $keyword = "%{$keyword}%";
        $stmt->bind_param("ssss", $keyword, $keyword, $keyword, $keyword);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    public function read_single() {
        $query = "SELECT 
            ts.spot_id, 
            ts.name, 
            ts.description, 
            t.name as town_name, 
            ts.category,
            ts.contact_info,
            ts.image_path,
            ts.status
        FROM 
            " . $this->table . " ts
        LEFT JOIN
            towns t ON ts.town_id = t.town_id
        WHERE
            ts.spot_id = ?
        LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->spot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function updateStatus() {
        $query = "UPDATE " . $this->table . "
                SET status = ?
                WHERE spot_id = ?";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->spot_id = htmlspecialchars(strip_tags($this->spot_id));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Bind parameters
        $stmt->bind_param("si", $this->status, $this->spot_id);

        return $stmt->execute();
    }
}
