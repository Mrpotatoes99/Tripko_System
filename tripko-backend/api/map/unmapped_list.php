<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/Database.php';

if (!isset($_SESSION['user_type_id'])) {
    http_response_code(401);
    echo json_encode(['error'=>true,'message'=>'Not authenticated']);
    exit;
}
$role = (int)$_SESSION['user_type_id'];
if ($role !== 3 && $role !== 1) {
    http_response_code(403);
    echo json_encode(['error'=>true,'message'=>'Forbidden']);
    exit;
}

// If tourism officer, lookup their town for scoping
$townId = null;
if ($role === 3) {
    try {
        $dbTmp = (new Database())->getConnection();
        if($stmt = $dbTmp->prepare('SELECT town_id FROM user WHERE user_id = ? AND user_type_id = 3 LIMIT 1')){
            $stmt->bind_param('i', $_SESSION['user_id']);
            if($stmt->execute()){
                $res = $stmt->get_result();
                if($row = $res->fetch_assoc()){
                    if ($row['town_id']) { $townId = (int)$row['town_id']; }
                }
            }
            $stmt->close();
        }
    } catch (Throwable $e) { /* ignore */ }
}

try {
    $db = (new Database())->getConnection();
    $items = [];

    // Tourist spots
    $sqlSpots = "SELECT ts.spot_id, ts.name, gp.latitude, gp.longitude, gp.accuracy
                 FROM tourist_spots ts
                 LEFT JOIN geo_points gp ON gp.entity_type='tourist_spot' AND gp.entity_id=ts.spot_id
                 WHERE ts.status='active" . ($townId ? " AND ts.town_id = ?" : "") . " LIMIT 500";
    if($townId){
        $stmtSpots = $db->prepare($sqlSpots);
        $stmtSpots->bind_param('i',$townId);
        $stmtSpots->execute();
        $resSpots = $stmtSpots->get_result();
    } else {
        $resSpots = $db->query($sqlSpots);
    }
    if($resSpots){ while($r = $resSpots->fetch_assoc()){
        $items[] = [
            'id'=>(int)$r['spot_id'],
            'name'=>$r['name'],
            'type'=>'tourist_spot',
            'lat'=>$r['latitude'] ? (float)$r['latitude'] : null,
            'lng'=>$r['longitude'] ? (float)$r['longitude'] : null,
            'has_coords'=> (bool)$r['latitude'] && (bool)$r['longitude'],
            'accuracy'=>$r['accuracy'] ?: null
        ]; } }

    // Festivals
    $sqlFest = "SELECT f.festival_id, f.name, gp.latitude, gp.longitude, gp.accuracy
                FROM festivals f
                LEFT JOIN geo_points gp ON gp.entity_type='festival' AND gp.entity_id=f.festival_id
                WHERE f.status='active" . ($townId ? " AND f.town_id = ?" : "") . " LIMIT 300";
    if($townId){
        $stmtFest = $db->prepare($sqlFest);
        $stmtFest->bind_param('i',$townId);
        $stmtFest->execute();
        $resFest = $stmtFest->get_result();
    } else { $resFest = $db->query($sqlFest); }
    if($resFest){ while($r = $resFest->fetch_assoc()){
        $items[] = [
            'id'=>(int)$r['festival_id'],
            'name'=>$r['name'],
            'type'=>'festival',
            'lat'=>$r['latitude'] ? (float)$r['latitude'] : null,
            'lng'=>$r['longitude'] ? (float)$r['longitude'] : null,
            'has_coords'=> (bool)$r['latitude'] && (bool)$r['longitude'],
            'accuracy'=>$r['accuracy'] ?: null
        ]; } }

    // Itineraries (optional table) scoped by town if officer
    try {
        $chkIt = $db->query("SHOW TABLES LIKE 'itineraries'");
        if ($chkIt && $chkIt->num_rows > 0) {
            $sqlIt = "SELECT i.itinerary_id, i.name, gp.latitude, gp.longitude, gp.accuracy
                      FROM itineraries i
                      LEFT JOIN geo_points gp ON gp.entity_type='itinerary' AND gp.entity_id=i.itinerary_id
                      WHERE i.status='active" . ($townId ? " AND (i.town_id = ? OR i.destination_id = ?)" : "") . " LIMIT 300";
            if($townId){
                $stmtIt = $db->prepare($sqlIt);
                $stmtIt->bind_param('ii',$townId,$townId);
                $stmtIt->execute();
                $resIt = $stmtIt->get_result();
            } else { $resIt = $db->query($sqlIt); }
            if($resIt){ while($r = $resIt->fetch_assoc()){
                $items[] = [
                    'id'=>(int)$r['itinerary_id'],
                    'name'=>$r['name'],
                    'type'=>'itinerary',
                    'lat'=>$r['latitude'] ? (float)$r['latitude'] : null,
                    'lng'=>$r['longitude'] ? (float)$r['longitude'] : null,
                    'has_coords'=> (bool)$r['latitude'] && (bool)$r['longitude'],
                    'accuracy'=>$r['accuracy'] ?: null
                ]; } }
        }
    } catch (Throwable $ignore) { /* ignore itineraries errors */ }

    echo json_encode(['items'=>$items]);
} catch (Throwable $e){
    http_response_code(500);
    echo json_encode(['error'=>true,'message'=>$e->getMessage()]);
}
