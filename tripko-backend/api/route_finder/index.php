<?php
// Route Finder API
// Computes straight-line route, estimated duration, and fare based on nearest terminals and fares table
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/Database.php';
// Enable verbose traces for local debugging (disable in production)
if (!defined('APP_DEBUG')) { define('APP_DEBUG', true); }

$debugNotes = [];

function haversine_km($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

function nearest_terminal(mysqli $conn, float $lat, float $lng): ?array {
    $sql = "
        SELECT 
            tl.terminal_id AS id,
            tl.location_name AS name,
            COALESCE(tl.latitude, gp.latitude) AS latitude,
            COALESCE(tl.longitude, gp.longitude) AS longitude
        FROM terminal_locations tl
        LEFT JOIN geo_points gp 
            ON gp.entity_type = 'terminal' AND gp.entity_id = tl.terminal_id
        WHERE tl.status = 'active'
    ";
    $res = $conn->query($sql);
    $best = null; $bestDist = PHP_FLOAT_MAX;
    while ($r = $res->fetch_assoc()) {
        if ($r['latitude'] === null || $r['longitude'] === null) continue;
        $d = haversine_km($lat, $lng, (float)$r['latitude'], (float)$r['longitude']);
        if ($d < $bestDist) {
            $bestDist = $d;
            $best = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'latitude' => (float)$r['latitude'],
                'longitude' => (float)$r['longitude'],
                'distance_km' => $bestDist
            ];
        }
    }
    return $best;
}

function get_terminal_by_id(mysqli $conn, int $terminalId): ?array {
    // Explicitly qualify columns to avoid ambiguity when both tables have latitude/longitude
    $sql = "SELECT tl.location_name, COALESCE(tl.latitude, gp.latitude) AS latitude, COALESCE(tl.longitude, gp.longitude) AS longitude FROM terminal_locations tl LEFT JOIN geo_points gp ON gp.entity_type='terminal' AND gp.entity_id=tl.terminal_id WHERE tl.terminal_id = ? AND tl.status='active' LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { return null; }
    $stmt->bind_param('i', $terminalId);
    if (!$stmt->execute()) { return null; }
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ($row['latitude'] === null || $row['longitude'] === null) return null;
        return [
            'id' => $terminalId,
            'name' => $row['location_name'],
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude']
        ];
    }
    return null;
}

function get_spot_by_id(mysqli $conn, int $spotId): ?array {
    $stmt = $conn->prepare("SELECT ts.name, ts.town_id, gp.latitude, gp.longitude FROM tourist_spots ts LEFT JOIN geo_points gp ON gp.entity_type='tourist_spot' AND gp.entity_id=ts.spot_id WHERE ts.spot_id = ? AND ts.status='active' LIMIT 1");
    $stmt->bind_param('i', $spotId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ($row['latitude'] === null || $row['longitude'] === null) return null;
        return [
            'id' => $spotId,
            'name' => $row['name'],
            'town_id' => (int)$row['town_id'],
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude']
        ];
    }
    return null;
}

/**
 * Get all terminals that serve a given municipality via municipal_transport_routes
 * Returns array of [terminal_id, name, latitude, longitude, modes => [[type_id, type_name, base_fare,...]]]
 */
