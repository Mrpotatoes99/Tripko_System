<?php
// List terminals that have transport going to a given municipality (town)
// Free-friendly: no external APIs. Uses municipal_transport_routes mapping table.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/Database.php';

try {
    $townId = isset($_GET['town_id']) ? (int)$_GET['town_id'] : 0;
    $modeFilter = isset($_GET['mode_ids']) ? trim($_GET['mode_ids']) : ''; // comma-separated type_id list

    if ($townId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'town_id is required']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Verify town exists and is active if such a column exists
    $townStmt = $conn->prepare("SELECT town_id, name FROM towns WHERE town_id = ? LIMIT 1");
    $townStmt->bind_param('i', $townId);
    $townStmt->execute();
    $townRes = $townStmt->get_result();
    if (!$townRes || !$townRes->num_rows) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Municipality not found']);
        exit;
    }
    $town = $townRes->fetch_assoc();

    $params = [$townId];
    $modeSql = '';
    if ($modeFilter !== '') {
        // sanitize CSV of ints
        $ids = array_filter(array_map('intval', explode(',', $modeFilter)), function($v){ return $v > 0; });
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $modeSql = " AND mtr.type_id IN ($in)";
            $params = array_merge($params, $ids);
        }
    }

    $sql = "
        SELECT 
            tl.terminal_id,
            tl.location_name,
            tl.address,
            COALESCE(tl.latitude, gp.latitude) AS latitude,
            COALESCE(tl.longitude, gp.longitude) AS longitude,
            tt.type_id,
            tt.type_name
        FROM municipal_transport_routes mtr
        JOIN terminal_locations tl ON tl.terminal_id = mtr.terminal_id
        LEFT JOIN geo_points gp ON gp.entity_type = 'terminal' AND gp.entity_id = tl.terminal_id
        JOIN transport_types tt ON tt.type_id = mtr.type_id
        WHERE mtr.town_id = ? AND mtr.active=1 AND tl.status='active' $modeSql
        ORDER BY tl.location_name ASC, tt.type_name ASC
    ";

    // Prepare dynamically with variable number of params
    $types = str_repeat('i', count($params));
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Failed to prepare query');
    }

    // bind_param requires references when passing variable params
    $bindParams = [];
    $bindParams[] = & $types;
    foreach ($params as $k => $v) {
        $bindParams[] = & $params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);

    $stmt->execute();
    $res = $stmt->get_result();

    $byTerminal = [];
    while ($row = $res->fetch_assoc()) {
        $tid = (int)$row['terminal_id'];
        if (!isset($byTerminal[$tid])) {
            $byTerminal[$tid] = [
                'terminal_id' => $tid,
                'name' => $row['location_name'],
                'address' => $row['address'],
                'latitude' => $row['latitude'] !== null ? (float)$row['latitude'] : null,
                'longitude' => $row['longitude'] !== null ? (float)$row['longitude'] : null,
                'modes' => []
            ];
        }
        $byTerminal[$tid]['modes'][] = [
            'type_id' => (int)$row['type_id'],
            'type_name' => $row['type_name']
        ];
    }

    $terminals = array_values($byTerminal);

    echo json_encode([
        'success' => true,
        'municipality' => [ 'town_id' => (int)$town['town_id'], 'name' => $town['name'] ],
        'count' => count($terminals),
        'terminals' => $terminals
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching municipality terminals',
        'error' => $e->getMessage()
    ]);
}
