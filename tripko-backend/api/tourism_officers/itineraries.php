<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../check_session.php';
require_once __DIR__ . '/../../config/Database.php';

checkTourismOfficerSession();

$method = $_SERVER['REQUEST_METHOD'];
// Support preflight
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $db = (new Database())->getConnection();
    if (!$db) throw new Exception('DB connection failed');

    $town_id = $_SESSION['town_id'] ?? null;
    if (!$town_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No town assigned to this tourism officer']);
        exit;
    }

    if ($method === 'GET') {
        // If id provided, return single itinerary (with days/items if schema supports)
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $db->prepare('SELECT * FROM itineraries WHERE itinerary_id = ? LIMIT 1');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Itinerary not found']);
                exit;
            }
            $ownerTown = $row['town_id'] ?? $row['destination_id'] ?? null;
            if ($ownerTown != $town_id) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

            $itinerary = [
                'id' => (int)$row['itinerary_id'],
                'title' => $row['name'],
                'name' => $row['name'], // keep original key for any legacy form binding
                'description' => $row['description'],
                'duration_days' => isset($row['duration_days']) ? (int)$row['duration_days'] : null,
                'min_price' => isset($row['min_price']) ? (int)$row['min_price'] : null,
                'max_price' => isset($row['max_price']) ? (int)$row['max_price'] : null,
                'category' => $row['category'] ?? null,
                'status' => $row['status'] ?? 'active',
                'featured_image' => $row['image_path'] ? '/tripko-system/uploads/' . rawurlencode(basename($row['image_path'])) : null,
                'days' => []
            ];

            // Attempt to load days & items if itinerary_days table exists
            if ($db->query("SHOW TABLES LIKE 'itinerary_days'")->num_rows > 0) {
                $daysSql = "SELECT day_id, day_number, title FROM itinerary_days WHERE itinerary_id = ? ORDER BY day_number";
                $ds = $db->prepare($daysSql); $ds->bind_param('i',$id); $ds->execute(); $dres=$ds->get_result();
                $dayMap = [];
                while($drow = $dres->fetch_assoc()){
                    $dayMap[$drow['day_id']] = [
                        'day_number' => (int)$drow['day_number'],
                        'title' => $drow['title'],
                        'activities' => [],
                        'times' => [],
                        'notes' => ''
                    ];
                }
                $ds->close();
                if(!empty($dayMap) && $db->query("SHOW TABLES LIKE 'itinerary_items'" )->num_rows > 0){
                    $idsIn = implode(',', array_map('intval', array_keys($dayMap)));
                    $itemsSql = "SELECT day_id, custom_name, start_time FROM itinerary_items WHERE day_id IN ($idsIn) ORDER BY day_id, sort_order, item_id";
                    if($ires = $db->query($itemsSql)){
                        while($irow = $ires->fetch_assoc()){
                            $did = (int)$irow['day_id'];
                            if(isset($dayMap[$did])){
                                $dayMap[$did]['activities'][] = $irow['custom_name'] ?: 'Activity';
                                $dayMap[$did]['times'][] = $irow['start_time'];
                            }
                        }
                        $ires->free();
                    }
                }
                // Re-index by ascending day_number
                usort($dayMap, function($a,$b){ return $a['day_number'] <=> $b['day_number']; });
                foreach($dayMap as $d){
                    $itinerary['days'][] = [
                        'day' => $d['day_number'],
                        'activities' => $d['activities'],
                        'times' => $d['times'],
                        'notes' => $d['notes']
                    ];
                }
            }

            echo json_encode(['success'=>true,'itinerary'=>$itinerary]);
            exit;
        }

        // List itineraries for this town including optional pricing/duration/category (only if columns exist)
        $colsRes = $db->query('SHOW COLUMNS FROM itineraries');
        $have = [];
        while($colsRes && $cRow = $colsRes->fetch_assoc()){ $have[$cRow['Field']] = true; }
        if($colsRes) $colsRes->free();

        $selectParts = ['itinerary_id','name','description'];
        foreach(['town_id','destination_id','environmental_fee','image_path','status','created_at','duration_days','min_price','max_price','category'] as $opt){
            if(isset($have[$opt])) $selectParts[] = $opt; // only include existing column
        }
        $selectList = implode(', ', $selectParts);

        $filterClause = '';
        $bindTypes = '';
        $bindVals = [];
        if(isset($have['destination_id'])){
            $filterClause = 'WHERE (town_id = ? OR destination_id = ?)';
            $bindTypes = 'ii';
            $bindVals = [$town_id, $town_id];
        } else if (isset($have['town_id'])) {
            $filterClause = 'WHERE town_id = ?';
            $bindTypes = 'i';
            $bindVals = [$town_id];
        } else {
            // Fallback: no town scoping columns present (unexpected) -> return empty
            echo json_encode(['success'=>true,'itineraries'=>[],'warning'=>'No town_id/destination_id columns found']);
            exit;
        }

        $q = "SELECT $selectList FROM itineraries $filterClause ORDER BY created_at DESC";
        $stmt = $db->prepare($q);
        if(!$stmt){
            echo json_encode(['success'=>false,'message'=>'Prepare failed','sql'=>$q,'error'=>$db->error]);
            exit;
        }
        $stmt->bind_param($bindTypes, ...$bindVals);
        $stmt->execute();
        $res = $stmt->get_result();
        $items = [];
        while($r = $res->fetch_assoc()){
            $items[] = [
                'id' => (int)$r['itinerary_id'],
                'title' => $r['name'],
                'description' => $r['description'],
                'duration_days' => isset($r['duration_days']) ? (int)$r['duration_days'] : null,
                'min_price' => isset($r['min_price']) ? (int)$r['min_price'] : null,
                'max_price' => isset($r['max_price']) ? (int)$r['max_price'] : null,
                'category' => $r['category'] ?? null,
                'town_id' => isset($r['town_id']) ? (int)$r['town_id'] : (isset($r['destination_id'])?(int)$r['destination_id']:null),
                'environmental_fee' => $r['environmental_fee'] ?? null,
                'featured_image' => isset($r['image_path']) && $r['image_path'] ? '/tripko-system/uploads/' . rawurlencode(basename($r['image_path'])) : null,
                'status' => $r['status'] ?? 'active',
                'created_at' => $r['created_at'] ?? null
            ];
        }
        $stmt->close();
        echo json_encode(['success'=>true,'itineraries'=>$items]);
        exit;
    }

    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['itinerary_id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'itinerary_id required']);
            exit;
        }

        // ensure ownership
        $chk = $db->prepare('SELECT town_id, destination_id FROM itineraries WHERE itinerary_id = ? LIMIT 1');
        $chk->bind_param('i', $id);
        $chk->execute();
        $r = $chk->get_result()->fetch_assoc();
        $ownerTown = $r['town_id'] ?? $r['destination_id'] ?? null;
        if ($ownerTown != $town_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        $del = $db->prepare('DELETE FROM itineraries WHERE itinerary_id = ?');
        $del->bind_param('i', $id);
        $ok = $del->execute();
        if ($ok) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'message' => 'Delete failed']);
        exit;
    }

    if ($method === 'POST' || $method === 'PUT') {
        if ($method === 'POST') {
            $postedTown = $_POST['town_id'] ?? $_POST['destination'] ?? null;
            if ($postedTown && intval($postedTown) !== intval($town_id)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: You can only create itineraries for your municipality.']);
                exit;
            }

            // Accept either 'name' or 'title' from the form
            $name = $_POST['name'] ?? $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $duration_days = isset($_POST['duration_days']) ? intval($_POST['duration_days']) : 1;
            $min_price = isset($_POST['min_price']) ? intval($_POST['min_price']) : 0;
            $max_price = isset($_POST['max_price']) ? intval($_POST['max_price']) : 0;
            $category = $_POST['category'] ?? null;

            // Prepare insert statement with only the stable columns
            $ins = $db->prepare('INSERT INTO itineraries (name, description, town_id, destination_id, duration_days, min_price, max_price, category, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "active", NOW())');
            if (!$ins) {
                echo json_encode(['success' => false, 'message' => $db->error]);
                exit;
            }

            $ins->bind_param('ssiiiiis', $name, $description, $town_id, $town_id, $duration_days, $min_price, $max_price, $category);
            $ok = $ins->execute();
            if ($ok) {
                $newId = $ins->insert_id;

                // handle featured_image upload (single file input name 'featured_image')
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../../uploads/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                    $fileExt = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid() . '_' . time() . '.' . $fileExt;
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetPath)) {
                        $u = $db->prepare('UPDATE itineraries SET image_path = ? WHERE itinerary_id = ?');
                        if ($u) {
                            $u->bind_param('si', $fileName, $newId);
                            $u->execute();
                        }
                    }
                }

                // Persist schedule (days & items) if tables exist and days posted
                if(!empty($_POST['days']) && is_array($_POST['days']) && $db->query("SHOW TABLES LIKE 'itinerary_days'")->num_rows>0){
                    $daysPosted = $_POST['days'];
                    foreach($daysPosted as $dayNum => $dData){
                        $dayNumber = (int)$dayNum;
                        $title = null; // form doesn't collect a separate day title
                        $dayStmt = $db->prepare('INSERT INTO itinerary_days (itinerary_id, day_number, title) VALUES (?,?,?)');
                        if($dayStmt){
                            $dayStmt->bind_param('iis',$newId,$dayNumber,$title);
                            if($dayStmt->execute()){
                                $dayId = $dayStmt->insert_id;
                                // Items
                                if($db->query("SHOW TABLES LIKE 'itinerary_items'" )->num_rows>0){
                                    $activities = $dData['activities'] ?? [];
                                    $times = $dData['times'] ?? [];
                                    foreach($activities as $idx=>$act){
                                        $custom = trim($act);
                                        if($custom==='') continue;
                                        $timeVal = $times[$idx] ?? null;
                                        $sort = $idx+1;
                                        $itemStmt = $db->prepare('INSERT INTO itinerary_items (day_id, spot_id, custom_name, start_time, sort_order) VALUES (?,?,?,?,?)');
                                        if($itemStmt){
                                            $null = null; $itemStmt->bind_param('iissi',$dayId,$null,$custom,$timeVal,$sort);
                                            $itemStmt->execute();
                                            $itemStmt->close();
                                        }
                                    }
                                }
                            }
                            $dayStmt->close();
                        }
                    }
                }

                echo json_encode(['success' => true, 'itinerary_id' => $newId]);
            } else {
                echo json_encode(['success' => false, 'message' => $db->error]);
            }
            exit;
        } else {
            parse_str(file_get_contents('php://input'), $putVars);
            $id = intval($_GET['id'] ?? $putVars['itinerary_id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id required']); exit; }

            $chk = $db->prepare('SELECT town_id, destination_id FROM itineraries WHERE itinerary_id = ? LIMIT 1');
            $chk->bind_param('i', $id);
            $chk->execute();
            $r = $chk->get_result()->fetch_assoc();
            $ownerTown = $r['town_id'] ?? $r['destination_id'] ?? null;
            if ($ownerTown != $town_id) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

            $name = $putVars['name'] ?? $putVars['title'] ?? '';
            $description = $putVars['description'] ?? '';
            $duration_days = $putVars['duration_days'] ?? 1;
            $min_price = $putVars['min_price'] ?? 0;
            $max_price = $putVars['max_price'] ?? 0;
            $category = $putVars['category'] ?? null;

            $upd = $db->prepare('UPDATE itineraries SET name = ?, description = ?, duration_days = ?, min_price = ?, max_price = ?, category = ? WHERE itinerary_id = ?');
            $upd->bind_param('ssiiisi', $name, $description, $duration_days, $min_price, $max_price, $category, $id);
            $ok = $upd->execute();

            if($ok && !empty($putVars['days']) && is_array($putVars['days']) && $db->query("SHOW TABLES LIKE 'itinerary_days'" )->num_rows>0){
                // Wipe existing days/items then re-create
                if($db->query("SHOW TABLES LIKE 'itinerary_items'" )->num_rows>0){
                    $db->query('DELETE it FROM itinerary_items it JOIN itinerary_days d ON it.day_id=d.day_id WHERE d.itinerary_id='.(int)$id);
                }
                $db->query('DELETE FROM itinerary_days WHERE itinerary_id='.(int)$id);
                foreach($putVars['days'] as $dayNum=>$dData){
                    $dayNumber=(int)$dayNum; $title=null;
                    $dayStmt = $db->prepare('INSERT INTO itinerary_days (itinerary_id, day_number, title) VALUES (?,?,?)');
                    if($dayStmt){
                        $dayStmt->bind_param('iis',$id,$dayNumber,$title);
                        if($dayStmt->execute()){
                            $dayId=$dayStmt->insert_id;
                            if($db->query("SHOW TABLES LIKE 'itinerary_items'" )->num_rows>0){
                                $acts = $dData['activities'] ?? [];
                                $times = $dData['times'] ?? [];
                                foreach($acts as $idx=>$act){ $custom=trim($act); if($custom==='') continue; $timeVal=$times[$idx]??null; $sort=$idx+1; $itemStmt=$db->prepare('INSERT INTO itinerary_items (day_id, spot_id, custom_name, start_time, sort_order) VALUES (?,?,?,?,?)'); if($itemStmt){ $null=null; $itemStmt->bind_param('iissi',$dayId,$null,$custom,$timeVal,$sort); $itemStmt->execute(); $itemStmt->close(); } }
                            }
                        }
                        $dayStmt->close();
                    }
                }
            }

            if ($ok) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'message'=>$db->error]);
            exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('tourism_officers/itineraries error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error', 'error' => $e->getMessage()]);
}

?>
