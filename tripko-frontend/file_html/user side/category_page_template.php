<?php
// Reusable Category Page Template replicating places-to-go.php design
// Expected predefined vars before including this file:
// $CATEGORY_KEY (e.g. 'Islands','Caves','Churches','Waterfalls','Festivals')
// $HERO_TITLE  (string)
// $HERO_SUBTITLE (string)
// $TAGS (array of strings) optional

if(!isset($CATEGORY_KEY)) { die('CATEGORY_KEY not set'); }
if(!isset($HERO_TITLE)) { $HERO_TITLE = $CATEGORY_KEY; }
if(!isset($HERO_SUBTITLE)) { $HERO_SUBTITLE = 'Explore amazing '.$CATEGORY_KEY.' in Pangasinan.'; }
if(!isset($TAGS) || !is_array($TAGS) || empty($TAGS)) { $TAGS = [$CATEGORY_KEY]; }

// Database connection (reuse backend config / correct port)
// Prefer using the shared Database class to avoid duplication of credentials & port.
// Fallback to direct mysqli if include fails.
mysqli_report(MYSQLI_REPORT_OFF);
$conn = null;
try {
  $dbConfigPath = $_SERVER['DOCUMENT_ROOT'] . '/tripko-system/tripko-backend/config/Database.php';
  if (file_exists($dbConfigPath)) {
    require_once $dbConfigPath;
    if (class_exists('Database')) {
      $db = new Database();
      $conn = $db->getConnection(); // Database.php already uses port 3307 and ensures DB exists
    }
  }
  if(!$conn){
    // Fallback manual connection with known settings (port 3307 as in Database.php)
    $conn = new mysqli('localhost','root','', 'tripko_db', 3307);
    if($conn->connect_error){ throw new Exception($conn->connect_error); }
    $conn->set_charset('utf8mb4');
  }
} catch (Throwable $e) {
  error_log('[Category Page] DB connection error: '.$e->getMessage());
  // Show a user-friendly message (avoid leaking details)
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Service Unavailable</title></head><body style="font-family:Arial;max-width:640px;margin:4rem auto;padding:1rem;color:#334;line-height:1.5;">';
  echo '<h1 style="font-size:1.4rem;margin:0 0 .8rem;">Temporary Issue</h1>';
  echo '<p>We\'re unable to retrieve the requested destinations right now. Please try again in a moment.</p>';
  echo '<p style="font-size:.75rem;color:#667;">Ref: CAT-TPL-DB</p>';
  echo '</body></html>';
  exit;
}

