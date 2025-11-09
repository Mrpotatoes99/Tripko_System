<?php
if (!function_exists('http_response_code')) {
    function http_response_code($code = NULL) {
        if ($code !== NULL) {
            switch ($code) {
                case 100: $text = 'Continue'; break;
                case 101: $text = 'Switching Protocols'; break;
                case 200: $text = 'OK'; break;
                case 201: $text = 'Created'; break;
                case 202: $text = 'Accepted'; break;
                case 203: $text = 'Non-Authoritative Information'; break;
                case 204: $text = 'No Content'; break;
                case 205: $text = 'Reset Content'; break;
                case 206: $text = 'Partial Content'; break;
                case 300: $text = 'Multiple Choices'; break;
                case 301: $text = 'Moved Permanently'; break;
                case 302: $text = 'Moved Temporarily'; break;
                case 303: $text = 'See Other'; break;
                case 304: $text = 'Not Modified'; break;
                case 305: $text = 'Use Proxy'; break;
                case 400: $text = 'Bad Request'; break;
                case 401: $text = 'Unauthorized'; break;
                case 402: $text = 'Payment Required'; break;
                case 403: $text = 'Forbidden'; break;
                case 404: $text = 'Not Found'; break;
                case 405: $text = 'Method Not Allowed'; break;
                case 406: $text = 'Not Acceptable'; break;
                case 407: $text = 'Proxy Authentication Required'; break;
                case 408: $text = 'Request Time-out'; break;
                case 409: $text = 'Conflict'; break;
                case 410: $text = 'Gone'; break;
                case 411: $text = 'Length Required'; break;
                case 412: $text = 'Precondition Failed'; break;
                case 413: $text = 'Request Entity Too Large'; break;
                case 414: $text = 'Request-URI Too Large'; break;
                case 415: $text = 'Unsupported Media Type'; break;
                case 500: $text = 'Internal Server Error'; break;
                case 501: $text = 'Not Implemented'; break;
                case 502: $text = 'Bad Gateway'; break;
                case 503: $text = 'Service Unavailable'; break;
                case 504: $text = 'Gateway Time-out'; break;
                case 505: $text = 'HTTP Version not supported'; break;
                default:  exit('Unknown http status code "' . htmlentities($code) . '"'); break;
            }
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            header($protocol . ' ' . $code . ' ' . $text);
            $GLOBALS['http_response_code'] = $code;
        } else {
            $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
        }
        return $code;
    }
}
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify required tables exist
function verifyTables($conn) {
    $requiredTables = [
        'tourist_spots',
        'towns',
        'transportation_type',
        'transport_route',
        'terminal_locations',
        'route_transport_types'
    ];

    $missingTables = [];
    foreach ($requiredTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result === false || $result->num_rows === 0) {
            $missingTables[] = $table;
        }
    }

    if (!empty($missingTables)) {
        throw new Exception("Missing required tables: " . implode(', ', $missingTables));
    }
}

// User statistics
function getUserStats($conn, $period) {
    try {
        $currentDate = date('Y-m-d');
        $compareDate = date('Y-m-d', strtotime("-$period days"));
        
        // For now, return dummy data since user tracking is not implemented yet
        return [
            'newUsers' => 0,
            'userGrowth' => 0
        ];
    } catch (Exception $e) {
        error_log("Error getting user stats: " . $e->getMessage());
        return [
            'newUsers' => 0,
            'userGrowth' => 0
        ];
    }
}

// Get recent tourist spots
function getRecentTouristSpots($conn) {
    try {
        $query = "SELECT ts.name, t.name as location, DATE_FORMAT(ts.created_at, '%Y-%m-%d') as added_date 
                 FROM tourist_spots ts
                 JOIN towns t ON ts.town_id = t.town_id
                 WHERE ts.status = 'active'
                 ORDER BY ts.created_at DESC
                 LIMIT 5";
                 
        $result = $conn->query($query);
        $spots = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $spots[] = [
                    'name' => $row['name'],
                    'location' => $row['location'],
                    'added_date' => $row['added_date']
                ];
            }
        }
        
        return $spots;
    } catch (Exception $e) {
        error_log("Error getting recent tourist spots: " . $e->getMessage());
        return [];
    }
}

// Get recent routes
function getRecentRoutes($conn) {
    try {
        $query = getRecentRoutesQuery();
        $result = $conn->query($query);
        $routes = [];if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['name'] !== null) { // Only add routes that have valid names
                    $routes[] = [
                        'name' => $row['name'],
                        'from_town' => $row['from_town'],
                        'to_town' => $row['to_town'],
                        'added_date' => $row['added_date']
                    ];
                }
            }
        }
        
        return $routes;
    } catch (Exception $e) {
        error_log("Error getting recent routes: " . $e->getMessage());
        return [];
    }
}

