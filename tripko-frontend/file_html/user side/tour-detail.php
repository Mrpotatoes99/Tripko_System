<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/tripko-system/tripko-backend/check_session.php');
if (!isLoggedIn()) { header('Location: SignUp_LogIn_Form.php'); exit; }
if (isAdmin()) { header('Location: dashboard.php'); exit; }
// Acquire tour id or slug from query
$tourId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/i','', $_GET['slug']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<title>Tour Detail - TripKo</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
<link rel="stylesheet" href="/tripko-system/tripko-frontend/css/user-shared.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />
<style>
:root { --accent:#0f8b92; }
body.user-shell { background:#f6f9fa; }
.tour-hero { display:flex;flex-direction:column;gap:.9rem; padding:2rem 0 1.5rem; }
.tour-title { margin:0;font-size:1.5rem;font-weight:800;color:#0e2f35; }
.tour-meta { display:flex;flex-wrap:wrap;gap:.7rem;font-size:.7rem;font-weight:600;letter-spacing:.5px;color:#3e5d65; }
.tour-meta span { background:#e6f3f5;padding:.35rem .7rem;border-radius:30px; }
.layout { display:flex;flex-direction:column;gap:1.5rem; }
@media (min-width:1150px){ .layout { flex-direction:row; align-items:flex-start; } }
.layout-main { flex:1; display:flex;flex-direction:column;gap:1.5rem; }
.layout-side { flex:0 0 340px; max-width:340px; position:relative; }
.sticky-box { position:sticky; top:1.2rem; background:#fff; border:1px solid #dfe7e9; border-radius:20px; padding:1.2rem 1.1rem 1.3rem; display:flex;flex-direction:column;gap:1rem; box-shadow:var(--shadow-md); }
@media (max-width:900px){ .layout-side { display:none; } .mobile-booking-trigger { position:fixed; bottom:0; left:0; right:0; background:#fff; padding:.8rem 1rem; box-shadow:0 -4px 14px -6px rgba(0,0,0,.15); display:flex;justify-content:space-between;align-items:center; z-index:1100; }
.mobile-booking-trigger button { background:var(--accent);color:#fff;border:none;padding:.7rem 1.2rem;border-radius:14px;font-weight:700;font-size:.8rem; }
}
.gallery-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.6rem; }
.gallery-item { aspect-ratio:4/3; background:#e2ecee; border-radius:14px; background-size:cover; background-position:center; position:relative; overflow:hidden; }
.section-card { background:#fff; border:1px solid #dfe7e9; border-radius:22px; padding:1.2rem 1.3rem 1.3rem; box-shadow:var(--shadow-sm); }
.section-card h2 { margin:0 0 .75rem; font-size:1rem; font-weight:800; letter-spacing:.5px; color:#0d2d33; }
.section-card h3 { margin:1.1rem 0 .6rem; font-size:.85rem; font-weight:800; letter-spacing:.5px; color:#0d2d33; }
.price-inline { font-size:1.2rem; font-weight:800; color:#0d2d33; }
.price-inline small { font-size:.6rem; font-weight:600; color:#3b5e66; margin-left:.4rem; }
.divider { height:1px; background:linear-gradient(90deg,rgba(0,0,0,0),#d9e4e6,rgba(0,0,0,0)); margin:.9rem 0; }
.booking-group { display:flex;flex-direction:column;gap:.65rem; }
.booking-group label { font-size:.6rem;font-weight:700;letter-spacing:.5px;color:#244047; }
.booking-group input, .booking-group select { padding:.55rem .7rem; border:1px solid #cfd9db; border-radius:12px; font-size:.7rem; font-weight:600; background:#fff; }
.booking-total { font-size:.85rem;font-weight:800;color:#0d2d33; display:flex;justify-content:space-between; }
.badge-pill { background:#0f8b92; color:#fff; font-size:.55rem; font-weight:700; padding:.35rem .65rem; border-radius:30px; letter-spacing:.5px; }
.inline-note { font-size:.55rem; font-weight:600; color:#32545d; }
.map-wrapper { width:100%; height:420px; border-radius:20px; border:1px solid #dfe7e9; overflow:hidden; position:relative; }
#tourMap { width:100%; height:100%; }
.stop-list { list-style:none; margin:0; padding:0; display:flex;flex-direction:column; gap:.55rem; }
.stop-list li { font-size:.6rem;font-weight:600;color:#234650; display:flex; gap:.5rem; align-items:flex-start; }
.stop-index { background:#0f8b92; color:#fff; width:22px; height:22px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-size:.55rem; font-weight:700; flex-shrink:0; }
.review-summary { display:flex;align-items:center;gap:.6rem;font-size:.7rem;font-weight:600;color:#234650; }
.rating-stars { color:#f6b400; font-size:.75rem; }
.modal-sheet { display:none; position:fixed; inset:0; background:rgba(15,32,41,.55); backdrop-filter:blur(4px); z-index:2000; padding:2.5rem 1rem 3.5rem; overflow:auto; }
.modal-inner { background:#fff; max-width:520px; margin:0 auto; border-radius:26px; padding:1.4rem 1.3rem 2rem; display:flex; flex-direction:column; gap:1.1rem; box-shadow:0 14px 44px -10px rgba(10,40,50,.4); }
.modal-inner h2 { margin:0; font-size:1rem; font-weight:800; }
.close-modal-btn { position:absolute; top:.8rem; right:.8rem; background:#0f8b92; color:#fff; border:none; width:34px; height:34px; border-radius:14px; cursor:pointer; font-size:.85rem; }
.primary-btn { background:#0f8b92; color:#fff; border:none; padding:.75rem 1.1rem; border-radius:14px; font-weight:700; font-size:.8rem; cursor:pointer; box-shadow:0 4px 14px -4px rgba(15,139,146,.45); }
.primary-btn:hover { filter:brightness(1.05); }
.outline-btn { background:#fff; border:1px solid #0f8b92; color:#0f8b92; padding:.6rem .9rem; border-radius:14px; font-size:.7rem; font-weight:700; cursor:pointer; }
</style>
</head>
<body class="user-shell">
<?php include_once __DIR__ . '/../includes/navbar.php'; if(function_exists('renderNavbar')) renderNavbar(); ?>
<div class="user-container">
  <div class="tour-hero">
    <h1 class="tour-title" id="tourTitle">Loading Tour...</h1>
    <div class="tour-meta" id="tourMeta"></div>
    <div class="review-summary" id="reviewSummary"></div>
  </div>
  <div class="layout">
    <main class="layout-main">
      <div class="section-card" id="overviewSection">
        <h2>Overview</h2>
        <p id="tourSummary" style="font-size:.7rem;line-height:1.25rem;color:#27454f;font-weight:600;margin:0 0 1rem;"></p>
        <div id="tourDescription" style="font-size:.65rem;line-height:1.3rem;color:#2a4852;font-weight:500;"></div>
      </div>
      <div class="section-card" id="gallerySection">
        <h2>Gallery</h2>
        <div class="gallery-grid" id="galleryGrid"></div>
      </div>
      <div class="section-card" id="stopsSection">
        <h2>Itinerary & Stops</h2>
        <div class="map-wrapper" style="margin-bottom:1rem;" id="mapWrap">
          <div id="tourMap"></div>
          <div id="mapLoading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:600;letter-spacing:.5px;color:#0f2f35;background:linear-gradient(160deg,#ffffff,#fcfeff);">Loading map…</div>
        </div>
        <ul class="stop-list" id="stopList"></ul>
      </div>
      <div class="section-card" id="reviewsSection">
        <h2>Reviews</h2>
        <div id="reviewsPlaceholder" style="font-size:.6rem;color:#375960;font-weight:600;">Reviews integration coming soon.</div>
      </div>
      <div class="section-card" id="cancellationSection">
        <h2>Cancellation Policy</h2>
        <div id="cancellationPolicy" style="font-size:.6rem;color:#2b4b55;font-weight:600;line-height:1.2rem;"></div>
      </div>
    </main>
    <aside class="layout-side">
      <div class="sticky-box" id="bookingWidget" aria-label="Booking widget">
        <div style="display:flex;flex-direction:column;gap:.4rem;">
          <div class="price-inline"><span id="priceDisplay">₱0.00</span><small>per adult</small></div>
          <div class="inline-note" id="pricingTierNote"></div>
        </div>
        <div class="booking-group">
          <label for="dateInput">Select Date</label>
          <input type="date" id="dateInput" />
        </div>
        <div class="booking-group">
          <label for="travelersInput">Travelers</label>
          <input type="number" id="travelersInput" min="1" value="1" />
        </div>
        <div class="booking-total"><span>Total</span><span id="totalPrice">₱0.00</span></div>
        <div class="inline-note" id="cancellationDeadlineNote"></div>
        <button class="primary-btn" id="reserveBtn">Reserve Now</button>
        <div style="font-size:.55rem;font-weight:600;color:#375b63;display:flex;flex-direction:column;gap:.3rem;">
          <span id="reserveNowLaterNote"></span>
        </div>
      </div>
    </aside>
  </div>
</div>
<div class="mobile-booking-trigger" id="mobileTrigger" style="display:none;">
  <div style="display:flex;flex-direction:column;gap:.15rem;">
    <span style="font-size:.55rem;font-weight:600;color:#34525a;">From</span>
    <strong style="font-size:.85rem;font-weight:800;color:#0d2d33;" id="mobilePrice">₱0.00</strong>
  </div>
  <button id="openBookingMobile">Reserve</button>
</div>
<div class="modal-sheet" id="bookingModal" role="dialog" aria-modal="true" aria-labelledby="bookingModalTitle">
  <div class="modal-inner">
    <button class="close-modal-btn" id="closeBookingModal" aria-label="Close booking panel">&times;</button>
    <h2 id="bookingModalTitle" style="margin-bottom:.3rem;">Reserve Tour</h2>
    <div style="display:flex;flex-direction:column;gap:1rem;">
      <div class="booking-group">
        <label for="dateInputMobile">Select Date</label>
        <input type="date" id="dateInputMobile" />
      </div>
      <div class="booking-group">
        <label for="travelersInputMobile">Travelers</label>
        <input type="number" id="travelersInputMobile" min="1" value="1" />
      </div>
      <div class="booking-total"><span>Total</span><span id="totalPriceMobile">₱0.00</span></div>
      <div class="inline-note" id="pricingTierNoteMobile" style="min-height:1rem;"></div>
      <div class="inline-note" id="cancellationDeadlineNoteMobile" style="min-height:1rem;"></div>
      <button class="primary-btn" id="reserveBtnMobile">Reserve Now</button>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<script>
const TOUR_API = '/tripko-system/tripko-backend/api/tours/get_tour.php';
const tourId = <?php echo $tourId ? (int)$tourId : 'null'; ?>;
const tourSlug = <?php echo $slug ? ('"'. $slug .'"') : 'null'; ?>;
let tourData = null; let mapInstance = null; let mapLayer = null;

function fetchTour(initial=true){
  const params = new URLSearchParams();
  if(tourId) params.append('tour_id', tourId); else if(tourSlug) params.append('slug', tourSlug);
  const travelers = getTravelers(); if(travelers) params.append('group_size', travelers);
  const d = getSelectedDate(); if(d) params.append('date', d);
  fetch(TOUR_API + '?' + params.toString())
    .then(r=> r.json())
    .then(json=>{ if(!json.success) throw new Error(json.error||'Failed'); tourData = json; renderTour(initial); })
    .catch(err=> console.error('Tour load error', err));
}
function renderTour(initial){
  const t = tourData.tour;
  document.getElementById('tourTitle').textContent = t.title;
  document.getElementById('tourSummary').textContent = t.summary || '';
  document.getElementById('tourDescription').innerHTML = (t.description||'').replace(/</g,'&lt;');
  const meta = [];
  if(t.duration_hours) meta.push(`<span>${t.duration_hours} hrs</span>`);
  if(t.start_time && t.end_time) meta.push(`<span>${t.start_time} - ${t.end_time}</span>`);
  if(t.meeting_point) meta.push(`<span>Meet: ${t.meeting_point}</span>`);
  document.getElementById('tourMeta').innerHTML = meta.join('');
  // Rating summary
  const stars = buildStars(t.average_rating);
  document.getElementById('reviewSummary').innerHTML = `${stars} <span>${t.average_rating.toFixed(1)} (${t.review_count})</span>`;
  // Gallery
  const gallery = document.getElementById('galleryGrid');
  const cover = t.cover_image_url ? [{image_url:t.cover_image_url, alt_text:t.title+' cover'}] : [];
  const combined = [...cover, ...tourData.gallery];
  gallery.innerHTML = combined.map(g=> `<div class='gallery-item' style="background-image:url('${g.image_url}');" title="${(g.alt_text||'').replace(/"/g,'&quot;')}"></div>`).join('');
  // Cancellation
  document.getElementById('cancellationPolicy').textContent = t.cancellation_policy_text || 'No cancellation policy provided.';
  updateCancellationDeadline();
  // Pricing area
  updatePricingDisplays();
  // Stops + Map
  renderStops(); if(initial) initMap(); else refreshMap();
  handleMobileUI();
}
function buildStars(avg){
  const full = Math.floor(avg); const half = (avg - full) >= 0.25 && (avg - full) < 0.75; const nearFull = (avg - full) >= 0.75;
  let html = '<div class="rating-stars">';
  for(let i=1;i<=5;i++){
    if(i <= full) html += '<i class="fa-solid fa-star"></i>'; else if(i===full+1 && (half||nearFull)) html += half?'<i class="fa-solid fa-star-half-stroke"></i>':'<i class="fa-solid fa-star"></i>'; else html += '<i class="fa-regular fa-star"></i>';
  }
  html += '</div>'; return html;
}
function getTravelers(){ return parseInt(document.getElementById('travelersInput').value) || 1; }
function getSelectedDate(){ return document.getElementById('dateInput').value || null; }
function updatePricingDisplays(){
  const p = tourData.pricing; const t = tourData.tour; const pricePer = p? p.price_per_person : t.base_price_per_adult; const total = p? p.total_price : pricePer * getTravelers();
  document.getElementById('priceDisplay').textContent = formatCurrency(pricePer);
  document.getElementById('totalPrice').textContent = formatCurrency(total);
  document.getElementById('mobilePrice').textContent = formatCurrency(pricePer);
  const note = p && p.tier ? `Tier ${p.tier.min_group_size}-${p.tier.max_group_size||'+'} applied` : '';
  document.getElementById('pricingTierNote').textContent = note;
  document.getElementById('pricingTierNoteMobile').textContent = note;
  document.getElementById('reserveNowLaterNote').textContent = t.reserve_now_pay_later? 'Reserve now & pay later available.' : '';
}
function formatCurrency(v){ return '₱' + Number(v).toLocaleString('en-PH',{minimumFractionDigits:2, maximumFractionDigits:2}); }
function updateCancellationDeadline(){
  const t = tourData.tour; const date = getSelectedDate(); if(!date){ document.getElementById('cancellationDeadlineNote').textContent=''; document.getElementById('cancellationDeadlineNoteMobile').textContent=''; return; }
  if(!t.cancellation_deadline_hours){ return; }
  const dt = new Date(date+'T00:00:00');
  const deadline = new Date(dt.getTime() - t.cancellation_deadline_hours*3600*1000);
  const opts = { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'};
  const text = 'Free cancellation until ' + deadline.toLocaleString(undefined, opts);
  document.getElementById('cancellationDeadlineNote').textContent = text;
  document.getElementById('cancellationDeadlineNoteMobile').textContent = text;
}
function renderStops(){
  const list = document.getElementById('stopList');
  const stops = tourData.stops || []; if(!stops.length){ list.innerHTML = '<li style="color:#3a5b63;">No stops defined.</li>'; return; }
  list.innerHTML = stops.map(s=> `<li><span class='stop-index'>${s.position}</span><div style='display:flex;flex-direction:column;gap:.2rem;'><strong style='font-size:.6rem;'>${s.name}</strong><span style='font-size:.55rem;font-weight:500;color:#47636b;'>${s.description||''}</span></div></li>`).join('');
}
function initMap(){ if(mapInstance) return; if(typeof L==='undefined'){ setTimeout(initMap,200); return; }
  mapInstance = L.map('tourMap', { zoomControl:true, attributionControl:false });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19 }).addTo(mapInstance);
  mapLayer = L.layerGroup().addTo(mapInstance);
  document.getElementById('mapLoading').style.display='none';
  refreshMap();
}
function refreshMap(){ if(!mapInstance || !mapLayer) return; mapLayer.clearLayers(); const stops = tourData.stops || []; let first = null; stops.forEach(s=>{ if(s.lat && s.lng){ const mk = L.marker([s.lat,s.lng], { title:s.name }); mk.bindPopup(`<strong>${s.position}. ${s.name}</strong>`); mk.addTo(mapLayer); if(!first) first=[s.lat,s.lng]; } }); if(first) mapInstance.setView(first, 10); setTimeout(()=> mapInstance.invalidateSize(), 220); }

// Events
['travelersInput','dateInput'].forEach(id=> document.getElementById(id).addEventListener('change', ()=>{ fetchTour(false); updateCancellationDeadline(); }));

function setupMobileBooking(){
  const trigger = document.getElementById('mobileTrigger');
  if(window.innerWidth <= 900){ trigger.style.display='flex'; } else trigger.style.display='none';
}
window.addEventListener('resize', setupMobileBooking);
function handleMobileUI(){ setupMobileBooking(); }

// Mobile modal logic
const bookingModal = document.getElementById('bookingModal');
const openBookingMobile = document.getElementById('openBookingMobile');
const closeBookingModal = document.getElementById('closeBookingModal');
openBookingMobile.addEventListener('click', ()=>{ bookingModal.style.display='block'; document.getElementById('dateInputMobile').value = document.getElementById('dateInput').value; document.getElementById('travelersInputMobile').value = document.getElementById('travelersInput').value; });
closeBookingModal.addEventListener('click', ()=> bookingModal.style.display='none');
['dateInputMobile','travelersInputMobile'].forEach(id=> document.getElementById(id).addEventListener('change', ()=>{ document.getElementById('dateInput').value = document.getElementById('dateInputMobile').value; document.getElementById('travelersInput').value = document.getElementById('travelersInputMobile').value; fetchTour(false); }));

// Initialize date inputs min=today
(function initDateInputs(){ const today = new Date(); const iso = today.toISOString().split('T')[0]; document.getElementById('dateInput').setAttribute('min', iso); document.getElementById('dateInputMobile').setAttribute('min', iso); })();

fetchTour(true);
</script>
</body>
</html>