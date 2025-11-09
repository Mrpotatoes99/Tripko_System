<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/Database.php';

// NOTE: This version uses geo_points for coordinates. Fallbacks to NONE yet for towns (no centroid columns).

try {
    $db = (new Database())->getConnection();
    $features = [];

    // Tourist Spots
    $sqlSpots = "SELECT ts.spot_id, ts.name, ts.description, ts.contact_info, ts.image_path, t.name AS town_name,
                        gp.latitude, gp.longitude, gp.accuracy
                 FROM tourist_spots ts
                 JOIN towns t ON ts.town_id = t.town_id
                 LEFT JOIN geo_points gp ON gp.entity_type='tourist_spot' AND gp.entity_id = ts.spot_id
                 WHERE ts.status='active' LIMIT 500";
    if($res = $db->query($sqlSpots)){
        while($row = $res->fetch_assoc()){
            if(!$row['latitude'] || !$row['longitude']) continue; // skip unmapped
            $features[] = [
                'type'=>'Feature',
                'geometry'=>['type'=>'Point','coordinates'=>[(float)$row['longitude'], (float)$row['latitude']]],
                'properties'=>[
                    'id'=>(int)$row['spot_id'],
                    'category'=>'spot',
                    'name'=>$row['name'],
                    'description'=>$row['description'],
                    'contact'=>$row['contact_info'],
                    'municipality'=>$row['town_name'],
                    'image'=>$row['image_path'] ? '/uploads/' . basename($row['image_path']) : null,
                    'accuracy'=>$row['accuracy'] ?: 'unknown'
                ]
            ];
        }
    }

    // Festivals
    $sqlFest = "SELECT f.festival_id, f.name, f.description, f.image_path, t.name AS town_name,
                       gp.latitude, gp.longitude, gp.accuracy
                FROM festivals f
                JOIN towns t ON f.town_id = t.town_id
                LEFT JOIN geo_points gp ON gp.entity_type='festival' AND gp.entity_id = f.festival_id
                WHERE f.status='active' LIMIT 300";
    if($res = $db->query($sqlFest)){
        while($row = $res->fetch_assoc()){
            if(!$row['latitude'] || !$row['longitude']) continue;
            $features[] = [
                'type'=>'Feature',
                'geometry'=>['type'=>'Point','coordinates'=>[(float)$row['longitude'], (float)$row['latitude']]],
                'properties'=>[
                    'id'=>(int)$row['festival_id'] + 100000,
                    'category'=>'festival',
                    'name'=>$row['name'],
                    'description'=>$row['description'],
                    'municipality'=>$row['town_name'],
                    'image'=>$row['image_path'] ? '/uploads/' . basename($row['image_path']) : null,
                    'accuracy'=>$row['accuracy'] ?: 'unknown'
                ]
            ];
        }
    }

    // Terminals (using terminal_locations table)
    $sqlTerm = "SELECT tl.terminal_id, tl.location_name, tl.address, tl.latitude, tl.longitude, tl.status
                FROM terminal_locations tl
                WHERE tl.status='active' LIMIT 300";
    if($res = $db->query($sqlTerm)){
        while($row = $res->fetch_assoc()){
            if(!$row['latitude'] || !$row['longitude']) continue;
            $features[] = [
                'type'=>'Feature',
                'geometry'=>['type'=>'Point','coordinates'=>[(float)$row['longitude'], (float)$row['latitude']]],
                'properties'=>[
                    'id'=>(int)$row['terminal_id'] + 200000,
                    'category'=>'terminal',
                    'name'=>$row['location_name'],
                    'description'=>$row['address'],
                    'terminal_type'=>null,
                    'accuracy'=>'exact'
                ]
            ];
        }
    }

    // Itineraries (optional) if geo_points defined
    $sqlIt = "SELECT i.itinerary_id, i.name, i.description, i.image_path, t.name AS town_name, gp.latitude, gp.longitude, gp.accuracy
              FROM itineraries i
              LEFT JOIN towns t ON i.town_id = t.town_id
              LEFT JOIN geo_points gp ON gp.entity_type='itinerary' AND gp.entity_id = i.itinerary_id
              WHERE i.status='active' LIMIT 300";
    if($res = $db->query($sqlIt)){
        while($row = $res->fetch_assoc()){
            if(!$row['latitude'] || !$row['longitude']) continue;
            $features[] = [
                'type'=>'Feature',
                'geometry'=>['type'=>'Point','coordinates'=>[(float)$row['longitude'], (float)$row['latitude']]],
                'properties'=>[
                    'id'=>(int)$row['itinerary_id'] + 400000,
                    'category'=>'itinerary',
                    'name'=>$row['name'],
                    'description'=>$row['description'],
                    'municipality'=>$row['town_name'],
                    'image'=>$row['image_path'] ? '/uploads/' . basename($row['image_path']) : null,
                    'accuracy'=>$row['accuracy'] ?: 'unknown'
                ]
            ];
        }
    }

    // Town markers only if geo_points defined for them
    $sqlTown = "SELECT t.town_id, t.name, gp.latitude, gp.longitude, gp.accuracy
                FROM towns t
                LEFT JOIN geo_points gp ON gp.entity_type='town' AND gp.entity_id = t.town_id
                WHERE t.status='active' LIMIT 100";
    if($res = $db->query($sqlTown)){
        while($row = $res->fetch_assoc()){
            if(!$row['latitude'] || !$row['longitude']) continue;
            $features[] = [
                'type'=>'Feature',
                'geometry'=>['type'=>'Point','coordinates'=>[(float)$row['longitude'], (float)$row['latitude']]],
                'properties'=>[
                    'id'=>(int)$row['town_id'] + 300000,
                    'category'=>'town',
                    'name'=>$row['name'],
                    'accuracy'=>$row['accuracy'] ?: 'approx'
                ]
            ];
        }
    }

    echo json_encode(['updated'=>gmdate('c'),'features'=>$features]);
} catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['error'=>true,'message'=>$e->getMessage()]);
}
