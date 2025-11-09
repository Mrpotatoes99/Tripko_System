<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../check_session.php';
require_once __DIR__ . '/../../config/Database.php';

checkTourismOfficerSession();

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $town_id = isset($_GET['town_id']) ? (int)$_GET['town_id'] : ($_SESSION['town_id'] ?? null);
    if (!$town_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'town_id is required']);
        exit();
    }

    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 5;
    $offset = ($page - 1) * $limit;

    // For recent activity we'll pull recent spots, festivals, itineraries
    $activities = [];
    // helper to detect created_at availability and build select/order
    function build_select_and_order($conn, $table, $id_col) {
        $hasCreated = false;
        $safeTable = $conn->real_escape_string($table);
        $check = $conn->query("SHOW COLUMNS FROM `" . $safeTable . "` LIKE 'created_at'");
        if ($check && $check->num_rows > 0) {
            $hasCreated = true;
        }

        if ($hasCreated) {
            $select = "$id_col as id, name as title, description, created_at";
            $order = "created_at DESC";
        } else {
            // fallback: return NULL as created_at and order by id desc
            $select = "$id_col as id, name as title, description, NULL as created_at";
            $order = "$id_col DESC";
        }

        return [$select, $order];
    }

    // recent spots
    list($select, $order) = build_select_and_order($conn, 'tourist_spots', 'spot_id');
    $sql = "SELECT $select FROM tourist_spots WHERE town_id = ? ORDER BY $order LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $town_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $activities[] = [
            'type' => 'spot',
            'id' => $row['id'] ?? null,
            'title' => $row['title'] ?? '',
            'description' => $row['description'] ?? '',
            'timestamp' => $row['created_at'] ?? null
        ];
    }
    $stmt->close();

    // recent festivals
    list($select, $order) = build_select_and_order($conn, 'festivals', 'festival_id');
    $sql = "SELECT $select FROM festivals WHERE town_id = ? ORDER BY $order LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $town_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $activities[] = [
            'type' => 'festival',
            'id' => $row['id'] ?? null,
            'title' => $row['title'] ?? '',
            'description' => $row['description'] ?? '',
            'timestamp' => $row['created_at'] ?? null
        ];
    }
    $stmt->close();

    // recent itineraries
    list($select, $order) = build_select_and_order($conn, 'itineraries', 'itinerary_id');
    $sql = "SELECT $select FROM itineraries WHERE (town_id = ? OR destination_id = ?) ORDER BY $order LIMIT 5";
    $stmt = $conn->prepare($sql);
    // some schemas use destination_id instead of town_id; bind twice to be safe
    $stmt->bind_param('ii', $town_id, $town_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $activities[] = [
            'type' => 'itinerary',
            'id' => $row['id'] ?? null,
            'title' => $row['title'] ?? '',
            'description' => $row['description'] ?? '',
            'timestamp' => $row['created_at'] ?? null
        ];
    }
    $stmt->close();

    // sort activities by timestamp desc and limit to 10
    usort($activities, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    $totalActivities = count($activities);
    $activities = array_slice($activities, $offset, $limit);
    $totalPages = ceil($totalActivities / $limit);

    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'activities' => $activities,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalActivities,
            'items_per_page' => $limit
        ]
    ]);

} catch (Exception $e) {
    error_log('recent_activity error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error', 'error' => $e->getMessage()]);
}
