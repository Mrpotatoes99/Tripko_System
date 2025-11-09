<?php
// get_tour.php - Returns detailed tour information similar to a TripAdvisor product page
// Query params:
//   tour_id (int) OR slug (string)
//   group_size (int, optional) - for pricing calculation
//   date (YYYY-MM-DD, optional) - for availability & cancellation window
// Response JSON structure:
// {
//   success: bool,
//   tour: { id, title, summary, description, duration_hours, start_time, end_time, meeting_point, cancellation_policy_text, reserve_now_pay_later, base_price_per_adult, price_currency, min_travelers, max_travelers, cover_image_url, average_rating, review_count, cancellation_deadline_hours },
//   pricing: { group_size, price_per_person, total_price, tier: { id, min_group_size, max_group_size } | null },
//   availability: { requested_date, status, seats_remaining } | null,
//   gallery: [ { id, image_url, alt_text } ],
//   stops: [ { id, position, name, spot_id, lat, lng, description } ],
//   reviews_summary: { average_rating, review_count }
// }

header('Content-Type: application/json; charset=utf-8');

require_once($_SERVER['DOCUMENT_ROOT'] . '/tripko-system/tripko-backend/config/db_connect.php'); // expects $conn (mysqli)

$response = [ 'success' => false ];

// --- Helpers ---
function safe_int($v){ return ($v !== null && $v !== '' && is_numeric($v)) ? (int)$v : null; }
function build_upload_url($filename){
    $fn = trim($filename ?? '');
    if($fn === '') return null;
    // If already appears to be a full URL
    if(preg_match('/^https?:/i',$fn)) return $fn;
    // Keep only basename to avoid traversal
    $base = basename($fn);
    return '/tripko-system/uploads/' . rawurlencode($base);
}

// --- Input ---
$tour_id = isset($_GET['tour_id']) ? safe_int($_GET['tour_id']) : null;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
$group_size = isset($_GET['group_size']) ? max(1, safe_int($_GET['group_size'])) : null;
$requested_date = isset($_GET['date']) ? $_GET['date'] : null; // Validate format later

if(!$tour_id && !$slug){
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Missing tour_id or slug']);
    exit;
}

// Validate date format if provided
if($requested_date){
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$requested_date)){
        $requested_date = null; // ignore invalid format
    }
}

