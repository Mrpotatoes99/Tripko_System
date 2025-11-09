
<?php
/**
 * Endpoint: /tripko-backend/api/itinerary/map_data.php
 * Purpose: Provide unified data for Things-To-Do page (spots + public itineraries with days/items)
 * Response schema:
 * {
 *   success: true,
 *   generated: "2025-10-01T12:34:56Z",
 *   spots: [ { id, name, municipality, category, lat, lng, image, description } ],
 *   itineraries: [
 *      { id, title, town_id, town_name, total_days, image, description, days:[ { day, title, items:[ { item_id, sort, spot_id, name, lat, lng, start_time, end_time, duration_min } ] } ] }
 *   ]
 * }
 * Notes:
 * - Only public & active itineraries (visibility='public' AND status='active')
 * - Items without coordinates (missing spot or spot has no geo) are skipped.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/Database.php';

try {
    $db = (new Database())->getConnection();
    if(!$db) throw new Exception('DB connection failed');

    $result = [
        'success' => true,
        'generated' => gmdate('c'),
        'spots' => [],
        'itineraries' => []
    ];

    /* ---------------------------------
       Load active tourist spots (with geo)
       --------------------------------- */
    $sqlSpots = "SELECT ts.spot_id, ts.name, ts.description, ts.category, ts.image_path, t.name AS town_name,
               gp.latitude, gp.longitude
           FROM tourist_spots ts
           JOIN towns t ON ts.town_id = t.town_id
           LEFT JOIN geo_points gp ON gp.entity_type='tourist_spot' AND gp.entity_id = ts.spot_id
           WHERE ts.status='active' AND gp.latitude IS NOT NULL AND gp.longitude IS NOT NULL
           LIMIT 800"; // generous upper bound

    if($res = $db->query($sqlSpots)){
        while($row = $res->fetch_assoc()){
            $result['spots'][] = [
                'id' => (int)$row['spot_id'],
                'name' => $row['name'],
                'municipality' => $row['town_name'],
                // use actual spot category so client filters work
                'category' => $row['category'] ?? null,
                'lat' => (float)$row['latitude'],
                'lng' => (float)$row['longitude'],
                'image' => $row['image_path'] ? '/uploads/' . basename($row['image_path']) : null,
                'description' => $row['description'],
                'avg_rating' => null,
                'review_count' => 0
            ];
        }
        $res->free();
    } else {
        throw new Exception('Query failed (spots): '.$db->error);
    }

     /* ---------------------------------
         Load public itineraries meta (flattened schema)
         - New itineraries table stores one row per item; compute totals by grouping
         --------------------------------- */
     $sqlIt = "SELECT i.itinerary_id, i.name, i.description, i.town_id, i.image_path, t.name AS town_name,
                            COUNT(DISTINCT i.day_number) AS total_days,
                            MIN(i.created_at) AS created_at
                  FROM itineraries i
                  LEFT JOIN towns t ON t.town_id = i.town_id
                  WHERE i.visibility='public' AND i.status='active'
                  GROUP BY i.itinerary_id
                  ORDER BY created_at DESC
                  LIMIT 300"; // reasonable cap

    $itMeta = [];
    if($res = $db->query($sqlIt)){
        while($row = $res->fetch_assoc()){
            $itMeta[$row['itinerary_id']] = [
                'id' => (int)$row['itinerary_id'],
                'title' => $row['name'],
                'description' => $row['description'],
                'town_id' => (int)$row['town_id'],
                'town_name' => $row['town_name'],
                'total_days' => (int)$row['total_days'],
                'image' => $row['image_path'] ? '/uploads/' . basename($row['image_path']) : null,
                'days' => [],
                'avg_rating' => null,
                'review_count' => 0
            ];
        }
        $res->free();
    } else {
        throw new Exception('Query failed (itineraries meta): '.$db->error);
    }

    if(!empty($itMeta)){
        $idsIn = implode(',', array_map('intval', array_keys($itMeta)));

        // Build day shells from flattened table (distinct day numbers per itinerary)
        $sqlDays = "SELECT itinerary_id, day_number, MAX(day_title) AS day_title
                    FROM itineraries
                    WHERE itinerary_id IN ($idsIn)
                    GROUP BY itinerary_id, day_number
                    ORDER BY itinerary_id, day_number";
        if($res = $db->query($sqlDays)){
            while($d = $res->fetch_assoc()){
                $itId   = (int)$d['itinerary_id'];
                $dayNum = (int)$d['day_number'];
                if(!isset($itMeta[$itId])) continue;
                $itMeta[$itId]['days'][$dayNum] = [
                    'day' => $dayNum,
                    'title' => $d['day_title'],
                    'items' => []
                ];
            }
            $res->free();
        } else {
            throw new Exception('Query failed (days - flat): '.$db->error);
        }

        // Fetch items for those itineraries (from flattened table) + geo via spot
        $sqlItems = "SELECT i.itinerary_id, i.day_number, i.item_id, i.spot_id, i.custom_name,
                            i.start_time, i.end_time, i.estimated_duration_minutes, i.sort_order,
                            gp.latitude, gp.longitude, ts.name AS spot_name
                     FROM itineraries i
                     LEFT JOIN tourist_spots ts ON i.spot_id = ts.spot_id
                     LEFT JOIN geo_points gp ON gp.entity_type='tourist_spot' AND gp.entity_id = i.spot_id
                     WHERE i.itinerary_id IN ($idsIn)
                     ORDER BY i.itinerary_id, i.day_number, i.sort_order, i.item_id";

        if($res = $db->query($sqlItems)){
            while($it = $res->fetch_assoc()){
                $itineraryId = (int)$it['itinerary_id'];
                $dayNum = (int)$it['day_number'];
                if(!isset($itMeta[$itineraryId]['days'][$dayNum])) continue;

                // Skip if no coordinates for spot (avoid map confusion)
                if(!$it['latitude'] || !$it['longitude']) continue;

                $displayName = $it['custom_name'] ?: ($it['spot_name'] ?: 'Activity');
                $itMeta[$itineraryId]['days'][$dayNum]['items'][] = [
                    'item_id' => $it['item_id'] !== null ? (int)$it['item_id'] : null,
                    'sort' => (int)$it['sort_order'],
                    'spot_id' => $it['spot_id'] ? (int)$it['spot_id'] : null,
                    'name' => $displayName,
                    'lat' => (float)$it['latitude'],
                    'lng' => (float)$it['longitude'],
                    'start_time' => $it['start_time'],
                    'end_time' => $it['end_time'],
                    'duration_min' => $it['estimated_duration_minutes'] ? (int)$it['estimated_duration_minutes'] : null
                ];
            }
            $res->free();
        } else {
            throw new Exception('Query failed (items - flat): '.$db->error);
        }

        // Normalize days array ordering and push to result
        foreach($itMeta as $itId => $meta){
            if(!empty($meta['days'])){ ksort($meta['days']); $meta['days'] = array_values($meta['days']); }
            $result['itineraries'][] = $meta;
        }
    }

    /* ---------------------------------
       Ratings enrichment (if reviews table exists)
       --------------------------------- */
    $hasReviews = false; $reviewsCols = [];
    if($chk = $db->query("SHOW TABLES LIKE 'reviews'")){
        if($chk->num_rows > 0){ $hasReviews = true; }
        $chk->free();
    }
    if($hasReviews){
        // Inspect columns to decide schema variant
        if($cRes = $db->query("SHOW COLUMNS FROM reviews")){
            while($cRow = $cRes->fetch_assoc()){ $reviewsCols[] = $cRow['Field']; }
            $cRes->free();
        }
        $modern = in_array('entity_type',$reviewsCols,true) && in_array('entity_id',$reviewsCols,true);

        // Spots ratings batch
        if(!empty($result['spots'])){
            $spotIdsIn = implode(',', array_map('intval', array_column($result['spots'], 'id')));
            if($spotIdsIn){
                if($modern){
                    $sqlR = "SELECT entity_id AS sid, AVG(rating) avg_r, COUNT(*) c FROM reviews WHERE entity_type='spot' AND entity_id IN ($spotIdsIn) GROUP BY entity_id";
                } else {
                    // Legacy schema assumed: column 'spot_id'
                    if(!in_array('spot_id',$reviewsCols,true)) { $sqlR = null; }
                    else $sqlR = "SELECT spot_id AS sid, AVG(rating) avg_r, COUNT(*) c FROM reviews WHERE spot_id IN ($spotIdsIn) GROUP BY spot_id";
                }
                if($sqlR && ($rres = $db->query($sqlR))){
                    $ratingMap = [];
                    while($r = $rres->fetch_assoc()){
                        $ratingMap[(int)$r['sid']] = [ 'avg'=>round((float)$r['avg_r'],2), 'c'=>(int)$r['c'] ];
                    }
                    $rres->free();
                    foreach($result['spots'] as &$s){ if(isset($ratingMap[$s['id']])){ $s['avg_rating']=$ratingMap[$s['id']]['avg']; $s['review_count']=$ratingMap[$s['id']]['c']; } }
                    unset($s);
                }
            }
        }
        // Itineraries ratings batch (only if modern schema supports entity_type/itinerary)
        if(!empty($result['itineraries']) && $modern){
            $itIdsIn = implode(',', array_map('intval', array_column($result['itineraries'], 'id')));
            if($itIdsIn){
                $sqlRI = "SELECT entity_id AS iid, AVG(rating) avg_r, COUNT(*) c FROM reviews WHERE entity_type='itinerary' AND entity_id IN ($itIdsIn) GROUP BY entity_id";
                if($rres2 = $db->query($sqlRI)){
                    $ratingMap2 = [];
                    while($r2 = $rres2->fetch_assoc()){
                        $ratingMap2[(int)$r2['iid']] = [ 'avg'=>round((float)$r2['avg_r'],2), 'c'=>(int)$r2['c'] ];
                    }
                    $rres2->free();
                    foreach($result['itineraries'] as &$it){ if(isset($ratingMap2[$it['id']])){ $it['avg_rating']=$ratingMap2[$it['id']]['avg']; $it['review_count']=$ratingMap2[$it['id']]['c']; } }
                    unset($it);
                }
            }
        }
    }

    echo json_encode($result);
} catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