// Tourism statistics
function getTourismStats($conn, $period) {
    try {
        $currentDate = date('Y-m-d');
        $compareDate = date('Y-m-d', strtotime("-$period days"));
        $stats = [];
        
        // For now, use dummy data for visitor tracking until the feature is implemented
        $stats['totalVisitors'] = rand(100, 1000); // Dummy data
        $stats['visitorTrend'] = rand(-10, 10); // Dummy trend
        
        // Get popular tourist spot
        $queryPopularSpot = "SELECT ts.name, t.name as town_name
                            FROM tourist_spots ts
                            JOIN towns t ON ts.town_id = t.town_id
                            WHERE ts.status = 'active'
                            ORDER BY ts.created_at DESC
                            LIMIT 1";
                            
        $result = $conn->query($queryPopularSpot);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['popularSpot'] = $row['name'];
            $stats['popularSpotLocation'] = $row['town_name'];
        } else {
            $stats['popularSpot'] = 'No data';
            $stats['popularSpotLocation'] = '';
        }
        
        // Generate dummy monthly data until visitor tracking is implemented
        $stats['monthlyData'] = [];
        for ($i = 0; $i < 6; $i++) {
            $month = date('Y-m', strtotime("-$i months"));
            $stats['monthlyData'][] = [
                'month' => $month,
                'count' => rand(500, 2000)
            ];
        }
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting tourism stats: " . $e->getMessage());
        return [
            'totalVisitors' => 0,
            'visitorTrend' => 0,
            'popularSpot' => 'No data',
            'popularSpotLocation' => '',
            'monthlyData' => []
        ];
    }
}

// Transport statistics
function logQuery($conn, $query, $context) {
    error_log("[$context] Executing query: " . $query);
    $result = $conn->query($query);
    if (!$result) {
        error_log("[$context] Query error: " . $conn->error);
        error_log("[$context] Full error: " . print_r($conn->error_list, true));
        return false;
    } 
    
    error_log("[$context] Query returned " . $result->num_rows . " rows");
    if ($result->num_rows === 0) {
        error_log("[$context] Warning: Query returned zero rows");
        // Log first few rows as debug info
        $debug_result = $conn->query(preg_replace('/WHERE.*$/i', '', $query) . ' LIMIT 3');
        if ($debug_result) {
            error_log("[$context] Debug - First 3 rows without WHERE clause:");
            while ($row = $debug_result->fetch_assoc()) {
                error_log(print_r($row, true));
            }
        }
    }
    return $result;
}

// Include transport queries
require_once __DIR__ . '/transport_queries.php';

function getTransportStats($conn, $period) {
    try {
        error_log("[getTransportStats] Starting with period: " . $period);
        $stats = [
            'popularRoute' => ['name' => 'No data', 'fromTown' => '', 'toTown' => ''],
            'typeDistribution' => []
        ];

        // Verify tables exist
        $tables = ['transport_routes', 'terminal_locations', 'transport_types', 'route_transport_types'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if (!$result || $result->num_rows === 0) {
                error_log("[getTransportStats] Missing table: $table");
                return $stats;
            }
        }

        // Get popular route
        $queryPopularRoute = getPopularRouteQuery();
        error_log("[getTransportStats] Popular route query: " . $queryPopularRoute);
            
        $result = $conn->query($queryPopularRoute);
        if (!$result) {
            error_log("[getTransportStats] Error executing popular route query: " . $conn->error);
            throw new Exception("Failed to execute popular route query");
        }
        
        if ($row = $result->fetch_assoc()) {
            $stats['popularRoute'] = [
                'name' => $row['route_name'],
                'fromTown' => $row['from_town'],
                'toTown' => $row['to_town']
            ];
            error_log("[getTransportStats] Found popular route: " . $row['route_name']);
        } else {
            error_log("[getTransportStats] No popular routes found");
        }

        // Get transport type distribution
        $queryTypes = getTypeDistributionQuery();
        error_log("[getTransportStats] Type distribution query: " . $queryTypes);
        
        $result = $conn->query($queryTypes);
        if (!$result) {
            error_log("[getTransportStats] Error executing transport types query: " . $conn->error);
            throw new Exception("Failed to execute transport types query");
        }
        
        $stats['typeDistribution'] = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['typeDistribution'][] = [
                    'type' => $row['type_name'],
                    'count' => intval($row['count'])
                ];
            }
        }
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting transport stats: " . $e->getMessage());
        return [
            'popularRoute' => [
                'name' => 'No data',
                'fromTown' => '',
                'toTown' => ''
            ],
            'typeDistribution' => []
        ];
    }
}

try {
    // Start output buffering
    ob_start();
    
    // Get database connection
    $database = new Database();
    $conn = $database->getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Verify required tables exist
    verifyTables($conn);
    
    // Get period from query parameter
    $period = isset($_GET['period']) ? intval($_GET['period']) : 30;
    if ($period <= 0 || $period > 365) {
        $period = 30;
    }
    
    error_log("[get_reports.php] Getting statistics for period: $period days");
    
    // Get all statistics
    $userStats = getUserStats($conn, $period);
    $tourismStats = getTourismStats($conn, $period);
    $transportStats = getTransportStats($conn, $period);        // Get recent items
        $recentSpots = getRecentTouristSpots($conn);
        $recentRoutes = getRecentRoutes($conn);
        
        // Prepare response
        $response = [
            'success' => true,
            'data' => [
                'users' => $userStats,
                'tourism' => $tourismStats,
                'transport' => $transportStats,
                'recentSpots' => $recentSpots,
                'recentRoutes' => $recentRoutes
            ]
        ];
    
    // Clear any output and send JSON response
    ob_end_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("[get_reports.php] Error: " . $e->getMessage());
    
    // Clear any output and send error response
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}