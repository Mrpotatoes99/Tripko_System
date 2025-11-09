<?php
header('Content-Type: application/json');
require_once('../../config/check_session.php');

try {
    checkAdminSession();
    
    // Get the period from query params
    $period = isset($_GET['period']) ? intval($_GET['period']) : 30;
    
    // Connect to database
    require_once('../../config/database.php');
    
    // Calculate date range
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$period} days"));
    
    // Get visitor statistics
    $visitorQuery = "SELECT COUNT(*) as total FROM user_visits WHERE visit_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($visitorQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $visitorResult = $stmt->get_result()->fetch_assoc();
    
    // Get popular spot
    $spotQuery = "SELECT ts.name, COUNT(uv.id) as visits 
                 FROM tourist_spots ts 
                 LEFT JOIN user_visits uv ON uv.spot_id = ts.id 
                 WHERE uv.visit_date BETWEEN ? AND ?
                 GROUP BY ts.id 
                 ORDER BY visits DESC 
                 LIMIT 1";
    $stmt = $conn->prepare($spotQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $spotResult = $stmt->get_result()->fetch_assoc();
    
    // Get new users count
    $usersQuery = "SELECT COUNT(*) as total FROM users WHERE created_at BETWEEN ? AND ?";
    $stmt = $conn->prepare($usersQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $usersResult = $stmt->get_result()->fetch_assoc();
    
    // Calculate trends (comparing with previous period)
    $prevStartDate = date('Y-m-d', strtotime("-{$period} days", strtotime($startDate)));
    
    // Previous period visitors
    $stmt = $conn->prepare($visitorQuery);
    $stmt->bind_param("ss", $prevStartDate, $startDate);
    $stmt->execute();
    $prevVisitorResult = $stmt->get_result()->fetch_assoc();
    
    // Previous period users
    $stmt = $conn->prepare($usersQuery);
    $stmt->bind_param("ss", $prevStartDate, $startDate);
    $stmt->execute();
    $prevUsersResult = $stmt->get_result()->fetch_assoc();
    
    // Calculate trend percentages
    $visitorsTrend = $prevVisitorResult['total'] > 0 
        ? round(($visitorResult['total'] - $prevVisitorResult['total']) / $prevVisitorResult['total'] * 100, 1)
        : 0;
    
    $usersTrend = $prevUsersResult['total'] > 0
        ? round(($usersResult['total'] - $prevUsersResult['total']) / $prevUsersResult['total'] * 100, 1)
        : 0;
    
    // Prepare response data
    $response = [
        'status' => 'success',
        'stats' => [
            'visitors' => intval($visitorResult['total']),
            'popularSpot' => $spotResult ? $spotResult['name'] : 'No visits recorded',
            'spotVisits' => $spotResult ? intval($spotResult['visits']) : 0,
            'newUsers' => intval($usersResult['total']),
            'visitorsTrend' => $visitorsTrend,
            'usersTrend' => $usersTrend
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while fetching dashboard data: ' . $e->getMessage()
    ]);
}
