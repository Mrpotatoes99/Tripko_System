<?php
// ============================================================================
// SIMPLIFIED BOOKING DETAILS API - Works with Denormalized Itineraries Table
// ============================================================================
// This version queries the actual database structure (no migration tables)
// ============================================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verify mysqli connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Get itinerary_id from query parameter
    $itinerary_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($itinerary_id <= 0) {
        throw new Exception('Invalid itinerary ID');
    }
    
    // ========================================================================
    // 1. GET BASIC ITINERARY INFO (from first row)
    // ========================================================================
    $query = "
        SELECT 
            i.itinerary_id,
            i.name,
            i.description,
            i.image_path,
            i.environmental_fee as base_price,
            i.status,
            i.visibility,
            i.published_at,
            t.name as town_name,
            t.town_id,
            COUNT(DISTINCT i.day_number) as total_days,
            COUNT(i.item_id) as total_items
        FROM itineraries i
        LEFT JOIN towns t ON i.town_id = t.town_id
        WHERE i.itinerary_id = ?
        GROUP BY i.itinerary_id
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $itinerary_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $itinerary = $result->fetch_assoc();
    
    if (!$itinerary) {
        throw new Exception('Itinerary not found');
    }
    
    // ========================================================================
    // 2. GET DAY-BY-DAY ITINERARY (grouped by day_number)
    // ========================================================================
    $days_query = "
        SELECT 
            i.day_number,
            i.day_title,
            i.day_description,
            i.item_id,
            i.spot_id,
            i.custom_name,
            i.start_time,
            i.end_time,
            i.estimated_duration_minutes,
            i.travel_minutes_from_prev,
            i.notes,
            i.sort_order,
            ts.name as spot_name,
            ts.image_path as spot_image
        FROM itineraries i
        LEFT JOIN tourist_spots ts ON i.spot_id = ts.spot_id
        WHERE i.itinerary_id = ?
        ORDER BY i.day_number ASC, i.sort_order ASC
    ";
    
    $stmt = $conn->prepare($days_query);
    $stmt->bind_param('i', $itinerary_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    // Group items by day
    $days = [];
    foreach ($items as $item) {
        $day_num = $item['day_number'];
        
        if (!isset($days[$day_num])) {
            $days[$day_num] = [
                'day' => $day_num,
                'title' => $item['day_title'] ?? "Day $day_num",
                'description' => $item['day_description'] ?? '',
                'items' => []
            ];
        }
        
        $days[$day_num]['items'][] = [
            'item_id' => $item['item_id'],
            'name' => $item['custom_name'] ?? $item['spot_name'] ?? 'Activity',
            'spot_id' => $item['spot_id'],
            'spot_name' => $item['spot_name'],
            'start_time' => $item['start_time'],
            'end_time' => $item['end_time'],
            'duration_minutes' => $item['estimated_duration_minutes'],
            'travel_time' => $item['travel_minutes_from_prev'],
            'notes' => $item['notes'],
            'sort_order' => $item['sort_order']
        ];
    }
    
    // Convert to indexed array
    $days = array_values($days);
    
    // ========================================================================
    // 3. GET REVIEWS
    // ========================================================================
    $reviews_query = "
        SELECT 
            review_id,
            reviewer_name,
            reviewer_email,
            rating,
            review_text,
            review_date,
            status
        FROM reviews
        WHERE entity_type = 'itinerary' 
        AND entity_id = ?
        AND status = 'active'
        ORDER BY review_date DESC
        LIMIT 20
    ";
    
    $stmt = $conn->prepare($reviews_query);
    $stmt->bind_param('i', $itinerary_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    // Calculate review breakdown
    $rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $total_reviews = count($reviews);
    $sum_ratings = 0;
    
    foreach ($reviews as $review) {
        $rating = intval($review['rating']);
        if ($rating >= 1 && $rating <= 5) {
            $rating_counts[$rating]++;
            $sum_ratings += $rating;
        }
    }
    
    $average_rating = $total_reviews > 0 ? round($sum_ratings / $total_reviews, 2) : 0;
    
    $review_breakdown = [
        'total' => $total_reviews,
        'average' => $average_rating,
        'distribution' => [
            ['stars' => 5, 'count' => $rating_counts[5], 'percentage' => $total_reviews > 0 ? round(($rating_counts[5] / $total_reviews) * 100) : 0],
            ['stars' => 4, 'count' => $rating_counts[4], 'percentage' => $total_reviews > 0 ? round(($rating_counts[4] / $total_reviews) * 100) : 0],
            ['stars' => 3, 'count' => $rating_counts[3], 'percentage' => $total_reviews > 0 ? round(($rating_counts[3] / $total_reviews) * 100) : 0],
            ['stars' => 2, 'count' => $rating_counts[2], 'percentage' => $total_reviews > 0 ? round(($rating_counts[2] / $total_reviews) * 100) : 0],
            ['stars' => 1, 'count' => $rating_counts[1], 'percentage' => $total_reviews > 0 ? round(($rating_counts[1] / $total_reviews) * 100) : 0],
        ]
    ];
    
    // ========================================================================
    // 4. GET SPOT IMAGES (for photo gallery)
    // ========================================================================
    $photos_query = "
        SELECT DISTINCT
            si.image_file as image_path,
            si.alt_text as caption,
            ts.name as spot_name
        FROM itineraries i
        INNER JOIN tourist_spots ts ON i.spot_id = ts.spot_id
        INNER JOIN spot_images si ON ts.spot_id = si.spot_id
        WHERE i.itinerary_id = ?
        AND i.spot_id IS NOT NULL
        ORDER BY si.is_primary DESC, si.position ASC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($photos_query);
    $stmt->bind_param('i', $itinerary_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $photos = [];
    while ($row = $result->fetch_assoc()) {
        $photos[] = $row;
    }
    
    // Add main itinerary image if no photos
    if (empty($photos) && !empty($itinerary['image_path'])) {
        $photos[] = [
            'image_path' => $itinerary['image_path'],
            'caption' => $itinerary['name'],
            'spot_name' => $itinerary['name']
        ];
    }
    
    // ========================================================================
    // 5. BUILD RESPONSE (Simplified - No Migration Fields)
    // ========================================================================
    $response = [
        'success' => true,
        'itinerary' => [
            'id' => $itinerary['itinerary_id'],
            'name' => $itinerary['name'],
            'description' => $itinerary['description'],
            'image_path' => $itinerary['image_path'],
            'town' => $itinerary['town_name'],
            'town_id' => $itinerary['town_id'],
            'status' => $itinerary['status'],
            'visibility' => $itinerary['visibility'],
            'published_at' => $itinerary['published_at'],
            'total_days' => $itinerary['total_days'],
            'total_items' => $itinerary['total_items']
        ],
        'pricing' => [
            'base_price' => floatval($itinerary['base_price'] ?? 0),
            'currency' => 'PHP',
            'price_note' => 'Contact tourism office for group rates and updated pricing'
        ],
        'trust_signals' => [
            'mobile_ticket' => false,
            'instant_confirmation' => false,
            'free_cancellation' => false,
            'cancellation_note' => 'Contact tourism office for cancellation policy'
        ],
        'highlights' => [
            'Guided tour of ' . $itinerary['town_name'] . ' attractions',
            $itinerary['total_days'] . '-day itinerary with ' . $itinerary['total_items'] . ' activities',
            'Explore local tourist spots and hidden gems',
            'Professional local guide',
            'Flexible schedule available'
        ],
        'whats_included' => [
            'Tour guide services',
            'Transportation between stops',
            'Entrance fees (where applicable)',
            'Itinerary planning and coordination'
        ],
        'whats_excluded' => [
            'Personal expenses',
            'Meals (unless specified)',
            'Accommodation',
            'Travel insurance',
            'Tips and gratuities'
        ],
        'what_to_bring' => [
            'Valid ID',
            'Comfortable clothing and shoes',
            'Sun protection (hat, sunscreen)',
            'Water bottle',
            'Camera or smartphone',
            'Cash for personal expenses'
        ],
        'meeting_point' => [
            'name' => $itinerary['town_name'] . ' Tourism Office',
            'address' => 'Contact tourism office for exact meeting point',
            'instructions' => 'Please arrive 15 minutes before departure time'
        ],
        'faqs' => [
            [
                'question' => 'How do I book this itinerary?',
                'answer' => 'Please contact the ' . $itinerary['town_name'] . ' Tourism Office to arrange your visit and confirm availability.'
            ],
            [
                'question' => 'Can I customize the itinerary?',
                'answer' => 'Yes! This is a suggested itinerary. The tourism office can help customize it based on your interests, time, and budget.'
            ],
            [
                'question' => 'What is the best time to visit?',
                'answer' => 'The best time depends on the season and your preferences. Contact the tourism office for recommendations based on current conditions.'
            ],
            [
                'question' => 'Is this suitable for children/elderly?',
                'answer' => 'Most activities are family-friendly. Please inform the tourism office of any special requirements when booking.'
            ]
        ],
        'days' => $days,
        'photos' => $photos,
        'reviews' => [
            'breakdown' => $review_breakdown,
            'recent_reviews' => array_slice($reviews, 0, 5) // Latest 5 reviews
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename(__FILE__),
            'line' => $e->getLine()
        ]
    ], JSON_PRETTY_PRINT);
}
?>