try {
    // --- Fetch main tour ---
    if($tour_id){
        $stmt = $conn->prepare("SELECT t.*, t.cover_image AS cover_image_file FROM tours t WHERE t.id = ? AND t.is_active = 1 LIMIT 1");
        $stmt->bind_param('i',$tour_id);
    } else {
        $stmt = $conn->prepare("SELECT t.*, t.cover_image AS cover_image_file FROM tours t WHERE t.slug = ? AND t.is_active = 1 LIMIT 1");
        $stmt->bind_param('s',$slug);
    }
    $stmt->execute();
    $tourRes = $stmt->get_result();
    if(!$tourRow = $tourRes->fetch_assoc()){
        http_response_code(404);
        echo json_encode(['success'=>false,'error'=>'Tour not found']);
        exit;
    }
    $stmt->close();

    // --- Denormalized rating fallback (if null compute from reviews table) ---
    $avgRating = (float)$tourRow['average_rating'];
    $reviewCount = (int)$tourRow['review_count'];
    if(($avgRating <= 0 || $reviewCount === 0) && $conn->query("SHOW TABLES LIKE 'reviews'")){ // simplistic check
        $ridSql = "SELECT AVG(rating) avg_rating, COUNT(*) c FROM reviews WHERE entity_type='tour' AND entity_id=".(int)$tourRow['id'];
        if($rs = $conn->query($ridSql)){
            if($r = $rs->fetch_assoc()){
                if($r['c'] > 0){
                    $avgRating = round((float)$r['avg_rating'],2);
                    $reviewCount = (int)$r['c'];
                }
            }
            $rs->close();
        }
    }

    $tour = [
        'id' => (int)$tourRow['id'],
        'title' => $tourRow['title'],
        'slug' => $tourRow['slug'],
        'summary' => $tourRow['summary'],
        'description' => $tourRow['description'],
        'duration_hours' => $tourRow['duration_hours'] !== null ? (int)$tourRow['duration_hours'] : null,
        'start_time' => $tourRow['start_time'],
        'end_time' => $tourRow['end_time'],
        'meeting_point' => $tourRow['meeting_point'],
        'cancellation_policy_text' => $tourRow['cancellation_policy_text'],
        'reserve_now_pay_later' => (int)$tourRow['reserve_now_pay_later'],
        'base_price_per_adult' => (float)$tourRow['base_price_per_adult'],
        'price_currency' => $tourRow['price_currency'] ?: 'PHP',
        'min_travelers' => (int)$tourRow['min_travelers'],
        'max_travelers' => (int)$tourRow['max_travelers'],
        'cancellation_deadline_hours' => (int)$tourRow['cancellation_deadline_hours'],
        'cover_image_url' => build_upload_url($tourRow['cover_image_file']),
        'average_rating' => $avgRating,
        'review_count' => $reviewCount
    ];

    // --- Gallery ---
    $gallery = [];
    if($conn->query("SHOW TABLES LIKE 'tour_images'")){
        $gid = $tour['id'];
        $gsql = "SELECT id, image_file, alt_text FROM tour_images WHERE tour_id = $gid ORDER BY position ASC, id ASC";
        if($grs = $conn->query($gsql)){
            while($g = $grs->fetch_assoc()){
                $gallery[] = [
                    'id'=>(int)$g['id'],
                    'image_url'=> build_upload_url($g['image_file']),
                    'alt_text'=> $g['alt_text']
                ];
            }
            $grs->close();
        }
    }

    // --- Stops ---
    $stops = [];
    if($conn->query("SHOW TABLES LIKE 'tour_stops'")){
        $sid = $tour['id'];
        $ssql = "SELECT s.id, s.position, s.custom_name, s.spot_id, s.lat, s.lng, s.description, ts.name AS spot_name, ts.image_path AS spot_image
                 FROM tour_stops s
                 LEFT JOIN tourist_spots ts ON ts.id = s.spot_id
                 WHERE s.tour_id = $sid ORDER BY s.position ASC";
        if($srs = $conn->query($ssql)){
            while($s = $srs->fetch_assoc()){
                $name = $s['custom_name'] ?: ($s['spot_name'] ?? 'Stop');
                $stops[] = [
                    'id' => (int)$s['id'],
                    'position' => (int)$s['position'],
                    'name' => $name,
                    'spot_id' => $s['spot_id'] ? (int)$s['spot_id'] : null,
                    'lat' => $s['lat'] !== null ? (float)$s['lat'] : null,
                    'lng' => $s['lng'] !== null ? (float)$s['lng'] : null,
                    'description' => $s['description']
                ];
            }
            $srs->close();
        }
    }

    // --- Pricing tiers ---
    $tiers = [];
    if($conn->query("SHOW TABLES LIKE 'tour_pricing_tiers'")){
        $pid = $tour['id'];
        $psql = "SELECT id, min_group_size, max_group_size, price_per_person FROM tour_pricing_tiers WHERE tour_id = $pid ORDER BY min_group_size ASC";
        if($prs = $conn->query($psql)){
            while($p = $prs->fetch_assoc()){
                $tiers[] = [
                    'id'=>(int)$p['id'],
                    'min_group_size'=>(int)$p['min_group_size'],
                    'max_group_size'=>$p['max_group_size'] !== null ? (int)$p['max_group_size'] : null,
                    'price_per_person'=>(float)$p['price_per_person']
                ];
            }
            $prs->close();
        }
    }

    // --- Pricing calculation ---
    $pricing = null;
    if($group_size){
        $pricePer = (float)$tour['base_price_per_adult'];
        $tierApplied = null;
        foreach($tiers as $t){
            $inRange = $group_size >= $t['min_group_size'] && ($t['max_group_size'] === null || $group_size <= $t['max_group_size']);
            if($inRange){
                $pricePer = $t['price_per_person'];
                $tierApplied = $t; break;
            }
        }
        $total = $pricePer * $group_size;
        $pricing = [
            'group_size' => $group_size,
            'price_per_person' => round($pricePer,2),
            'total_price' => round($total,2),
            'tier' => $tierApplied
        ];
    }

    // --- Availability (simple) ---
    $availability = null;
    if($requested_date && $conn->query("SHOW TABLES LIKE 'tour_dates'")){
        $dstmt = $conn->prepare("SELECT seats_total, seats_booked, status FROM tour_dates WHERE tour_id = ? AND tour_date = ? LIMIT 1");
        $dstmt->bind_param('is', $tour['id'], $requested_date);
        $dstmt->execute();
        $dres = $dstmt->get_result();
        if($drow = $dres->fetch_assoc()){
            $remaining = (int)$drow['seats_total'] - (int)$drow['seats_booked'];
            if($remaining < 0) $remaining = 0;
            $availability = [
                'requested_date' => $requested_date,
                'status' => $drow['status'],
                'seats_remaining' => $remaining
            ];
        } else {
            // If no specific row, treat as open (basic assumption)
            $availability = [
                'requested_date' => $requested_date,
                'status' => 'open',
                'seats_remaining' => null
            ];
        }
        $dstmt->close();
    }

    $response['success'] = true;
    $response['tour'] = $tour;
    $response['pricing'] = $pricing;
    $response['availability'] = $availability;
    $response['gallery'] = $gallery;
    $response['stops'] = $stops;
    $response['reviews_summary'] = [ 'average_rating'=>$tour['average_rating'], 'review_count'=>$tour['review_count'] ];

    echo json_encode($response);
    exit;

} catch(Exception $ex){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Server error','detail'=>$ex->getMessage()]);
    exit;
}
