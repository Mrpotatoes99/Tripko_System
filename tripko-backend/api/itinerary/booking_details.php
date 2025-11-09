<?php
/**
 * Get Itinerary Booking Details
 * Returns complete booking page data including pricing, FAQs, photos, reviews
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once($_SERVER['DOCUMENT_ROOT'] . '/tripko-system/tripko-backend/config/database.php');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get itinerary_id from query param
    $itinerary_id = isset($_GET['itinerary_id']) ? intval($_GET['itinerary_id']) : 0;
    
    if ($itinerary_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid itinerary ID'
        ]);
        exit;
    }
    
    // Get comprehensive itinerary data from view
    $query = "SELECT * FROM vw_itinerary_booking_detail WHERE itinerary_id = :itinerary_id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':itinerary_id', $itinerary_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $itinerary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itinerary) {
        echo json_encode([
            'success' => false,
            'message' => 'Itinerary not found'
        ]);
        exit;
    }
    
    // Parse JSON fields
    $jsonFields = ['highlights', 'whats_included', 'whats_excluded', 'what_to_bring', 'additional_info'];
    foreach ($jsonFields as $field) {
        if (!empty($itinerary[$field])) {
            $decoded = json_decode($itinerary[$field], true);
            $itinerary[$field] = $decoded ?: [];
        } else {
            $itinerary[$field] = [];
        }
    }
    
    // Get pricing tiers
    $pricingQuery = "SELECT * FROM itinerary_pricing_tiers 
                     WHERE itinerary_id = :itinerary_id AND status = 'active'
                     ORDER BY sort_order ASC";
    $pricingStmt = $db->prepare($pricingQuery);
    $pricingStmt->bindParam(':itinerary_id', $itinerary_id, PDO::PARAM_INT);
    $pricingStmt->execute();
    $pricing_tiers = $pricingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get FAQs
    $faqQuery = "SELECT question, answer FROM itinerary_faqs 
                 WHERE itinerary_id = :itinerary_id AND status = 'active'
                 ORDER BY sort_order ASC";
    $faqStmt = $db->prepare($faqQuery);
    $faqStmt->bindParam(':itinerary_id', $itinerary_id, PDO::PARAM_INT);
    $faqStmt->execute();
    $faqs = $faqStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get photos (hero carousel)
    $photoQuery = "SELECT image_path, caption, photo_type 
                   FROM itinerary_photos 
                   WHERE itinerary_id = :itinerary_id AND status = 'active' AND is_hero = 1
                   ORDER BY sort_order ASC";
    $photoStmt = $db->prepare($photoQuery);
    $photoStmt->bindParam(':itinerary_id', $itinerary_id, PDO::PARAM_INT);
    $photoStmt->execute();
    $photos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add uploads path to photos
    foreach ($photos as &$photo) {
        $photo['url'] = '/tripko-system/uploads/' . $photo['image_path'];
    }
    
    // Get day-by-day itinerary
    $dayQuery = "SELECT day_number, day_title, day_description, 
                        item_id, spot_id, custom_name, item_name,
                        start_time, end_time, estimated_duration_minutes,
                        travel_minutes_from_prev, notes, sort_order
                 FROM vw_itinerary_detail
                 WHERE itinerary_id = :itinerary_id
                 ORDER BY day_number ASC, sort_order ASC";
    $dayStmt = $db->prepare($dayQuery);
    $dayStmt->bindParam(':itinerary_id', $itinerary_id, PDO::PARAM_INT);
    $dayStmt->execute();
    $dayItems = $dayStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize by days
    $days = [];
    foreach ($dayItems as $item) {
        $dayNum = $item['day_number'];
        if (!$dayNum) continue;
        
        if (!isset($days[$dayNum])) {
            $days[$dayNum] = [
                'day_number' => $dayNum,
                'day_title' => $item['day_title'],
                'day_description' => $item['day_description'],
                'items' => []
            ];
        }
        
        if ($item['item_id']) {
            $days[$dayNum]['items'][] = [
                'item_id' => $item['item_id'],
                'name' => $item['item_name'] ?: $item['custom_name'],
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
                'duration' => $item['estimated_duration_minutes'],
                'travel_time' => $item['travel_minutes_from_prev'],
                'notes' => $item['notes']
            ];
        }
    }
    
    $days = array_values($days); // Re-index
    
    // Return complete booking data
    echo json_encode([
        'success' => true,
        'data' => [
            'itinerary_id' => $itinerary['itinerary_id'],
            'name' => $itinerary['name'],
            'description' => $itinerary['description'],
            'image_path' => $itinerary['image_path'],
            'town_name' => $itinerary['town_name'],
            
            // Pricing & Booking
            'base_price' => floatval($itinerary['base_price']),
            'currency' => $itinerary['price_currency'],
            'duration_hours' => intval($itinerary['duration_hours']),
            'start_time' => $itinerary['start_time'],
            'min_travelers' => intval($itinerary['min_travelers']),
            'max_travelers' => intval($itinerary['max_travelers']),
            
            // Trust signals
            'mobile_ticket' => (bool)$itinerary['mobile_ticket'],
            'instant_confirmation' => (bool)$itinerary['instant_confirmation'],
            'free_cancellation' => (bool)$itinerary['free_cancellation'],
            'cancellation_hours' => intval($itinerary['cancellation_hours']),
            
            // Content
            'highlights' => $itinerary['highlights'],
            'whats_included' => $itinerary['whats_included'],
            'whats_excluded' => $itinerary['whats_excluded'],
            'what_to_bring' => $itinerary['what_to_bring'],
            'accessibility_info' => $itinerary['accessibility_info'],
            'additional_info' => $itinerary['additional_info'],
            'meeting_point' => $itinerary['meeting_point'],
            'end_point' => $itinerary['end_point'],
            'pickup_offered' => (bool)$itinerary['pickup_offered'],
            'pickup_details' => $itinerary['pickup_details'],
            
            // Reviews
            'total_reviews' => intval($itinerary['total_reviews']),
            'average_rating' => floatval($itinerary['average_rating']),
            'rating_breakdown' => [
                '5' => [
                    'count' => intval($itinerary['rating_5_count']),
                    'percent' => floatval($itinerary['rating_5_percent'])
                ],
                '4' => [
                    'count' => intval($itinerary['rating_4_count']),
                    'percent' => floatval($itinerary['rating_4_percent'])
                ],
                '3' => [
                    'count' => intval($itinerary['rating_3_count']),
                    'percent' => floatval($itinerary['rating_3_percent'])
                ],
                '2' => [
                    'count' => intval($itinerary['rating_2_count']),
                    'percent' => floatval($itinerary['rating_2_percent'])
                ],
                '1' => [
                    'count' => intval($itinerary['rating_1_count']),
                    'percent' => floatval($itinerary['rating_1_percent'])
                ]
            ],
            
            // Structured data
            'pricing_tiers' => $pricing_tiers,
            'faqs' => $faqs,
            'photos' => $photos,
            'days' => $days,
            'day_count' => intval($itinerary['day_count']),
            'item_count' => intval($itinerary['item_count'])
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Booking details error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