function get_serving_terminals_for_town(mysqli $conn, int $townId): array {
    global $debugNotes;
    $advancedSql = "SELECT 
                tl.terminal_id,
                tl.location_name,
                COALESCE(tl.latitude, gp.latitude) AS latitude,
                COALESCE(tl.longitude, gp.longitude) AS longitude,
                tt.type_id,
                tt.type_name,
                tt.base_fare, tt.per_km_rate, tt.min_fare, tt.avg_speed_kph, tt.road_factor
            FROM municipal_transport_routes mtr
            JOIN terminal_locations tl ON tl.terminal_id = mtr.terminal_id
            LEFT JOIN geo_points gp ON gp.entity_type='terminal' AND gp.entity_id = tl.terminal_id
            JOIN transport_types tt ON tt.type_id = mtr.type_id
            WHERE mtr.town_id = ? AND mtr.active = 1 AND tl.status='active'";

    $fallbackSql = "SELECT 
                tl.terminal_id,
                tl.location_name,
                COALESCE(tl.latitude, gp.latitude) AS latitude,
                COALESCE(tl.longitude, gp.longitude) AS longitude,
                tt.type_id,
                tt.type_name
            FROM municipal_transport_routes mtr
            JOIN terminal_locations tl ON tl.terminal_id = mtr.terminal_id
            LEFT JOIN geo_points gp ON gp.entity_type='terminal' AND gp.entity_id = tl.terminal_id
            JOIN transport_types tt ON tt.type_id = mtr.type_id
            WHERE mtr.town_id = ? AND mtr.active = 1 AND tl.status='active'";

    $useFallback = false;
    $stmt = $conn->prepare($advancedSql);
    if (!$stmt) {
        $debugNotes[] = 'prepare_advanced_failed:' . $conn->error;
        $useFallback = true;
    }

    if (!$useFallback) {
        $stmt->bind_param('i', $townId);
        if (!$stmt->execute()) {
            $debugNotes[] = 'exec_advanced_failed:' . $conn->error;
            $useFallback = true;
        }
    }

    if ($useFallback) {
        $stmt = $conn->prepare($fallbackSql);
        if (!$stmt) {
            $debugNotes[] = 'prepare_fallback_failed:' . $conn->error;
            return [];
        }
        $stmt->bind_param('i', $townId);
        if (!$stmt->execute()) {
            $debugNotes[] = 'exec_fallback_failed:' . $conn->error;
            return [];
        }
    }

    $res = $stmt->get_result();
    $map = [];
    while ($r = $res->fetch_assoc()) {
        if ($r['latitude'] === null || $r['longitude'] === null) continue;
        $tid = (int)$r['terminal_id'];
        if (!isset($map[$tid])) {
            $map[$tid] = [
                'terminal_id' => $tid,
                'name' => $r['location_name'],
                'latitude' => (float)$r['latitude'],
                'longitude' => (float)$r['longitude'],
                'modes' => []
            ];
        }
        $map[$tid]['modes'][] = [
            'type_id' => (int)$r['type_id'],
            'type_name' => $r['type_name'],
            'base_fare' => isset($r['base_fare']) ? (float)($r['base_fare']) : null,
            'per_km_rate' => isset($r['per_km_rate']) ? (float)($r['per_km_rate']) : null,
            'min_fare' => isset($r['min_fare']) ? (float)($r['min_fare']) : null,
            'avg_speed_kph' => isset($r['avg_speed_kph']) ? (float)($r['avg_speed_kph']) : 40.0,
            'road_factor' => isset($r['road_factor']) ? (float)($r['road_factor']) : 1.30
        ];
    }
    if ($useFallback) $debugNotes[] = 'used_fallback_query';
    return array_values($map);
}

