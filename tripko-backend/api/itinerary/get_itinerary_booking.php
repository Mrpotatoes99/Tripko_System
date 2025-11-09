<?php
/**
 * Endpoint: /tripko-backend/api/itinerary/get_itinerary_booking.php?itinerary_id=123
 * Returns booking/pricing meta + pricing tiers for an itinerary.
 * Response (success): {
 *   success: true,
 *   data: {
 *     itinerary_id, title, image, currency, reserve_now_pay_later, cancellation_policy_text,
 *     cancellation_deadline_hours, min_travelers, max_travelers, from_price_per_adult,
 *     tiers: [ { min_pax, max_pax, price_per_adult } ]
 *   }
 * }
 * Notes:
 * - Designed to degrade gracefully if migration columns not yet present.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/Database.php';

$response = ['success'=>false];
try {
    $id = isset($_GET['itinerary_id']) ? (int)$_GET['itinerary_id'] : 0;
    if($id <= 0) throw new Exception('Invalid itinerary ID');

    $db = (new Database())->getConnection();
    if(!$db) throw new Exception('DB connection failed');

    // Detect columns added by booking migration
    $cols = [];
    if($cRes = $db->query("SHOW COLUMNS FROM itineraries")) {
        while($cRow = $cRes->fetch_assoc()) { $cols[$cRow['Field']] = true; }
        $cRes->free();
    }
    $hasBookingCols = isset($cols['base_price_per_adult']);

    // Build SELECT list dynamically (avoid errors pre-migration)
    $sel = ['i.itinerary_id','i.name','i.image_path'];
    foreach(['base_price_per_adult','price_currency','reserve_now_pay_later','cancellation_policy_text','cancellation_deadline_hours','min_travelers','max_travelers'] as $opt){
        if(isset($cols[$opt])) $sel[] = 'i.' . $opt;
    }
    $sql = 'SELECT ' . implode(',', $sel) . ' FROM itineraries i WHERE i.itinerary_id='.$id.' LIMIT 1';
    if(!$res = $db->query($sql)) throw new Exception('Query failed: '.$db->error);
    $row = $res->fetch_assoc(); $res->close();
    if(!$row) throw new Exception('Itinerary not found');

    $data = [
        'itinerary_id' => (int)$row['itinerary_id'],
        'title' => $row['name'],
        'image' => $row['image_path'] ? '/uploads/' . basename($row['image_path']) : null,
        'currency' => $row['price_currency'] ?? 'PHP',
        'reserve_now_pay_later' => isset($row['reserve_now_pay_later']) ? (bool)$row['reserve_now_pay_later'] : false,
        'cancellation_policy_text' => $row['cancellation_policy_text'] ?? null,
        'cancellation_deadline_hours' => isset($row['cancellation_deadline_hours']) ? (int)$row['cancellation_deadline_hours'] : 0,
        'min_travelers' => isset($row['min_travelers']) ? (int)$row['min_travelers'] : 1,
        'max_travelers' => isset($row['max_travelers']) ? (int)$row['max_travelers'] : 0,
        'tiers' => []
    ];

    // Pricing tiers (if table exists)
    $hasTierTable = false;
    if($chk = $db->query("SHOW TABLES LIKE 'itinerary_pricing_tiers'")) { if($chk->num_rows>0) $hasTierTable = true; $chk->free(); }
    if($hasTierTable){
        $tierSql = "SELECT min_pax, max_pax, price_per_adult FROM itinerary_pricing_tiers WHERE itinerary_id=$id ORDER BY min_pax ASC";
        if($tRes = $db->query($tierSql)){
            while($t = $tRes->fetch_assoc()){
                $data['tiers'][] = [
                    'min_pax' => (int)$t['min_pax'],
                    'max_pax' => (int)$t['max_pax'],
                    'price_per_adult' => (float)$t['price_per_adult']
                ];
            }
            $tRes->free();
        }
    }

    // Fallback single tier from base_price_per_adult
    if(empty($data['tiers']) && $hasBookingCols && isset($row['base_price_per_adult']) && $row['base_price_per_adult'] !== null){
        $data['tiers'][] = [
            'min_pax' => $data['min_travelers'],
            'max_pax' => $data['max_travelers'] ?: $data['min_travelers'],
            'price_per_adult' => (float)$row['base_price_per_adult']
        ];
    }

    // Compute from price
    $data['from_price_per_adult'] = null;
    if(!empty($data['tiers'])){
        $data['from_price_per_adult'] = min(array_map(function($t){return $t['price_per_adult'];}, $data['tiers']));
    }

    // Graceful message if no booking columns yet
    if(!$hasBookingCols){
        $data['note'] = 'Booking pricing not yet configured for this itinerary.';
    }

    $response['success'] = true; $response['data'] = $data;
} catch(Throwable $e){
    http_response_code(400);
    $response['message'] = $e->getMessage();
}
echo json_encode($response);
