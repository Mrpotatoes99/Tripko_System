<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Use mysqli connection
require_once '../../config/db.php'; // provides $conn (mysqli)

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection error');
    }

    $spot_id = isset($_GET['spot_id']) ? (int)$_GET['spot_id'] : 0;
    if ($spot_id <= 0) {
        throw new Exception('Invalid spot ID');
    }

    // Verify spot exists & fetch name
    $spot_sql = "SELECT name FROM tourist_spots WHERE spot_id = ? AND status = 'active' LIMIT 1";
    $spot_stmt = $conn->prepare($spot_sql);
    if (!$spot_stmt) throw new Exception('Prepare failed (spot check): ' . $conn->error);
    $spot_stmt->bind_param('i', $spot_id);
    $spot_stmt->execute();
    $spot_result = $spot_stmt->get_result();
    $spot = $spot_result->fetch_assoc();
    $spot_stmt->close();
    if (!$spot) throw new Exception('Tourist spot not found');

    // Pagination params
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(5, (int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;

    // Stats query
    $stats_sql = "SELECT 
            COUNT(*) AS total_reviews,
            AVG(rating) AS average_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS rating_5,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS rating_4,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS rating_3,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS rating_2,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS rating_1
        FROM reviews WHERE spot_id = ? AND status = 'active'";
    $stats_stmt = $conn->prepare($stats_sql);
    if (!$stats_stmt) throw new Exception('Prepare failed (stats): ' . $conn->error);
    $stats_stmt->bind_param('i', $spot_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();
    $stats_stmt->close();

    // Reviews query
    $reviews_sql = "SELECT review_id, reviewer_name, rating, review_text, review_date, helpful_count
                    FROM reviews
                    WHERE spot_id = ? AND status = 'active'
                    ORDER BY review_date DESC
                    LIMIT ? OFFSET ?";
    $reviews_stmt = $conn->prepare($reviews_sql);
    if (!$reviews_stmt) throw new Exception('Prepare failed (reviews): ' . $conn->error);
    $reviews_stmt->bind_param('iii', $spot_id, $limit, $offset);
    $reviews_stmt->execute();
    $reviews_res = $reviews_stmt->get_result();

    $formatted_reviews = [];
    $now = time();
    while ($row = $reviews_res->fetch_assoc()) {
        $ts = strtotime($row['review_date']);
        $formatted_date = date('F d, Y', $ts);
        $relative = $formatted_date;
        if ($ts >= strtotime('-7 days', $now)) {
            $relative = 'This week';
        } elseif ($ts >= strtotime('-1 month', $now)) {
            $relative = 'This month';
        }
        $formatted_reviews[] = [
            'id' => (int)$row['review_id'],
            'reviewer_name' => htmlspecialchars($row['reviewer_name'] ?? 'Anonymous'),
            'reviewer_initial' => strtoupper(substr($row['reviewer_name'] ?? 'A', 0, 1)),
            'rating' => (int)$row['rating'],
            'review_text' => htmlspecialchars($row['review_text']),
            'date' => $formatted_date,
            'relative_date' => $relative,
            'helpful_count' => (int)$row['helpful_count']
        ];
    }
    $reviews_stmt->close();

    $total_reviews = (int)($stats['total_reviews'] ?? 0);
    $avg_rating = $stats['average_rating'] !== null ? round((float)$stats['average_rating'], 1) : 0.0;
    $total_for_pct = max(1, $total_reviews);
    $rating_breakdown = [];
    for ($i = 5; $i >= 1; $i--) {
        $count = (int)($stats['rating_' . $i] ?? 0);
        $rating_breakdown[$i] = [
            'count' => $count,
            'percentage' => $total_reviews ? round(($count / $total_for_pct) * 100) : 0
        ];
    }

    $response['success'] = true;
    $response['data'] = [
        'spot_name' => $spot['name'],
        'total_reviews' => $total_reviews,
        'average_rating' => $avg_rating,
        'rating_breakdown' => $rating_breakdown,
        'reviews' => $formatted_reviews,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_reviews ? (int)ceil($total_reviews / $limit) : 1,
            'has_more' => ($offset + $limit) < $total_reviews
        ]
    ];
} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
