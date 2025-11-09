<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/Database.php';
// TODO: add auth/session include to enforce permissions

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if(!$data){
    http_response_code(400);
    echo json_encode(['error'=>true,'message'=>'Invalid JSON']);
    exit;
}

$entityType = $data['entity_type'] ?? null;
$entityId   = isset($data['entity_id']) ? (int)$data['entity_id'] : null;
$lat        = isset($data['latitude']) ? (float)$data['latitude'] : null;
$lng        = isset($data['longitude']) ? (float)$data['longitude'] : null;
$accuracy   = $data['accuracy'] ?? 'exact';

$validTypes = ['tourist_spot','festival','itinerary','terminal','town'];
if(!in_array($entityType, $validTypes, true)){
    http_response_code(422);
    echo json_encode(['error'=>true,'message'=>'Invalid entity_type']);
    exit;
}
if(!$entityId || $lat === null || $lng === null){
    http_response_code(422);
    echo json_encode(['error'=>true,'message'=>'Missing required fields']);
    exit;
}
if($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180){
    http_response_code(422);
    echo json_encode(['error'=>true,'message'=>'Invalid coordinate range']);
    exit;
}
if(!in_array($accuracy, ['exact','approx','geocoded'], true)){
    $accuracy = 'exact';
}

try {
    $db = (new Database())->getConnection();
    $sql = "INSERT INTO geo_points (entity_type, entity_id, latitude, longitude, accuracy)
            VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE latitude=VALUES(latitude), longitude=VALUES(longitude), accuracy=VALUES(accuracy)";
    if(!$stmt = $db->prepare($sql)){
        throw new Exception('Prepare failed: '.$db->error);
    }
    $stmt->bind_param('sidds', $entityType, $entityId, $lat, $lng, $accuracy);
    if(!$stmt->execute()){
        throw new Exception('Execute failed: '.$stmt->error);
    }
    echo json_encode(['success'=>true]);
} catch (Throwable $e){
    http_response_code(500);
    echo json_encode(['error'=>true,'message'=>$e->getMessage()]);
}
