<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
require_once '../../config/db.php'; // mysqli $conn
$response = ['success'=>false,'data'=>[],'message'=>''];
try{
  if(!isset($conn) || $conn->connect_error) throw new Exception('Database connection error');
  $itinerary_id = isset($_GET['itinerary_id']) ? (int)$_GET['itinerary_id'] : 0; if($itinerary_id<=0) throw new Exception('Invalid itinerary ID');
  // Verify itinerary exists (correct primary key & column name)
  $it_sql = "SELECT name AS title FROM itineraries WHERE itinerary_id = ? LIMIT 1";
  $it_stmt=$conn->prepare($it_sql);
  if(!$it_stmt) throw new Exception('Prepare failed (itinerary): '.$conn->error);
  $it_stmt->bind_param('i',$itinerary_id);
  $it_stmt->execute();
  $it_res=$it_stmt->get_result();
  $itinerary=$it_res->fetch_assoc();
  $it_stmt->close();
  if(!$itinerary) throw new Exception('Itinerary not found');
  // Detect reviews table + columns
  $hasReviews=false; $cols=[]; if($chk=$conn->query("SHOW TABLES LIKE 'reviews'")){ if($chk->num_rows>0){ $hasReviews=true; } }
  if(!$hasReviews) throw new Exception('Reviews table not found');
  if($cRes=$conn->query("SHOW COLUMNS FROM reviews")){ while($cRow=$cRes->fetch_assoc()){ $cols[]=$cRow['Field']; } }
  $modern = in_array('entity_type',$cols,true) && in_array('entity_id',$cols,true);
  if(!$modern){
    // Legacy schema only supports spot reviews; return empty but succeed for forward compatibility
    $response['success']=true; $response['data']=[
      'itinerary_title'=>$itinerary['title'],
      'total_reviews'=>0,'average_rating'=>0,'rating_breakdown'=>[5=>['count'=>0,'percentage'=>0],4=>['count'=>0,'percentage'=>0],3=>['count'=>0,'percentage'=>0],2=>['count'=>0,'percentage'=>0],1=>['count'=>0,'percentage'=>0]],'reviews'=>[], 'pagination'=>['current_page'=>1,'total_pages'=>1,'has_more'=>false], 'note'=>'Itinerary reviews not yet supported on legacy reviews schema.'
    ]; echo json_encode($response); return; }
  // Pagination params
  $page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1; $limit = isset($_GET['limit']) ? min(50,max(5,(int)$_GET['limit'])) : 10; $offset = ($page-1)*$limit;
  // Stats
  $stats_sql = "SELECT COUNT(*) total_reviews, AVG(rating) average_rating, SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) rating_5, SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) rating_4, SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) rating_3, SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) rating_2, SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) rating_1 FROM reviews WHERE entity_type='itinerary' AND entity_id=? AND status='active'"; $st=$conn->prepare($stats_sql); if(!$st) throw new Exception('Prepare failed (stats): '.$conn->error); $st->bind_param('i',$itinerary_id); $st->execute(); $stats=$st->get_result()->fetch_assoc(); $st->close();
  // Reviews
  $reviews_sql = "SELECT review_id, reviewer_name, rating, review_text, review_date, helpful_count FROM reviews WHERE entity_type='itinerary' AND entity_id=? AND status='active' ORDER BY review_date DESC LIMIT ? OFFSET ?"; $rv=$conn->prepare($reviews_sql); if(!$rv) throw new Exception('Prepare failed (reviews): '.$conn->error); $rv->bind_param('iii',$itinerary_id,$limit,$offset); $rv->execute(); $rv_res=$rv->get_result();
  $formatted=[]; $now=time(); while($row=$rv_res->fetch_assoc()){ $ts=strtotime($row['review_date']); $formatted_date=date('F d, Y',$ts); $relative=$formatted_date; if($ts>=strtotime('-7 days',$now)) $relative='This week'; else if($ts>=strtotime('-1 month',$now)) $relative='This month'; $formatted[]=[ 'id'=>(int)$row['review_id'], 'reviewer_name'=>htmlspecialchars($row['reviewer_name']??'Anonymous'), 'reviewer_initial'=>strtoupper(substr($row['reviewer_name']??'A',0,1)), 'rating'=>(int)$row['rating'], 'review_text'=>htmlspecialchars($row['review_text']), 'date'=>$formatted_date, 'relative_date'=>$relative, 'helpful_count'=>(int)$row['helpful_count'] ]; }
  $rv->close();
  $total=(int)($stats['total_reviews']??0); $avg=$stats['average_rating']!==null? round((float)$stats['average_rating'],1):0.0; $total_for_pct=max(1,$total); $breakdown=[]; for($i=5;$i>=1;$i--){ $cnt=(int)($stats['rating_'.$i]??0); $breakdown[$i]=['count'=>$cnt,'percentage'=>$total? round(($cnt/$total_for_pct)*100):0]; }
  $response['success']=true; $response['data']=[ 'itinerary_title'=>$itinerary['title'], 'total_reviews'=>$total, 'average_rating'=>$avg, 'rating_breakdown'=>$breakdown, 'reviews'=>$formatted, 'pagination'=>['current_page'=>$page,'total_pages'=>$total? (int)ceil($total/$limit):1,'has_more'=>($offset+$limit)<$total] ];
} catch(Exception $e){ http_response_code(400); $response['message']=$e->getMessage(); }
echo json_encode($response);