// Build query depending on category
if(strtolower($CATEGORY_KEY) !== 'festivals') {
    $query = "SELECT ts.spot_id, ts.name, ts.description, ts.category, ts.town_id, ts.contact_info, ts.image_path, ts.status, ts.created_at, ts.updated_at,
                     t.name AS town_name,
                     COALESCE(AVG(r.rating),0) AS avg_rating,
                     COALESCE(COUNT(r.review_id),0) AS review_count
              FROM tourist_spots ts
              LEFT JOIN towns t ON ts.town_id = t.town_id
              LEFT JOIN reviews r ON r.spot_id = ts.spot_id AND r.status='active'
              WHERE ts.status='active' AND ts.category = ?
              GROUP BY ts.spot_id, ts.name, ts.description, ts.category, ts.town_id, ts.contact_info, ts.image_path, ts.status, ts.created_at, ts.updated_at, t.name
              ORDER BY ts.name ASC";
    $stmt = $conn->prepare($query);
    if(!$stmt){ die('Prepare failed: '.$conn->error); }
    $stmt->bind_param('s', $CATEGORY_KEY);
    if(!$stmt->execute()) { die('Execute failed: '.$stmt->error); }
    $result = $stmt->get_result();
} else {
    $query = "SELECT f.festival_id AS spot_id, f.name, f.description, 'Festival' AS category, f.town_id, f.image_path, f.date, t.name AS town_name
              FROM festivals f
              LEFT JOIN towns t ON t.town_id = f.town_id
              ORDER BY f.date ASC";
    $result = $conn->query($query);
}
if(!$result){ die('Query failed: '.$conn->error); }
// Collect towns for filter
$towns = [];
if($result->num_rows > 0){
    // We need to buffer rows because we'll iterate twice (once to collect towns and again to render)
    $rows = [];
    while($r = $result->fetch_assoc()) { $rows[] = $r; if(!empty($r['town_name'])) $towns[$r['town_name']] = true; }
    // Recreate array iterator for rendering
    $result = new ArrayObject($rows);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($HERO_TITLE); ?> - TripKo Pangasinan</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',system-ui,sans-serif; }
    body { line-height:1.6; overflow-x:hidden; background:#f8fafc; color:#1e293b; }
    .hero_content { margin-top:0px; padding:2.0rem 2rem 2rem; text-align:center; background:linear-gradient(135deg, rgba(0,166,184,0.1), rgba(15,118,110,0.05)); position:relative; }
    .title-row { display:flex; align-items:center; justify-content:center; position:relative; margin-bottom:1rem; z-index:2; }
    .hero_title { color:#1e293b; font-size:clamp(1.7rem,4.7vw,2.7rem); font-weight:800; letter-spacing:-1px; margin-left: 2rem; }
    .back-button { position:absolute; left:2rem; top:50%; transform:translateY(-50%); display:inline-flex; align-items:center; gap:.5rem; padding:.75rem 1.5rem; background:rgba(255,255,255,0.9); color:#00a6b8; text-decoration:none; border-radius:50px; border:2px solid #00a6b8; font-weight:600; backdrop-filter:blur(10px); transition:.3s; }
    .back-button:hover { background:#00a6b8; color:#fff; transform:translateY(-2px); }
    .scroll-container { display:flex; overflow-x:auto; gap:20px; padding:20px; scroll-behavior:smooth; }
    .scroll-container::-webkit-scrollbar { height:8px; }
    .scroll-container::-webkit-scrollbar-thumb { background:rgba(0,166,184,.70); border-radius:20px; }
    .scroll-nav { position:absolute; top:50%; transform:translateY(-50%); background:rgba(0,166,184,.70); color:#fff; border:none; padding:15px; cursor:pointer; border-radius:50%; display:flex; align-items:center; justify-content:center; transition:.3s; z-index:100; }
    .scroll-nav:hover { background:rgba(0,166,184,.70); }
    .scroll-left { left:20px; } .scroll-right { right:20px; }
    .card { width:320px; height:450px; flex-shrink:0; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.08); border:1px solid rgba(0,0,0,.06); transition:.3s; cursor:pointer; position:relative; display:flex; flex-direction:column; }
    .card:hover { transform:translateY(-4px); box-shadow:0 12px 24px rgba(0,0,0,.12); border-color:rgba(0,166,184,0.2); }
    .image-container { position:relative; width:100%; height:250px; overflow:hidden; }
    .see-details-overlay { position:absolute; inset:0; background:linear-gradient(135deg, rgba(0,166,184,0.9), rgba(15,118,110,0.9)); display:flex; align-items:center; justify-content:center; opacity:0; transition:.3s; z-index:2; }
    .card:hover .see-details-overlay { opacity:1; }
    .see-details-btn { background:#fff; color:#00a6b8; border:none; padding:12px 24px; border-radius:25px; font-weight:600; font-size:14px; cursor:pointer; transition:.3s; text-transform:uppercase; letter-spacing:.5px; }
    .see-details-btn:hover { transform:scale(1.05); box-shadow:0 4px 15px rgba(255,255,255,0.3); }
    .card img { width:100%; height:250px; object-fit:cover; opacity:0; transition:opacity .3s ease-in; }
    .card img.loaded { opacity:1; }
    .card .content { padding:16px 20px 20px; background:#fff; flex:1; display:flex; flex-direction:column; gap:8px; }
    .card-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px; }
    .rating-badge { background:rgba(0,166,184,.70); color:#fff; padding:4px 8px; border-radius:12px; font-size:.85rem; font-weight:600; min-width:45px; text-align:center; }
    .rating-info { display:flex; align-items:center; gap:8px; margin-bottom:6px; }
    .stars { display:flex; gap:2px; }
    .star { width:12px; height:12px; background:#ffa500; clip-path:polygon(50% 0%,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%); }
    .star.empty { background:#e0e0e0; }
    .review-count { color:#666; font-size:.85rem; }
    .location-info { color:#666; font-size:.9rem; font-weight: 600; display:flex; align-items:center; gap:4px; }
    .card-tags { display:flex; flex-wrap:wrap; gap:6px; margin-top:auto; }
    .tag { background:rgba(0,166,184,0.1); color:#00a6b8; padding:3px 8px; border-radius:10px; font-size:.75rem; font-weight:500; }
    .spot-name { font-size:1.25rem; font-weight:700; color:#1a1a1a; margin:0 0 4px; line-height:1.3; }
    .no-results { text-align:center; padding:40px; color:#255D8A; font-size:1.1em; }
    .loading-overlay { position:fixed; inset:0; background:rgba(255,255,255,0.9); display:flex; justify-content:center; align-items:center; z-index:1000; }
    .loading-spinner { width:50px; height:50px; border:5px solid #f3f3f3; border-top:5px solid #255D8A; border-radius:50%; animation:spin 1s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }
    .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:10000; backdrop-filter:blur(5px); }
    .modal-content { position:relative; background:#fff; margin:2% auto; width:90%; max-width:900px; max-height:90vh; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
    .modal-header { position:relative; height:300px; overflow:hidden; }
    .modal-header img { width:100%; height:100%; object-fit:cover; }
    .modal-close { position:absolute; top:16px; right:16px; background:rgba(255,255,255,0.9); border:none; width:40px; height:40px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:18px; z-index:1; }
    .modal-body { padding:24px; max-height:calc(90vh - 300px); overflow-y:auto; }
    .modal-title { font-size:1.5rem; font-weight:700; color:#1a1a1a; margin-bottom:8px; }
    .modal-rating { display:flex; align-items:center; gap:12px; margin-bottom:16px; }
    .modal-rating .rating-badge { font-size:1rem; padding:6px 12px; }
    .modal-location { color:#666; font-size:1rem; font-weight: 600; margin-bottom:20px; display:flex; align-items:center; gap:6px; }
    .modal-description { font-size:1rem; line-height:1.6; color:#444; margin-bottom:24px; }
    .reviews-section { border-top:1px solid #e0e0e0; padding-top:24px; }
    .reviews-header-container { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .write-review-btn { background:#00a6b8; color:#fff; border:none; padding:10px 20px; border-radius:20px; cursor:pointer; font-weight:600; transition:.3s; }
    .write-review-btn:hover { background:#0095a6; transform:translateY(-1px); }
    .review-form { background:#f8f9fa; padding:20px; border-radius:12px; margin-bottom:20px; border:1px solid #e0e0e0; }
    .form-group { margin-bottom:15px; }
    .form-group label { display:block; margin-bottom:5px; font-weight:600; color:#333; }
    .form-group input, .form-group textarea { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px; }
    .rating-input { display:flex; gap:5px; margin:5px 0; }
    .rating-star { font-size:24px; color:#ddd; cursor:pointer; transition:color .2s; user-select:none; }
    .rating-star:hover, .rating-star.active { color:#ffa500; }
    .char-count { display:block; text-align:right; color:#666; font-size:12px; margin-top:5px; }
    .form-actions { display:flex; gap:10px; justify-content:flex-end; }
    .form-actions button { padding:10px 20px; border:none; border-radius:6px; cursor:pointer; font-weight:600; }
    .form-actions button[type="button"] { background:#f0f0f0; color:#666; }
    .form-actions button[type="submit"] { background:#00a6b8; color:#fff; }
    .review-summary { background:#fff; padding:20px; border-radius:12px; border:1px solid #e0e0e0; margin-bottom:20px; }
    .rating-overview { display:flex; gap:30px; align-items:center; }
    .overall-rating { text-align:center; }
    .rating-number { font-size:3rem; font-weight:700; color:#00a6b8; display:block; }
    .stars-large { display:flex; justify-content:center; gap:3px; margin:5px 0; }
    .stars-large .star { width:20px; height:20px; }
    .total-count { color:#666; font-size:.9rem; }
    .rating-breakdown { flex:1; }
    .breakdown-item { display:flex; align-items:center; margin-bottom:8px; gap:10px; }
    .breakdown-stars { width:80px; text-align:right; font-size:.9rem; color:#666; }
    .breakdown-bar { flex:1; height:8px; background:#f0f0f0; border-radius:4px; overflow:hidden; }
    .breakdown-fill { height:100%; background:#ffa500; transition:width .3s; }
    .breakdown-count { width:40px; text-align:right; font-size:.9rem; color:#666; }
    .reviews-list { min-height:200px; }
    .loading-reviews, .no-reviews { text-align:center; padding:40px; color:#666; }
    .load-more-btn { display:block; margin:20px auto 0; padding:12px 24px; background:transparent; border:2px solid #00a6b8; color:#00a6b8; border-radius:25px; cursor:pointer; font-weight:600; transition:.3s; }
    .load-more-btn:hover { background:#00a6b8; color:#fff; }
    /* --- Added structured review layout styles --- */
    .review-item { display:flex; flex-direction:column; gap:.6rem; padding:14px 16px 16px; background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 2px 4px rgba(0,0,0,0.03); }
    .review-header { display:flex; align-items:flex-start; gap:.9rem; }
    .reviewer-avatar { flex-shrink:0; width:44px; height:44px; border-radius:50%; background:#00a6b8; color:#fff; font-weight:700; font-size:.85rem; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 4px rgba(0,0,0,0.15); letter-spacing:.5px; }
    .review-meta { display:flex; flex-direction:column; gap:2px; min-width:0; }
    .reviewer-name { font-size:.8rem; font-weight:700; color:#1e293b; line-height:1.1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .review-date { font-size:.55rem; font-weight:600; letter-spacing:.4px; color:#64748b; }
    .review-rating { margin-left:auto; display:flex; gap:2px; align-items:center; }
    .review-rating .star { width:14px; height:14px; background:#ffa500; }
    .review-rating .star.empty { background:#e2e8f0; }
    .review-text { font-size:.65rem; line-height:1.2rem; color:#334155; font-weight:500; white-space:normal; word-break:break-word; }
    .reviews-list { display:flex; flex-direction:column; gap:1rem; }
    @media (min-width:600px){
      .review-item { flex-direction:column; }
      .review-text { font-size:.7rem; }
    }
    @media (min-width:900px){
      .review-item { padding:16px 20px 20px; }
      .reviewer-avatar { width:50px; height:50px; font-size:.95rem; }
      .reviewer-name { font-size:.85rem; }
      .review-text { font-size:.72rem; line-height:1.25rem; }
    }
    .filter-bar { display:flex; flex-wrap:wrap; gap:12px; justify-content:center; margin:0 0 1.75rem; padding:0 1rem; }
    .filter-group { display:flex; align-items:center; gap:8px; background:#ffffffaa; backdrop-filter:blur(6px); padding:10px 14px; border:1px solid #e2e8f0; border-radius:50px; }
    .filter-group label { font-size:.75rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#475569; }
    .filter-select { appearance:none; -webkit-appearance:none; padding:8px 14px; border:1px solid #cbd5e1; border-radius:30px; background:#fff url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 20 20\"><path fill=\"%236b7280\" d=\"M5.23 7.21a.75.75 0 011.06.02L10 10.17l3.71-2.94a.75.75 0 111.04 1.08l-4.24 3.36a.75.75 0 01-.94 0L5.21 8.31a.75.75 0 01.02-1.1z\"/></svg>') no-repeat right 10px center; background-size:16px; font-size:.8rem; font-weight:500; color:#334155; min-width:180px; }
    .filter-select:focus { outline:none; border-color:rgba(0,166,184,.70); box-shadow:0 0 0 3px rgba(0,166,184,0.25); }
    .clear-filter-btn { border:none; background:#f1f5f9; color:#475569; padding:8px 14px; border-radius:30px; font-size:.7rem; font-weight:600; letter-spacing:.5px; cursor:pointer; transition:.25s; }
    .clear-filter-btn:hover { background:#e2e8f0; }
    .pill-indicator { background:#00a6b8; color:#fff; padding:4px 10px; border-radius:20px; font-size:.65rem; font-weight:600; letter-spacing:.5px; display:none; align-items:center; gap:6px; }
    .pill-indicator button { background:transparent; border:none; color:#fff; font-size:14px; cursor:pointer; display:flex; align-items:center; }
    
    /* Get Directions Button Styles */
    .modal-actions { display: flex; justify-content: center; align-items: center; }
    .btn-get-directions { 
      display: inline-flex; 
      align-items: center; 
      gap: 10px; 
      padding: 14px 28px; 
      background: linear-gradient(135deg, #00a6b8 0%, #0095a6 100%);
      color: #fff; 
      border: none; 
      border-radius: 50px; 
      font-size: 1rem; 
      font-weight: 600; 
      cursor: pointer; 
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 166, 184, 0.3);
      text-decoration: none;
    }
    .btn-get-directions:hover { 
      background: linear-gradient(135deg, #0095a6 0%, #007f8f 100%);
      transform: translateY(-2px); 
      box-shadow: 0 6px 20px rgba(0, 166, 184, 0.4);
    }
    .btn-get-directions:active { 
      transform: translateY(0); 
    }
    .btn-get-directions i { 
      font-size: 1.25rem; 
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }
    #noCoordinatesMsg {
      padding: 12px;
      background: #f8f9fa;
      border-radius: 8px;
      border-left: 4px solid #ffc107;
    }
  </style>
</head>
<body>
  <?php include_once __DIR__ . '/../includes/navbar.php'; if(function_exists('renderNavbar')) renderNavbar(); ?>
  <div class="loading-overlay"><div class="loading-spinner"></div></div>
  <section class="hero_content">
    <div class="title-row">
      <h1 class="hero_title"><?php echo htmlspecialchars($HERO_TITLE); ?></h1>
      <a href="javascript:history.back()" class="back-button"><i class='bx bx-arrow-back'></i> Back</a>
    </div>
    <div class="filter-bar">
      <div class="filter-group">
        <label for="townFilter">Municipality</label>
        <select id="townFilter" class="filter-select">
          <option value="">All Municipalities</option>
          <?php foreach(array_keys($towns) as $town): ?>
            <option value="<?php echo htmlspecialchars($town); ?>"><?php echo htmlspecialchars($town); ?></option>
          <?php endforeach; ?>
        </select>
        <button class="clear-filter-btn" id="clearTownFilter" type="button" title="Clear municipality filter">Reset</button>
      </div>
      <div class="pill-indicator" id="activeFilterPill"> <span id="activeFilterLabel"></span> <button type="button" id="removeFilter" aria-label="Remove filter">✕</button></div>
    </div>
    <button class="scroll-nav scroll-left" aria-label="Scroll left"><i class='bx bx-chevron-left'></i></button>
    <button class="scroll-nav scroll-right" aria-label="Scroll right"><i class='bx bx-chevron-right'></i></button>
    <div class="scroll-container" id="spotScroller">
      <?php if($result instanceof ArrayObject && count($result) > 0): foreach($result as $row): ?>
        <?php $imagePath = $row['image_path'] ?? ''; ?>
  <div class="card" data-spot-id="<?php echo $row['spot_id']; ?>" data-town="<?php echo htmlspecialchars($row['town_name'] ?? ''); ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>" data-description="<?php echo htmlspecialchars(($row['description'] ?: 'A wonderful destination in Pangasinan.'), ENT_QUOTES); ?>" data-image="<?php echo (!$imagePath || $imagePath==='placeholder.jpg') ? '../../assets/images/placeholder.jpg' : '../../../uploads/'.htmlspecialchars($imagePath); ?>">
          <div class="image-container">
            <img class="spot-img" src="<?php echo (!$imagePath || $imagePath==='placeholder.jpg') ? '../../assets/images/placeholder.jpg' : '../../../uploads/'.htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" loading="lazy" onerror="this.src='../../assets/images/placeholder.jpg'">
            <div class="see-details-overlay">
              <button class="see-details-btn">See Details</button>
            </div>
          </div>
          <div class="content">
            <div class="card-header">
              <div class="spot-name"><?php echo htmlspecialchars($row['name']); ?></div>
              <?php if(!isset($row['review_count']) || $row['review_count']>0): ?>
                <div class="rating-badge"><?php echo isset($row['avg_rating']) ? number_format($row['avg_rating'],1) : '0.0'; ?></div>
              <?php endif; ?>
            </div>
            <div class="rating-info">
              <div class="stars">
                <?php $rating = isset($row['avg_rating']) ? round($row['avg_rating']) : 0; for($i=1;$i<=5;$i++){ echo '<div class="star'.($i<=$rating?'':' empty').'"></div>'; } ?>
              </div>
              <span class="review-count">
                <?php if(isset($row['review_count']) && $row['review_count']>0): ?>
                  (<?php echo number_format($row['review_count']); ?> review<?php echo $row['review_count']!=1?'s':''; ?>)
                <?php else: ?>(No reviews yet)<?php endif; ?>
              </span>
            </div>
            <div class="location-info"><i class='bx bx-map'></i><span><?php echo htmlspecialchars($row['town_name'] ?? ''); ?></span></div>
            <div class="card-tags">
              <?php foreach($TAGS as $tg): ?><span class="tag"><?php echo htmlspecialchars($tg); ?></span><?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; elseif($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
        <!-- fallback path if not re-wrapped; should not normally execute after buffering -->
      <?php endwhile; else: ?>
        <div class="no-results"><i class='bx bx-search-alt' style="font-size:2em; margin-bottom:10px;"></i><p>No records found.</p></div>
      <?php endif; ?>
    </div>
  </section>

  <div id="detailModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <img id="modalImage" src="" alt="">
        <button class="modal-close" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body">
        <h1 id="modalTitle" class="modal-title"></h1>
        <div class="modal-rating" id="modalRatingHeader">
          <div class="rating-badge" id="modalAvgBadge">0.0</div>
          <div class="stars" id="modalStars"></div>
          <span class="review-count" id="modalReviewCount">(0 reviews)</span>
        </div>
        <div id="modalLocation" class="modal-location"><i class='bx bx-map'></i><span></span></div>
        <div id="modalDescription" class="modal-description"></div>
        
        <!-- Get Directions Button -->
        <div class="modal-actions" style="margin: 20px 0; padding: 15px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
          <button class="btn-get-directions" id="btnGetDirections" onclick="openGoogleMapsDirections()" style="display:none;">
            <i class='bx bx-navigation'></i>
            <span>How to get there?</span>
          </button>
          <div id="noCoordinatesMsg" style="display:none; color:#666; font-size:0.9em; text-align:center;">
            <i class='bx bx-info-circle'></i> Coordinates not available for this location
          </div>
        </div>
        
        <div class="reviews-section">
          <div class="reviews-header-container">
            <h3 class="reviews-header">Reviews & Ratings</h3>
            <button class="write-review-btn" onclick="showReviewForm()">Write a Review</button>
          </div>
          <div id="reviewForm" class="review-form" style="display:none;">
            <form onsubmit="submitReview(event)">
              <div class="form-group"><label for="reviewerName">Your Name *</label><input type="text" id="reviewerName" name="reviewer_name" required maxlength="100"></div>
              <div class="form-group"><label for="reviewerEmail">Email (Optional)</label><input type="email" id="reviewerEmail" name="reviewer_email" maxlength="150"></div>
              <div class="form-group"><label>Rating *</label><div class="rating-input"><span class="rating-star" data-rating="1">★</span><span class="rating-star" data-rating="2">★</span><span class="rating-star" data-rating="3">★</span><span class="rating-star" data-rating="4">★</span><span class="rating-star" data-rating="5">★</span></div><input type="hidden" id="reviewRating" name="rating" required></div>
              <div class="form-group"><label for="reviewText">Your Review *</label><textarea id="reviewText" name="review_text" rows="4" required minlength="10" maxlength="1000" placeholder="Share your experience..."></textarea><small class="char-count">0/1000 characters</small></div>
              <div class="form-actions"><button type="button" onclick="hideReviewForm()">Cancel</button><button type="submit">Submit Review</button></div>
            </form>
          </div>
          <div id="reviewSummary" class="review-summary">
            <div class="rating-overview">
              <div class="overall-rating"><span id="averageRating" class="rating-number">0.0</span><div id="overallStars" class="stars-large"></div><div id="totalReviews" class="total-count">0 reviews</div></div>
              <div id="ratingBreakdown" class="rating-breakdown"></div>
            </div>
          </div>
          <div id="reviewsList" class="reviews-list"><div class="loading-reviews">Loading reviews...</div></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    let currentSpotId = null;
    let currentSpotCoordinates = null;
    
    function openModal(spotId, name, location, description, imageSrc){
      currentSpotId = spotId; const modal=document.getElementById('detailModal');
      document.getElementById('modalTitle').textContent = name;
      document.getElementById('modalLocation').querySelector('span').textContent = location;
      document.getElementById('modalDescription').textContent = description;
      const img=document.getElementById('modalImage'); img.src=imageSrc; img.alt=name;
      modal.style.display='block'; document.body.style.overflow='hidden';
      loadReviews(currentSpotId);
      loadSpotCoordinates(currentSpotId);
    }
    
    async function loadSpotCoordinates(spotId) {
      try {
        const API_BASE = '/tripko-system/tripko-backend/api';
        const response = await fetch(`${API_BASE}/spots/get_coordinates.php?spot_id=${spotId}`);
        const data = await response.json();
        
        const btn = document.getElementById('btnGetDirections');
        const noCoordMsg = document.getElementById('noCoordinatesMsg');
        
        if (data.success && data.latitude && data.longitude) {
          currentSpotCoordinates = {
            lat: parseFloat(data.latitude),
            lng: parseFloat(data.longitude)
          };
          btn.style.display = 'flex';
          noCoordMsg.style.display = 'none';
        } else {
          currentSpotCoordinates = null;
          btn.style.display = 'none';
          noCoordMsg.style.display = 'block';
        }
      } catch (error) {
        console.error('Error loading coordinates:', error);
        currentSpotCoordinates = null;
        document.getElementById('btnGetDirections').style.display = 'none';
        document.getElementById('noCoordinatesMsg').style.display = 'block';
      }
    }
    
    function openGoogleMapsDirections() {
      if (!currentSpotCoordinates) {
        alert('Location coordinates are not available for this spot.');
        return;
      }
      
      const { lat, lng } = currentSpotCoordinates;
      const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
      
      window.open(googleMapsUrl, '_blank');
    }
    function closeModal(){ const modal=document.getElementById('detailModal'); modal.style.display='none'; document.body.style.overflow='auto'; }
    window.onclick = e => { const modal=document.getElementById('detailModal'); if(e.target===modal) closeModal(); };
    function showReviewForm(){ const f=document.getElementById('reviewForm'); f.style.display='block'; f.scrollIntoView({behavior:'smooth'}); }
    function hideReviewForm(){ const f=document.getElementById('reviewForm'); f.style.display='none'; f.querySelector('form').reset(); document.getElementById('reviewRating').value=''; updateStarRating(0); updateCharCount(); }
    function updateStarRating(r){ document.querySelectorAll('.rating-star').forEach((s,i)=>{ s.classList.toggle('active', i<r); }); }
    function updateCharCount(){ const ta=document.getElementById('reviewText'); if(!ta) return; const c=ta.value.length; const el=document.querySelector('.char-count'); el.textContent=`${c}/1000 characters`; el.style.color=c>900?'#e74c3c':(c>800?'#f39c12':'#666'); }
    async function submitReview(ev){ ev.preventDefault(); if(!currentSpotId){ alert('No spot selected'); return; } const fd=new FormData(ev.target); const data={spot_id:currentSpotId, reviewer_name:fd.get('reviewer_name'), reviewer_email:fd.get('reviewer_email'), rating:parseInt(fd.get('rating')), review_text:fd.get('review_text')}; if(!data.reviewer_name||!data.rating||!data.review_text){ alert('Fill all required'); return;} try { const btn=ev.target.querySelector('button[type="submit"]'); btn.disabled=true; btn.textContent='Submitting...'; const API_BASE='/tripko-system/tripko-backend/api'; const resp=await fetch(`${API_BASE}/reviews/submit_review.php`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}); const raw=await resp.text(); if(!resp.ok) throw new Error('HTTP '+resp.status); const js=JSON.parse(raw); if(js.success){ alert('Review submitted!'); hideReviewForm(); loadReviews(currentSpotId);} else alert('Error: '+(js.message||'Unknown')); } catch(err){ console.error(err); alert('Submit failed'); } finally { const btn=ev.target.querySelector('button[type="submit"]'); btn.disabled=false; btn.textContent='Submit Review'; } }
    async function loadReviews(id){ try { const list=document.getElementById('reviewsList'); list.innerHTML='<div class="loading-reviews">Loading reviews...</div>'; const API_BASE='/tripko-system/tripko-backend/api'; const resp=await fetch(`${API_BASE}/reviews/get_reviews.php?spot_id=${id}&limit=5`); const raw=await resp.text(); if(!resp.ok) throw new Error(raw.substring(0,200)); let js; try{ js=JSON.parse(raw);}catch(e){ throw new Error('Invalid JSON'); } if(js.success&&js.data){ updateReviewSummary(js.data); displayReviews(js.data.reviews||[]); updateModalHeaderRating(js.data); updateOriginCardRating(id, js.data); } else { list.innerHTML='<div class="no-reviews">Unable to load reviews.</div>'; } } catch(e){ console.error(e); document.getElementById('reviewsList').innerHTML='<div class="no-reviews">Error loading reviews.</div>'; } }
    function updateModalHeaderRating(d){ const avg=d.average_rating||0; const total=d.total_reviews||0; document.getElementById('modalAvgBadge').textContent=avg.toFixed(1); document.getElementById('modalReviewCount').textContent=`(${total} review${total===1?'':'s'})`; const wrap=document.getElementById('modalStars'); wrap.innerHTML=''; const r=Math.round(avg); for(let i=1;i<=5;i++){ const s=document.createElement('div'); s.className='star'+(i<=r?'':' empty'); wrap.appendChild(s);} }
    function updateOriginCardRating(id,d){ const card=document.querySelector(`.card[data-spot-id='${id}']`); if(!card) return; const badge=card.querySelector('.rating-badge'); const starsWrap=card.querySelector('.stars'); const countSpan=card.querySelector('.review-count'); if(!badge||!starsWrap||!countSpan) return; const avg=d.average_rating||0; const total=d.total_reviews||0; badge.textContent=avg.toFixed(1); starsWrap.innerHTML=''; const r=Math.round(avg); for(let i=1;i<=5;i++){ const div=document.createElement('div'); div.className='star'+(i<=r?'':' empty'); starsWrap.appendChild(div);} countSpan.textContent= total?`(${total} review${total===1?'':'s'})`:'(No reviews yet)'; }
    function updateReviewSummary(d){ document.getElementById('averageRating').textContent=d.average_rating||'0.0'; document.getElementById('totalReviews').textContent=`${d.total_reviews} review${d.total_reviews!==1?'s':''}`; const overall=document.getElementById('overallStars'); overall.innerHTML=''; for(let i=1;i<=5;i++){ const st=document.createElement('div'); st.className='star'+(i<=Math.round(d.average_rating)?'':' empty'); overall.appendChild(st);} const breakdown=document.getElementById('ratingBreakdown'); breakdown.innerHTML=''; if(d.total_reviews>0){ for(let i=5;i>=1;i--){ const item=document.createElement('div'); item.className='breakdown-item'; const bd=d.rating_breakdown[i]; item.innerHTML=`<div class='breakdown-stars'>${i} star${i!==1?'s':''}</div><div class='breakdown-bar'><div class='breakdown-fill' style='width:${bd.percentage}%'></div></div><div class='breakdown-count'>${bd.count}</div>`; breakdown.appendChild(item);} } else breakdown.innerHTML='<div style="text-align:center;color:#666;padding:20px;">No reviews yet.</div>'; }
    function displayReviews(reviews){ const list=document.getElementById('reviewsList'); if(!reviews.length){ list.innerHTML='<div class="no-reviews">No reviews yet.</div>'; return;} let html=''; reviews.forEach(r=>{ const stars=Array.from({length:5},(_,i)=>`<div class=\"star${i<r.rating?'':' empty'}\"></div>`).join(''); html+=`<div class='review-item'><div class='review-header'><div class='reviewer-avatar'>${r.reviewer_initial}</div><div class='review-meta'><div class='reviewer-name'>${r.reviewer_name}</div><div class='review-date'>${r.relative_date}</div></div><div class='review-rating'>${stars}</div></div><div class='review-text'>${r.review_text}</div></div>`; }); list.innerHTML=html; }
    function initializeReviewForm(){ document.querySelectorAll('.rating-star').forEach((star,i)=>{ star.addEventListener('click',()=>{ document.getElementById('reviewRating').value=i+1; updateStarRating(i+1); }); star.addEventListener('mouseenter',()=>updateStarRating(i+1)); }); const ri=document.querySelector('.rating-input'); if(ri) ri.addEventListener('mouseleave',()=>{ const cur=document.getElementById('reviewRating').value||0; updateStarRating(parseInt(cur)); }); const ta=document.getElementById('reviewText'); if(ta){ ta.addEventListener('input',updateCharCount); updateCharCount(); } }
    document.addEventListener('DOMContentLoaded',()=>{ 
      initializeReviewForm(); 
      const loading=document.querySelector('.loading-overlay'); 
      let loaded=0; 
      const imgs=document.querySelectorAll('.card img'); 
      const total=imgs.length; 
      // If there are no cards/images, hide the loading overlay immediately
      if(total === 0 && loading){ loading.style.display='none'; }
      function done(img){ if(img) img.classList.add('loaded'); loaded++; if(loading && loaded>=total) loading.style.display='none'; }
      imgs.forEach(img=>{ if(img.complete) done(img); else { img.addEventListener('load',()=>done(img)); img.addEventListener('error',()=>done(img)); }});
      // Safety fallback: ensure loader doesn't hang due to unexpected conditions
      setTimeout(()=>{ if(loading && loading.style.display !== 'none'){ loading.style.display='none'; } }, 4000);
      // Attach click listeners to cards using data attributes (replaces inline onclick removed)
      document.querySelectorAll('.card').forEach(card=>{
        card.addEventListener('click', ()=>{
          const id = parseInt(card.getAttribute('data-spot-id'));
          const name = card.getAttribute('data-name')||'';
          const town = card.getAttribute('data-town')||'';
          const desc = card.getAttribute('data-description')||'';
          const img = card.getAttribute('data-image')||'';
          openModal(id, name, town, desc, img);
        });
      });
      const cards=document.querySelectorAll('.card'); let tStart=0,tEnd=0; cards.forEach(card=>{ card.addEventListener('click',()=>{ if(Math.abs(tEnd - tStart) <5){ cards.forEach(c=>{ if(c!==card) c.classList.remove('flipped'); }); card.classList.toggle('flipped'); }}); card.addEventListener('touchstart',e=>{ tStart=e.changedTouches[0].screenX; }); card.addEventListener('touchend',e=>{ tEnd=e.changedTouches[0].screenX; if(Math.abs(tEnd - tStart)>50) card.classList.remove('flipped'); }); });
      const sc=document.querySelector('.scroll-container'); const left=document.querySelector('.scroll-left'); const right=document.querySelector('.scroll-right'); const step=320; left.addEventListener('click',()=>sc.scrollBy({left:-step,behavior:'smooth'})); right.addEventListener('click',()=>sc.scrollBy({left:step,behavior:'smooth'})); function updateBtns(){ const {scrollLeft,scrollWidth,clientWidth}=sc; left.style.display= scrollLeft>0 ? 'flex':'none'; right.style.display= scrollLeft < (scrollWidth - clientWidth - 10) ? 'flex':'none'; } sc.addEventListener('scroll',updateBtns); window.addEventListener('resize',updateBtns); updateBtns(); document.addEventListener('keydown',e=>{ if(e.key==='ArrowLeft') sc.scrollBy({left:-step,behavior:'smooth'}); else if(e.key==='ArrowRight') sc.scrollBy({left:step,behavior:'smooth'}); }); window.addEventListener('error',e=>{ console.error(e); loading.style.display='none'; });
      const townFilter = document.getElementById('townFilter');
      const clearBtn = document.getElementById('clearTownFilter');
      const pill = document.getElementById('activeFilterPill');
      const pillLabel = document.getElementById('activeFilterLabel');
      const removeFilter = document.getElementById('removeFilter');
      const scroller = document.getElementById('spotScroller');
      function applyTownFilter(){
        const val = townFilter.value.trim();
        let visibleCount = 0;
        scroller.querySelectorAll('.card').forEach(card=>{
          const town = (card.getAttribute('data-town')||'').trim();
          const show = !val || town === val;
          card.style.display = show ? '' : 'none';
          if(show) visibleCount++;
        });
        if(val){ pillLabel.textContent = val; pill.style.display='inline-flex'; } else { pill.style.display='none'; }
        // If no visible cards show a temp message
        if(!visibleCount){
          if(!document.getElementById('emptyFilterMsg')){
            const msg=document.createElement('div');
            msg.id='emptyFilterMsg';
            msg.className='no-results';
            msg.innerHTML='<i class="bx bx-filter"></i><p>No results for selected municipality.</p>';
            scroller.appendChild(msg);
          }
        } else {
          const msg=document.getElementById('emptyFilterMsg'); if(msg) msg.remove();
        }
      }
      townFilter.addEventListener('change', applyTownFilter);
      clearBtn.addEventListener('click', ()=>{ townFilter.value=''; applyTownFilter(); });
      removeFilter.addEventListener('click', ()=>{ townFilter.value=''; applyTownFilter(); });
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>
