<?php
// Inspect current DB schema and output JSON: tables and columns for key entities
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/Database.php';

$targets = [
    'towns',
    'tourist_spots',
    'terminal_locations',
    'transport_types',
    'transportation_type',
    'fares',
    'transport_routes',
    'route_transport_types',
    'geo_points',
    'municipal_transport_routes'
];

$out = [
    'db' => null,
    'tables' => [],
    'errors' => []
];

try {
    $db = new Database();
    $conn = $db->getConnection();
    $out['db'] = [
        'host' => 'connected',
        'name' => getenv('DB_NAME') ?: 'tripko_db',
        'port' => (int)(getenv('DB_PORT') ?: 3307)
    ];

    // List all tables
    $allTables = [];
    if ($res = $conn->query("SHOW TABLES")) {
        while ($row = $res->fetch_array(MYSQLI_NUM)) {
            $allTables[] = $row[0];
        }
    }

    foreach ($targets as $t) {
        $exists = in_array($t, $allTables, true);
        $entry = [
            'exists' => $exists,
            'columns' => [],
            'indexes' => []
        ];
        if ($exists) {
            if ($res = $conn->query("SHOW COLUMNS FROM `{$t}`")) {
                while ($col = $res->fetch_assoc()) {
                    $entry['columns'][] = $col; // Field, Type, Null, Key, Default, Extra
                }
            }
            if ($res = $conn->query("SHOW INDEX FROM `{$t}`")) {
                while ($idx = $res->fetch_assoc()) {
                    $entry['indexes'][] = $idx; // Key_name, Column_name, Non_unique, etc.
                }
            }
        }
        $out['tables'][$t] = $entry;
    }

    echo json_encode($out, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    $out['errors'][] = $e->getMessage();
    echo json_encode($out, JSON_PRETTY_PRINT);
}
