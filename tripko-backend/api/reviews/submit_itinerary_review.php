<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(204); exit; }
if(session_status()===PHP_SESSION_NONE){ session_start(); }
require_once '../../config/db.php';
$response=['success'=>false,'message'=>''];
try{
  if($_SERVER['REQUEST_METHOD']!=='POST') throw new Exception('Only POST allowed');
  if(!isset($conn) || $conn->connect_error) throw new Exception('DB connection error');
  $raw=file_get_contents('php://input'); $input=json_decode($raw,true); if(!is_array($input)) throw new Exception('Invalid JSON');
  $required=['itinerary_id','reviewer_name','rating','review_text'];
  foreach($required as $f){ if(!isset($input[$f]) || trim((string)$input[$f])==='') throw new Exception("Field '$f' is required"); }
  $itinerary_id=(int)$input['itinerary_id'];
  $reviewer_name=trim($input['reviewer_name']);
  $reviewer_email=isset($input['reviewer_email']) && trim($input['reviewer_email'])!==''? trim($input['reviewer_email']):null;
  $rating=(int)$input['rating'];
  $review_text=trim($input['review_text']);
  $user_id= isset($_SESSION['user_id'])? (int)$_SESSION['user_id']: null;
  if($itinerary_id<=0) throw new Exception('Invalid itinerary ID');
  if($rating<1||$rating>5) throw new Exception('Rating must be 1-5');
  if(strlen($reviewer_name)<2) throw new Exception('Name too short');
  if(strlen($reviewer_name)>100) throw new Exception('Name too long');
  if($reviewer_email && !filter_var($reviewer_email,FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email');
  if(strlen($review_text)<10) throw new Exception('Review must be at least 10 chars');
  if(strlen($review_text)>1000) throw new Exception('Review must be < 1000 chars');
  // Ensure modern polymorphic schema exists
  $cols=[]; if($cRes=$conn->query("SHOW COLUMNS FROM reviews")){ while($c=$cRes->fetch_assoc()){ $cols[]=$c['Field']; } }
  $modern = in_array('entity_type',$cols,true) && in_array('entity_id',$cols,true);
  if(!$modern) throw new Exception('Itinerary reviews not supported yet (schema upgrade needed)');
  // Check itinerary exists
  $itStmt=$conn->prepare("SELECT itinerary_id FROM itineraries WHERE itinerary_id=? LIMIT 1");
  if(!$itStmt) throw new Exception('Prepare failed (itinerary check): '.$conn->error);
  $itStmt->bind_param('i',$itinerary_id); $itStmt->execute(); $itRes=$itStmt->get_result(); $itStmt->close();
  if(!$itRes->num_rows) throw new Exception('Itinerary not found');
  // Duplicate prevention (24h) by same user OR same email/name pair
  if($user_id){
    $dup=$conn->prepare("SELECT review_id FROM reviews WHERE entity_type='itinerary' AND entity_id=? AND user_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 24 HOUR) LIMIT 1");
    if(!$dup) throw new Exception('Prepare failed (dup user): '.$conn->error);
    $dup->bind_param('ii',$itinerary_id,$user_id); $dup->execute(); $dupRes=$dup->get_result(); $dup->close();
    if($dupRes->num_rows) throw new Exception('You already reviewed this itinerary recently. Try later.');
  } elseif($reviewer_email){
    $dup=$conn->prepare("SELECT review_id FROM reviews WHERE entity_type='itinerary' AND entity_id=? AND reviewer_email=? AND created_at>DATE_SUB(NOW(),INTERVAL 24 HOUR) LIMIT 1");
    if(!$dup) throw new Exception('Prepare failed (dup email): '.$conn->error);
    $dup->bind_param('is',$itinerary_id,$reviewer_email); $dup->execute(); $dupRes=$dup->get_result(); $dup->close();
    if($dupRes->num_rows) throw new Exception('You already reviewed this itinerary recently. Try later.');
  }
  // Insert
  $sql="INSERT INTO reviews (entity_type, entity_id, spot_id, user_id, reviewer_name, reviewer_email, rating, review_text, status, review_date, created_at) VALUES ('itinerary', ?, NULL, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";
  $stmt=$conn->prepare($sql); if(!$stmt) throw new Exception('Prepare failed (insert): '.$conn->error);
  $stmt->bind_param('iissis',$itinerary_id,$user_id,$reviewer_name,$reviewer_email,$rating,$review_text);
  if(!$stmt->execute()) throw new Exception('Insert failed: '.$stmt->error);
  $newId=$conn->insert_id; $stmt->close();
  $response['success']=true; $response['message']='Review submitted'; $response['review_id']=(int)$newId; $response['user_attached']=(bool)$user_id;
} catch(Exception $e){ http_response_code(400); $response['message']=$e->getMessage(); }
echo json_encode($response);
