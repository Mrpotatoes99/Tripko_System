<?php
session_start();
// Basic guard: allow tourism officer (user_type_id=3) or admin (1) to view.
if (!isset($_SESSION['user_type_id'])) {
  header('Location: ../../login.php');
  exit; 
}
$role = (int)$_SESSION['user_type_id'];
if ($role !== 3 && $role !== 1) {
  http_response_code(403);
  echo 'Access denied';
  exit;
}
// Capture officer's town (if any) for client filtering context.
$officerTownId = isset($_SESSION['user_id']) ? null : null; // fallback
// If tourism officer, attempt to fetch town id (reuse logic from check_session.php if available)
if ($role === 3) {
  // Lightweight town lookup (no central helper yet)
  try {
  require_once '../../../tripko-backend/config/Database.php';
    $db = (new Database())->getConnection();
    if($stmt = $db->prepare('SELECT town_id FROM user WHERE user_id = ? AND user_type_id = 3 LIMIT 1')){
      $stmt->bind_param('i', $_SESSION['user_id']);
      if($stmt->execute()){
        $res = $stmt->get_result();
        if($row = $res->fetch_assoc()){
          if ($row['town_id']) { $officerTownId = (int)$row['town_id']; }
        }
      }
      $stmt->close();
    }
  } catch (Throwable $e) {
    // Silent fail; page will still function but API will enforce
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Set Locations</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link href="https://unpkg.com/maplibre-gl@3.6.1/dist/maplibre-gl.css" rel="stylesheet" />
  <style>
    body{margin:0;font-family:system-ui,Arial,sans-serif;background:#0f1115;color:#f5f5f7;}
    header{padding:.75rem 1rem;background:#161b22;border-bottom:1px solid #242b33;font-weight:600;}
    .layout{display:flex;min-height:calc(100vh - 54px);} 
    aside{width:340px;border-right:1px solid #242b33;overflow:auto;background:#11161c;padding:0.75rem;display:flex;flex-direction:column;gap:.75rem}
    h1{font-size:1.05rem;margin:0;}
    .item-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.5rem}
    .item{background:#151b22;border:1px solid #1f2730;padding:.55rem .65rem;border-radius:6px;cursor:pointer;font-size:.8rem;display:flex;justify-content:space-between;align-items:center;}
    .item.approx{outline:1px dashed #d97706;}
    .item.unmapped{outline:1px solid #dc2626;}
    .badge{font-size:.6rem;text-transform:uppercase;background:#1e2936;padding:.2rem .35rem;border-radius:4px;letter-spacing:.5px}
    .badge.approx{background:#92400e}
    .badge.unmapped{background:#991b1b}
    .controls{display:flex;gap:.5rem;margin-bottom:.35rem;flex-wrap:wrap}
    .controls select,.controls input{background:#0f1419;border:1px solid #2a323c;color:#fff;padding:.4rem .5rem;border-radius:4px;font-size:.75rem}
    button{background:#2563eb;border:none;color:#fff;padding:.5rem .75rem;border-radius:6px;cursor:pointer;font-size:.75rem}
    button.secondary{background:#1d2430}
    button:hover{background:#1d4fc1}
    main{flex:1;position:relative}
    #map{position:absolute;inset:0}
    .floating-panel{position:absolute;top:10px;left:10px;background:#11161c;padding:.75rem;border:1px solid #242b33;border-radius:8px;width:260px;font-size:.7rem;display:flex;flex-direction:column;gap:.5rem}
    .row{display:flex;flex-direction:column;gap:.25rem}
    label{font-size:.65rem;letter-spacing:.5px;text-transform:uppercase;color:#9ca3af}
    input[type=text]{background:#0f1419;border:1px solid #2a323c;padding:.45rem .5rem;color:#fff;border-radius:4px;font-size:.7rem}
    .status-line{font-size:.65rem;min-height:1em}
  </style>
</head>
<body>
  <header>Set Map Locations <?php if($role===3 && $officerTownId){ echo ' - Town #'.htmlspecialchars($officerTownId); } ?></header>
  <div class="layout">
    <aside>
      <div class="controls">
        <select id="filterType">
          <option value="all">All</option>
          <option value="tourist_spot">Spots</option>
          <option value="festival">Festivals</option>
          <option value="itinerary">Itineraries</option>
        </select>
        <input type="text" id="searchBox" placeholder="Search" />
      </div>
      <ul id="itemList" class="item-list"></ul>
    </aside>
    <main>
      <div id="map"></div>
      <div class="floating-panel">
        <div class="row">
          <label>Selected Item</label>
          <div id="selectedName">None</div>
        </div>
        <div class="row">
          <label>Latitude / Longitude</label>
          <input id="latInput" type="text" placeholder="Lat" />
          <input id="lngInput" type="text" placeholder="Lng" />
        </div>
        <div class="row">
          <button id="saveBtn" disabled>Save Location</button>
          <button id="locateBtn" class="secondary">Use My Location</button>
        </div>
        <div class="status-line" id="statusLine"></div>
      </div>
    </main>
  </div>
  <script src="https://unpkg.com/maplibre-gl@3.6.1/dist/maplibre-gl.js"></script>
  <script>window.TOURISM_ROLE=<?=$role?>;window.OFFICER_TOWN_ID=<?= $officerTownId? $officerTownId : 'null' ?>;</script>
  <script type="module" src="../../file_js/location_picker.js"></script>
</body>
</html>