function estimate_fare(mysqli $conn, ?int $fromTerminalId, ?int $toTerminalId): ?array {
    if (!$fromTerminalId || !$toTerminalId) return null;

    // Try direct fare first
    $stmt = $conn->prepare("SELECT amount, category, type_id FROM fares WHERE from_terminal_id = ? AND to_terminal_id = ? AND status='active' ORDER BY amount ASC LIMIT 1");
    $stmt->bind_param('ii', $fromTerminalId, $toTerminalId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return [
            'amount' => (float)$row['amount'],
            'currency' => 'PHP',
            'category' => $row['category'],
            'type_id' => (int)$row['type_id']
        ];
    }
    // Try reverse (assume symmetric fare if defined)
    $stmt = $conn->prepare("SELECT amount, category, type_id FROM fares WHERE from_terminal_id = ? AND to_terminal_id = ? AND status='active' ORDER BY amount ASC LIMIT 1");
    $stmt->bind_param('ii', $toTerminalId, $fromTerminalId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return [
            'amount' => (float)$row['amount'],
            'currency' => 'PHP',
            'category' => $row['category'],
            'type_id' => (int)$row['type_id']
        ];
    }
    return null;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $startType = $input['start_type'] ?? 'coords'; // 'terminal' | 'coords'
    $startId = isset($input['start_id']) ? (int)$input['start_id'] : null;
    $startLat = isset($input['start_lat']) ? (float)$input['start_lat'] : null;
    $startLng = isset($input['start_lng']) ? (float)$input['start_lng'] : null;
    $destSpotId = isset($input['destination_id']) ? (int)$input['destination_id'] : null;

    if (!$destSpotId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'destination_id (spot) is required']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $destination = get_spot_by_id($conn, $destSpotId);
    if (!$destination) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Destination spot not found or has no coordinates']);
        exit;
    }

    $start = null;
    $startTerminal = null;
    if ($startType === 'terminal' && $startId) {
        $t = get_terminal_by_id($conn, $startId);
        if (!$t) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Start terminal not found or has no coordinates']);
            exit;
        }
        $start = [
            'label' => $t['name'],
            'latitude' => $t['latitude'],
            'longitude' => $t['longitude'],
            'type' => 'terminal',
            'id' => $t['id']
        ];
        $startTerminal = $t;
    } else {
        if ($startLat === null || $startLng === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'start_lat and start_lng are required when start_type=coords']);
            exit;
        }
        $start = [
            'label' => 'My Location',
            'latitude' => $startLat,
            'longitude' => $startLng,
            'type' => 'coords',
            'id' => null
        ];
        // find nearest terminal to user start
        $startTerminal = nearest_terminal($conn, $startLat, $startLng);
    }

    // Municipality-aware planning: find terminals that serve destination's town
    $serving = $destination['town_id'] ? get_serving_terminals_for_town($conn, (int)$destination['town_id']) : [];

    $chosenTerminal = null;
    if (!empty($serving)) {
        // If user selected a terminal but it doesn't serve the town, we will still compute but prefer the nearest serving terminal from user's origin
        $bestDist = PHP_FLOAT_MAX;
        foreach ($serving as $t) {
            $d = haversine_km($start['latitude'], $start['longitude'], $t['latitude'], $t['longitude']);
            if ($d < $bestDist) { $bestDist = $d; $chosenTerminal = $t; }
        }
    }

    // If no serving terminal found, fallback to generic nearest terminal to destination (legacy behavior)
    $destNearestTerminal = null;
    if ($chosenTerminal === null) {
        $destNearestTerminal = nearest_terminal($conn, $destination['latitude'], $destination['longitude']);
    }

    $polyline = [];
    $distanceKm = 0.0;
    $durationMin = 0;
    $fare = null;
    $assumptions = [
        'distance_model' => 'haversine-straight-line',
    ];

    if ($chosenTerminal !== null) {
        // Compose two-leg polyline: origin -> terminal -> destination
        $polyline = [
            [$start['latitude'], $start['longitude']],
            [$chosenTerminal['latitude'], $chosenTerminal['longitude']],
            [$destination['latitude'], $destination['longitude']]
        ];
        $userToTerminalKm = haversine_km($start['latitude'], $start['longitude'], $chosenTerminal['latitude'], $chosenTerminal['longitude']);
        $terminalToDestKm = haversine_km($chosenTerminal['latitude'], $chosenTerminal['longitude'], $destination['latitude'], $destination['longitude']);
        $distanceKm = round($userToTerminalKm + $terminalToDestKm, 2);

        // Choose mode with lowest estimated fare (rule-based) using transport_types params; default speed 40
        $bestMode = null; $bestFareAmt = PHP_FLOAT_MAX; $bestEta = null;
        if (!empty($chosenTerminal['modes']) && is_array($chosenTerminal['modes'])) {
        foreach ($chosenTerminal['modes'] as $mode) {
            $roadFactor = $mode['road_factor'] ?? 1.30;
            $rate = $mode['per_km_rate'] ?? 2.00;
            $base = $mode['base_fare'] ?? 20.00;
            $minFare = $mode['min_fare'] ?? 15.00;
            $speed = $mode['avg_speed_kph'] ?? 40.0;
            $effectiveKm = $terminalToDestKm * $roadFactor;
            $estFare = max($minFare, $base + $rate * $effectiveKm);
            $estMinutes = max(1, (int)round(($effectiveKm / $speed) * 60));
            if ($estFare < $bestFareAmt) {
                $bestFareAmt = $estFare;
                $bestMode = $mode + [
                    'estimated_fare' => $estFare,
                    'effective_km' => $effectiveKm,
                    'estimated_minutes' => $estMinutes
                ];
                $bestEta = $estMinutes;
            }
        }
        } else {
            $debugNotes[] = 'no_modes_for_chosen_terminal';
        }

        $isStartTerminal = ($startType === 'terminal' && $startTerminal);

        // If have actual fare in fares table between a real start/end terminal pair, prefer it (only covers start->serving segment; we still estimate remainder if needed)
        $actualFare = null;
        if ($startTerminal) {
            $actualFare = estimate_fare($conn, (int)$startTerminal['id'], (int)$chosenTerminal['terminal_id']);
        }

        $walkSpeed = 4.5; // km/h
        $walkMin = 0; $vehicleMin = 0; $durationMin = 0;

        if ($isStartTerminal) {
            // User is already at a terminal. Treat BOTH segments (startTerminal->serving + serving->destination) as vehicle travel.
            // Effective distance for fare/time should include both legs.
            if ($bestMode) {
                $combinedEffectiveKm = ($userToTerminalKm + $terminalToDestKm) * ($bestMode['road_factor'] ?? 1.30);
                $combinedFare = max($bestMode['min_fare'] ?? 15.00, ($bestMode['base_fare'] ?? 20.00) + ($bestMode['per_km_rate'] ?? 2.00) * $combinedEffectiveKm);
                $combinedMinutes = max(1, (int)round(($combinedEffectiveKm / ($bestMode['avg_speed_kph'] ?? 40.0)) * 60));
                // Override mode details to reflect combined segment
                $bestMode['effective_km'] = $combinedEffectiveKm;
                $bestMode['estimated_fare'] = $combinedFare;
                $bestMode['estimated_minutes'] = $combinedMinutes;
                $vehicleMin = $combinedMinutes;
                $durationMin = $vehicleMin; // no walking
                if ($actualFare) {
                    // Actual fare currently only covers first inter-terminal leg; we keep estimated for full until real data available.
                    $fare = [
                        'amount' => round($combinedFare, 2),
                        'currency' => 'PHP',
                        'category' => 'Estimated (terminal start)',
                        'type_id' => $bestMode['type_id'],
                        'components' => [
                            'inter_terminal_fare_available' => true,
                            'inter_terminal_actual_amount' => $actualFare['amount']
                        ]
                    ];
                } else {
                    $fare = [ 'amount' => round($combinedFare, 2), 'currency' => 'PHP', 'category' => 'Estimated (terminal start)', 'type_id' => $bestMode['type_id'] ];
                }
                $debugNotes[] = 'adjusted_terminal_start_mode';
            } else {
                // no modes; fallback generic speed for full distance
                $vehicleMin = max(1, (int)round((($userToTerminalKm + $terminalToDestKm) / 40.0) * 60));
                $durationMin = $vehicleMin;
            }
            $walkMin = 0; // explicit
        } else {
            // Original behavior: walk to serving terminal then ride from terminal to destination
            if ($actualFare) {
                $fare = $actualFare; // actual inter-terminal fare; we still estimate terminal->destination via mode for time only
            }
            if (!$fare && $bestMode) {
                $fare = [ 'amount' => round($bestMode['estimated_fare'], 2), 'currency' => 'PHP', 'category' => 'Estimated', 'type_id' => $bestMode['type_id'] ];
            }
            $walkMin = max(0, (int)round(($userToTerminalKm / $walkSpeed) * 60));
            $vehicleMin = $bestEta ?? max(1, (int)round(($terminalToDestKm / 40.0) * 60));
            $durationMin = $walkMin + $vehicleMin;
        }

        $assumptions['avg_walk_kmh'] = $walkSpeed;
        if (isset($bestMode['avg_speed_kph'])) $assumptions['vehicle_avg_speed_kmh'] = $bestMode['avg_speed_kph'];
        if (isset($bestMode['road_factor'])) $assumptions['road_factor'] = $bestMode['road_factor'];
        $assumptions['planning'] = 'municipality-serving terminals chosen';
    } else {
        // Fallback legacy: direct line origin -> destination
        $distanceKm = haversine_km($start['latitude'], $start['longitude'], $destination['latitude'], $destination['longitude']);
        $avgSpeedKmh = 40.0;
        $durationMin = max(1, (int)round(($distanceKm / $avgSpeedKmh) * 60));
        $polyline = [
            [$start['latitude'], $start['longitude']],
            [$destination['latitude'], $destination['longitude']]
        ];
        // Try fare via generic nearest terminals
        $destNearestTerminal = $destNearestTerminal ?? nearest_terminal($conn, $destination['latitude'], $destination['longitude']);
        if ($startTerminal && $destNearestTerminal) {
            $fare = estimate_fare($conn, (int)$startTerminal['id'], (int)$destNearestTerminal['id']);
        }
        $assumptions['avg_speed_kmh'] = 40.0;
        $assumptions['planning'] = 'fallback-legacy';
    }

    echo json_encode([
        'success' => true,
        'route' => [
            'start' => $start,
            'destination' => [
                'label' => $destination['name'],
                'latitude' => $destination['latitude'],
                'longitude' => $destination['longitude'],
                'type' => 'spot',
                'id' => $destination['id'] ?? $destSpotId
            ],
            'distance_km' => round($distanceKm, 2),
            'duration_minutes' => $durationMin,
            'polyline' => $polyline,
            'fare' => $fare,
            'assumptions' => $assumptions,
            'terminals_used' => [
                'start_terminal' => $startTerminal,
                'serving_terminal' => $chosenTerminal ?? null,
                'fallback_destination_terminal' => $destNearestTerminal
            ],
            'legs' => $chosenTerminal !== null ? (
                $isStartTerminal ? [
                    'start_terminal_to_serving_terminal_km' => round($userToTerminalKm, 2),
                    'serving_terminal_to_destination_km' => round($terminalToDestKm, 2),
                    'walk_minutes' => 0,
                    'vehicle_minutes' => $vehicleMin,
                    'selected_mode' => $bestMode ? [
                        'type_id' => $bestMode['type_id'],
                        'type_name' => $bestMode['type_name'],
                        'estimated_fare' => isset($bestMode['estimated_fare']) ? round($bestMode['estimated_fare'], 2) : null,
                        'effective_km' => isset($bestMode['effective_km']) ? round($bestMode['effective_km'], 2) : null,
                        'estimated_minutes' => $bestMode['estimated_minutes'] ?? null
                    ] : null,
                    'terminal_start' => true
                ] : [
                    'user_to_terminal_km' => round($userToTerminalKm, 2),
                    'terminal_to_destination_km' => round($terminalToDestKm, 2),
                    'walk_minutes' => $walkMin,
                    'vehicle_minutes' => $vehicleMin,
                    'selected_mode' => $bestMode ? [
                        'type_id' => $bestMode['type_id'],
                        'type_name' => $bestMode['type_name'],
                        'estimated_fare' => round($bestMode['estimated_fare'], 2),
                        'effective_km' => round($bestMode['effective_km'], 2),
                        'estimated_minutes' => $bestMode['estimated_minutes']
                    ] : null,
                    'terminal_start' => false
                ]
            ) : null
        ],
        // Non-breaking debug context to help diagnose 500s if any occur client-side
        'debug' => [
            'serving_terminals_count' => isset($serving) ? count($serving) : null,
            'chosen_terminal_id' => isset($chosenTerminal['terminal_id']) ? $chosenTerminal['terminal_id'] : null,
            'start_type' => $startType,
            'has_start_terminal' => (bool)$startTerminal,
            'fallback_destination_terminal' => $destNearestTerminal ? ($destNearestTerminal['id'] ?? null) : null,
            'notes' => $debugNotes
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error computing route',
        'error' => $e->getMessage(),
        'trace' => defined('APP_DEBUG') && APP_DEBUG ? $e->getTraceAsString() : null
    ]);
}
