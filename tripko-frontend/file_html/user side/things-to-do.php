<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/tripko-system/tripko-backend/check_session.php');
if (!isLoggedIn()) { header('Location: SignUp_LogIn_Form.php'); exit; }
if (isAdmin()) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<title>Things To Do - TripKo Pangasinan</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="/tripko-system/tripko-frontend/css/user-shared.css" />
<link rel="stylesheet" href="/tripko-system/tripko-frontend/css/booking-page.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
<style>
/* Overview modal hero banner styles */
.detail-modal-header {
  position: relative;
}
.detail-modal-header img {
  width: 100%;
  border-radius: 12px 12px 0 0;
  object-fit: cover;
  height: 280px;
  display: block;
}
.detail-modal-header h2 {
  position: absolute;
  bottom: 20px;
  left: 25px;
  color: white;
  font-weight: 700;
  text-shadow: 0 3px 8px rgba(0,0,0,0.6);
  background: rgba(0,0,0,0.25);
  padding: .5rem 1.2rem;
  border-radius: 8px;
}
.hero-title {
  letter-spacing: 1px;
  text-shadow: 0 2px 6px rgba(0,0,0,0.3);
}
.hero-block {
  padding-top: 2.2rem;
  padding-bottom: 2.2rem;
}
.hero-title {
  margin-top: 2.2rem;
  margin-bottom: 0.8rem;
}
.hero-sub {
  margin-bottom: 1.5rem;
}
.hero-block {
  position: relative;
  background: linear-gradient(to bottom, rgba(0,80,80,0.8), rgba(0,50,70,0.95)),
              /* Fallback: pangasinan-bg.jpg missing, use hundred-islands-park.jpg instead */
              url('/tripko-system/tripko-frontend/images/pangasinan-bg.jpg'),
              url('/tripko-system/tripko-frontend/images/hundred-islands-park.jpg');
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;
  min-height: 340px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  z-index: 1;
}
.hero-block::before {
  content: '';
  position: absolute;
  inset: 0;
  background: inherit;
  filter: blur(8px) brightness(0.7);
  opacity: 0.45;
  z-index: 0;
}
.hero-block .hero-inner {
  position: relative;
  z-index: 2;
}
/* Layout split */
.things-layout {display:flex;flex-direction:column;gap:1.25rem;margin:2rem auto;}
@media (min-width:1100px){.things-layout{flex-direction:row;align-items:stretch;}
  .things-sidebar{flex:0 0 390px;max-width:390px;overflow:auto;}
  .things-mapwrap{flex:1;min-height:720px;}}
.things-sidebar{display:flex;flex-direction:column;gap:1rem;}
#map {width:100%;height:460px;border-radius:18px;box-shadow:var(--shadow-md);}
@media (min-width:1100px){#map{height:100%;min-height:720px;}}
.filter-bar{display:flex;flex-wrap:wrap;gap:.6rem;}
.filter-bar input, .filter-bar select{padding:.65rem .9rem;border:2px solid #e2ecee;border-radius:12px;font-size:.8rem;font-weight:600;background:#fff;}
.activity-list, .itinerary-panel{background:linear-gradient(160deg,#ffffff,rgba(255,255,255,.85));border:var(--border);border-radius:20px;padding:1rem 1rem 1.15rem;display:flex;flex-direction:column;gap:.8rem;box-shadow:var(--shadow-sm);}
.activity-card{display:flex;gap:.75rem;align-items:flex-start;padding:.55rem .55rem .6rem;border:1px solid #e6ecee;border-radius:14px;background:#fff;cursor:pointer;transition:.18s box-shadow, .18s border-color;}
.activity-card:hover{box-shadow:0 4px 14px -4px rgba(2,42,54,.15);border-color:#c2d8dc;}
.activity-thumb{width:70px;height:70px;border-radius:12px;background-size:cover;background-position:center;flex-shrink:0;}
.activity-body h4{margin:0;font-size:.8rem;font-weight:700;color:#0f2f35;}
.activity-meta{font-size:.6rem;font-weight:600;letter-spacing:.6px;color:#446068;display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.15rem;}
.badge-cat{display:inline-block;padding:.25rem .55rem;font-size:.55rem;font-weight:700;border-radius:30px;letter-spacing:.5px;background:#0f2f35;color:#fff;}
.itinerary-card-mini{padding:.7rem .75rem .8rem;border:1px solid #e0e9eb;border-radius:14px;background:#fff;display:flex;flex-direction:column;gap:.35rem;cursor:pointer;}
.itinerary-card-mini:hover{border-color:#bfd1d4;}
.itinerary-card-mini h5{margin:0;font-size:.75rem;font-weight:700;color:#0f2f35;}
.itinerary-days-badge{font-size:.55rem;font-weight:700;letter-spacing:.5px;color:#24434b;background:#e6f3f5;padding:.25rem .5rem;border-radius:30px;}
.day-group{border:1px solid #e1ecee;border-radius:14px;padding:.65rem .75rem;background:#ffffff;}
.day-group h6{margin:0 0 .4rem;font-size:.65rem;font-weight:800;letter-spacing:.7px;color:#0f2f35;}
.day-items{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.3rem;}
.day-items li{font-size:.6rem;font-weight:600;color:#21404a;display:flex;align-items:center;gap:.35rem;}
.day-items li span.dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#0f8b92;}
.leaflet-popup-content-wrapper{border-radius:14px;}
.sidebar-section-title{margin:0;font-size:.78rem;font-weight:800;letter-spacing:.65px;color:#e4ecee;text-transform:uppercase;}
/* Add styles for itinerary modal tabs */
.modal-tabs {display:flex;gap:.9rem;border-bottom:1px solid #e4ecee;margin:0 0 1rem;flex-wrap:wrap;}
.modal-tab-btn {background:none;border:none;font-weight:700;font-size:.7rem;letter-spacing:.5px;padding:.55rem .25rem;cursor:pointer;position:relative;color:#34525a;}
.modal-tab-btn.active {color:#0f2f35;}
.modal-tab-btn.active:after {content:"";position:absolute;left:0;right:0;bottom:0;height:2px;background:#0f8b92;border-radius:2px;}
#itineraryOverview, #itineraryDays, #itineraryMapPane {display:none;}
#itineraryOverview.active, #itineraryDays.active, #itineraryMapPane.active {display:block;}
.itinerary-meta-pills {display:flex;gap:.5rem;flex-wrap:wrap;font-size:.55rem;font-weight:600;letter-spacing:.45px;color:#395861;}
.itinerary-meta-pills span {background:#e6f3f5;padding:.3rem .6rem;border-radius:30px;}
.day-group{border:1px solid #e1ecee;border-radius:14px;padding:.65rem .75rem;background:#ffffff;}
#itineraryMap {width:100%;height:480px;border-radius:18px;}
#mapLoadingInline {position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:600;color:#0f2f35;background:linear-gradient(160deg,#ffffff,#fcfeff);} 
.itinerary-card:hover {
  box-shadow: 0 6px 20px -8px rgba(8,35,45,.18);
  border-color: #bfd1d4;
  transform: translateY(-2px) scale(1.03);
  transition: box-shadow 0.18s, border-color 0.18s, transform 0.18s;
}
</style>
</head>
<body class="user-shell" style="background:#f8fafc;">
<?php include_once __DIR__ . '/../includes/navbar.php'; if(function_exists('renderNavbar')) renderNavbar(); ?>
<section class="hero-block">
  <div class="hero-inner">
    <h1 class="hero-title">Discover Curated Itineraries</h1>
    <p class="hero-sub">Explore ready-made travel plans and multi-day experiences across Pangasinan.</p>    
  </div>
</section>

<div class="user-container" style="margin-top:1.5rem;">
  <!-- Removed Spots tab - Things to Do page is for itineraries only -->
  <div id="panel-itineraries" class="tab-panel" style="display:block;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:.8rem;margin-bottom:.7rem;flex-wrap:wrap;">
      <h3 class="sidebar-section-title" style="margin:0;color:#0f2f35;">Curated Itineraries</h3>
      <div style="display:flex;gap:.5rem;align-items:center;">
        <select id="itineraryCategoryFilter" style="padding:.45rem .6rem;border:1px solid #ccd8da;border-radius:10px;font-size:.65rem;">
          <option value="">All Categories</option>
          <option value="Adventure">Adventure</option>
          <option value="Cultural">Cultural</option>
          <option value="Nature">Nature</option>
          <option value="Food & Culinary">Food & Culinary</option>
          <option value="Relaxation">Relaxation</option>
          <option value="Family-friendly">Family-friendly</option>
        </select>
        <select id="itineraryDaysFilter" style="padding:.45rem .6rem;border:1px solid #ccd8da;border-radius:10px;font-size:.65rem;">
          <option value="">All Days</option>
          <option value="1">1 Day</option>
          <option value="2">2 Days</option>
          <option value="3">3 Days</option>
          <option value="4">4 Days</option>
          <option value="5">5 Days</option>
          <option value="6">6 Days</option>
          <option value="7">7 Days</option>
          <option value="8">8 Days</option>
          <option value="9">9 Days</option>
          <option value="10">10 Days</option>
        </select>
        <select id="itinerarySort" style="padding:.45rem .6rem;border:1px solid #ccd8da;border-radius:10px;font-size:.65rem;">
          <option value="newest">Newest</option>
          <option value="days_desc">Most Days</option>
          <option value="days_asc">Fewest Days</option>
        </select>
        <button class="btn outline" id="refreshItineraries" style="padding:.45rem .7rem;font-size:.6rem;background:#fff;">Refresh</button>
      </div>
    </div>
    <div id="itineraryCards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.9rem;"></div>
  </div>
</div>

<!-- Itinerary Modal Refactored -->
<div id="itineraryModal" style="display:none;position:fixed;inset:0;z-index:1200;background:rgba(15,32,41,.55);backdrop-filter:blur(4px);align-items:flex-start;overflow:auto;padding:3rem 1rem 4rem;">
  <div class="modal-inner" style="background:#fff;width:100%;max-width:1100px;margin:0 auto;border-radius:22px;box-shadow:0 12px 40px -8px rgba(8,35,45,.35);display:flex;flex-direction:column;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.4rem;border-bottom:1px solid #eef2f3;">
      <h2 id="modalTitle" style="margin:0;font-size:1.05rem;font-weight:800;color:#0f2f35;">Itinerary</h2>
      <button id="closeModal" class="btn" style="padding:.45rem .85rem;font-size:.65rem;">Close</button>
    </div>
    <div style="padding:0 1.4rem 1.4rem;display:flex;flex-direction:column;">
      <div class="modal-tabs" id="itineraryModalTabs">
  <button class="modal-tab-btn active" data-pane="itineraryOverview">Overview</button>
  <button class="modal-tab-btn" data-pane="itineraryDays">Day Breakdown</button>
  <button class="modal-tab-btn" data-pane="itineraryMapPane">Map</button>
  <button class="modal-tab-btn" data-pane="itineraryReviews">Reviews</button>
      </div>
      <div id="itineraryOverview" class="active" style="display:block;">
        <div id="overviewContent"></div>
<!-- Removed early inline review script (duplicated & caused null reference) - review logic is now handled when overview is (re)built -->
      </div>
      <div id="itineraryDays"></div>
      <div id="itineraryMapPane" style="position:relative;">
        <div id="itineraryMap"></div>
        <div id="mapLoadingInline">Loading map…</div>
      </div>
      <div id="itineraryReviews" style="display:none;">
        <div id="itineraryReviewsRoot" style="display:flex;flex-direction:column;gap:1.1rem;padding:.4rem 0;">
          <div id="itineraryReviewFormWrap" style="border:1px solid #e1ecee;border-radius:16px;padding:.75rem .9rem;background:#f8fbfc;display:flex;flex-direction:column;gap:.6rem;">
            <h4 style="margin:0;font-size:.7rem;font-weight:800;letter-spacing:.6px;color:#0f2f35;display:flex;align-items:center;gap:.45rem;">
              <i class="fa-solid fa-pen-to-square" style="color:#0f8b92;"></i> Write a Review
            </h4>
            <form id="itineraryReviewForm" style="display:flex;flex-direction:column;gap:.55rem;">
              <div style="display:flex;flex-wrap:wrap;gap:.7rem;align-items:center;">
                <div style="display:flex;align-items:center;gap:.35rem;">
                  <label style="font-size:.55rem;font-weight:700;color:#173b43;">Rating</label>
                  <div id="itineraryReviewStars" style="display:flex;gap:.25rem;cursor:pointer;" aria-label="Select rating" role="radiogroup">
                    <span data-val="1" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
                    <span data-val="2" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
                    <span data-val="3" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
                    <span data-val="4" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
                    <span data-val="5" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
                  </div>
                  <input type="hidden" id="itineraryReviewRating" name="rating" value="0" />
                </div>
                <input type="text" id="itineraryReviewerName" name="reviewer_name" placeholder="Your name" required style="flex:1;min-width:140px;padding:.55rem .6rem;border:1px solid #ccd8da;border-radius:10px;font-size:.55rem;font-weight:600;" />
                <input type="email" id="itineraryReviewerEmail" name="reviewer_email" placeholder="Email (optional)" style="flex:1;min-width:160px;padding:.55rem .6rem;border:1px solid #ccd8da;border-radius:10px;font-size:.55rem;font-weight:600;" />
              </div>
              <textarea id="itineraryReviewText" name="review_text" placeholder="Share your experience..." rows="3" required style="width:100%;padding:.6rem .65rem;border:1px solid #ccd8da;border-radius:10px;font-size:.55rem;font-weight:600;resize:vertical;"></textarea>
              <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                <button type="submit" class="btn" style="padding:.55rem .95rem;font-size:.6rem;">Submit Review</button>
                <span id="itineraryReviewStatus" style="font-size:.55rem;font-weight:600;color:#45626b;"></span>
              </div>
            </form>
          </div>
          <div id="itineraryReviewsList" style="display:flex;flex-direction:column;gap:1rem;"></div>
        </div>
      </div>
      <!-- Booking tab removed -->
    </div>
  </div>
</div>

<!-- Leaflet JS with correct SRI (v1.9.4) -->
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
// Show all public itineraries when top Itineraries button is clicked
document.addEventListener('DOMContentLoaded', function() {
  var btn = document.getElementById('showAllItinerariesBtn');
  if (btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      // Switch to itineraries tab
      document.querySelectorAll('.tab-btn').forEach(function(b){
        b.classList.remove('active');
        b.style.background = 'none';
        b.style.color = '#fff';
      });
      var itTab = document.querySelector('.tab-btn[data-tab="itineraries"]');
      if (itTab) {
        itTab.classList.add('active');
        itTab.style.background = '#0f8b92';
        itTab.style.color = '#fff';
      }
      document.querySelectorAll('.tab-panel').forEach(function(p){ p.style.display='none'; });
      var itPanel = document.getElementById('panel-itineraries');
      if (itPanel) itPanel.style.display = 'block';
      // Render all itineraries
      if (typeof renderItineraryCards === 'function') renderItineraryCards();
    });
  }
});
// Fallback loader if CDN blocked
if (typeof L === 'undefined') {
  console.warn('Leaflet CDN failed, attempting secondary source...');
  var alt = document.createElement('script');
  alt.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
  alt.onload = ()=> console.log('Loaded Leaflet from unpkg fallback');
  document.head.appendChild(alt);
}
</script>
<script>
const API_URL = '/tripko-system/tripko-backend/api/itinerary/map_data.php';
let rawData = { spots: [], itineraries: [] };
let mapModalInstance = null; let mapModalLayer = null; let activeItinerary = null; let mapInitialized = false;

async function loadData(){
  try { const res = await fetch(API_URL); if(!res.ok) throw new Error('Network'); rawData = await res.json(); renderItineraryCards(); } catch(e){ console.error('Data load failed', e); }
}
function filteredItineraries() {
  const cat = (document.getElementById('itineraryCategoryFilter').value || '').trim().toLowerCase();
  const days = (document.getElementById('itineraryDaysFilter').value || '').trim();
  let list = rawData.itineraries;
  // Category not present in API/schema; ignore category filter to avoid empty results
  if (days) {
    list = list.filter(it => String(it.total_days) === days);
  }
  return list;
}
function renderItineraryCards(){
  const wrap=document.getElementById('itineraryCards');
  if(!wrap) return;
  const list = filteredItineraries();
  if(!list.length){
    wrap.innerHTML='<div style="font-size:.65rem;color:#446068;">No public itineraries yet.</div>';
    return;
  }
  const sort=document.getElementById('itinerarySort').value;
  if(sort==='days_desc') list.sort((a,b)=> b.total_days - a.total_days);
  else if(sort==='days_asc') list.sort((a,b)=> a.total_days - b.total_days);
  else if(sort==='newest') list.sort((a,b)=> b.id - a.id);
  wrap.innerHTML = list.map(it=>{
    let img = it.image || '/tripko-system/tripko-frontend/images/placeholder.jpg';
    if(img.startsWith('/uploads/')){
      const fileOnly=img.split('/').pop();
      img='/tripko-system/uploads/'+encodeURIComponent(fileOnly);
    }
    const rating = (it.avg_rating && it.review_count)? `<div style='position:absolute;top:6px;left:6px;background:rgba(15,47,53,.85);color:#fff;font-size:.55rem;font-weight:700;padding:.25rem .4rem;border-radius:10px;display:flex;align-items:center;gap:.25rem;'><i class='fa-solid fa-star' style='color:#f6b400;'></i>${Number(it.avg_rating).toFixed(1)}<span style='opacity:.75;'>(${it.review_count})</span></div>`:'';
    return `<div class=\"itinerary-card\" data-it=\"${it.id}\" style=\"position:relative;display:flex;flex-direction:column;gap:.5rem;border:1px solid #dfe6e8;border-radius:20px;overflow:hidden;background:#fff;box-shadow:var(--shadow-sm);cursor:pointer;\"><div style=\"position:relative;aspect-ratio:4/3;background:#eef2f3;background-image:url('${img}');background-size:cover;background-position:center;\">${rating}</div><div style=\"padding:.65rem .75rem .8rem;display:flex;flex-direction:column;gap:.45rem;\"><h5 style=\"margin:0;font-size:.8rem;font-weight:800;color:#0f2f35;\">${it.title}</h5><div style=\"display:flex;gap:.4rem;flex-wrap:wrap;align-items:center;font-size:.55rem;font-weight:600;letter-spacing:.5px;color:#45626b;\"><span class=\"itinerary-days-badge\">${it.total_days} DAY${it.total_days===1?'':'S'}</span><span>${it.town_name||''}</span></div></div></div>`;
  }).join('');
  wrap.querySelectorAll('.itinerary-card').forEach(el=> el.addEventListener('click', ()=>{ const id=parseInt(el.getAttribute('data-it')); openItineraryModal(id); }));
}
function openItineraryModal(id){ activeItinerary = rawData.itineraries.find(i=>i.id===id); if(!activeItinerary) return; document.getElementById('modalTitle').textContent=activeItinerary.title; buildOverview(); buildDays(); setActivePane('itineraryOverview'); document.getElementById('itineraryModal').style.display='flex'; document.body.style.overflow='hidden'; }
function closeItineraryModal(){ document.getElementById('itineraryModal').style.display='none'; document.body.style.overflow='auto'; }

function buildOverview(){ const wrap=document.getElementById('overviewContent'); if(!wrap) return; let imgPath = activeItinerary.image ? activeItinerary.image : '/tripko-system/tripko-frontend/images/placeholder.jpg'; if(imgPath.startsWith('/uploads/')){ const fileOnly=imgPath.split('/').pop(); imgPath='/tripko-system/uploads/'+encodeURIComponent(fileOnly); } const safeDesc = activeItinerary.description ? activeItinerary.description.replace(/</g,'&lt;') : 'No description provided.'; const ratingBlock = (activeItinerary.avg_rating && activeItinerary.review_count)? `<div style='display:flex;align-items:center;gap:.4rem;font-size:.6rem;font-weight:700;color:#0f2f35;'><i class='fa-solid fa-star' style='color:#f6b400;'></i>${Number(activeItinerary.avg_rating).toFixed(1)} <span style='color:#456068;font-weight:600;'>(${activeItinerary.review_count} reviews)</span></div>`:''; let dayBreakdownHtml = ''; if (activeItinerary.days && activeItinerary.days.length) { dayBreakdownHtml = `<div id=\"overviewDayBreakdown\" style=\"margin-top:1.2rem;\"><h3 style=\"margin:0 0 .6rem;font-size:.8rem;font-weight:800;color:#0f2f35;\">Day Breakdown</h3>` +
      activeItinerary.days.map(d=>`<div class='day-group' style='margin-bottom:.8rem;'><h6 style='margin:0 0 .4rem;font-size:.65rem;font-weight:800;letter-spacing:.7px;color:#0f2f35;'>DAY ${d.day} ${d.title?('- '+d.title):''}</h6><ul class='day-items' style='list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.3rem;'>${d.items.map(itm=>`<li style='font-size:.6rem;font-weight:600;color:#21404a;display:flex;align-items:center;gap:.35rem;'><span class='dot' style='display:inline-block;width:8px;height:8px;border-radius:50%;background:#0f8b92;'></span>${itm.name}</li>`).join('')}</ul></div>`).join('') + '</div>';
  }
  let mapHtml = `<div id=\"overviewMapSection\" style=\"margin-top:1.2rem;\"><h3 style=\"margin:0 0 .6rem;font-size:.8rem;font-weight:800;color:#0f2f35;\">Map</h3><div id=\"overviewMapContainer\" style=\"width:100%;height:180px;border-radius:18px;box-shadow:var(--shadow-md);background:#eaf3f6;position:relative;overflow:hidden;\"></div></div>`;
  let reviewsHtml = `
  <div id="overviewReviewsSection" style="margin-top:1.2rem;">
    <h3 style="margin:0 0 .6rem;font-size:.8rem;font-weight:800;color:#0f2f35;">Reviews</h3>
    <button id="showReviewFormBtn" class="btn" style="margin-bottom:.7rem;padding:.55rem .95rem;font-size:.6rem;">Write a Review</button>
    <div id="overviewReviewFormWrap" style="display:none;border:1px solid #e1ecee;border-radius:16px;padding:.75rem .9rem;background:#f8fbfc;max-width:420px;margin-bottom:1rem;">
  <form id="overviewReviewForm" style="display:flex;flex-direction:column;gap:.55rem;" action="javascript:void(0);">
        <div style="display:flex;flex-wrap:wrap;gap:.7rem;align-items:center;">
          <div style="display:flex;align-items:center;gap:.35rem;">
            <label style="font-size:.55rem;font-weight:700;color:#173b43;">Rating <span style="color:#a33;">*</span></label>
            <div id="overviewReviewStars" style="display:flex;gap:.25rem;cursor:pointer;" aria-label="Select rating" role="radiogroup">
              <span data-val="1" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
              <span data-val="2" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
              <span data-val="3" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
              <span data-val="4" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
              <span data-val="5" class="star" style="font-size:.9rem;color:#c5d4d7;" role="radio" aria-checked="false">★</span>
            </div>
            <input type="hidden" id="overviewReviewRating" name="rating" value="0" />
          </div>
          <input type="text" id="overviewReviewerName" name="reviewer_name" placeholder="Your name *" required style="flex:1;min-width:140px;padding:.55rem .6rem;border:1px solid #ccd8da;border-radius:10px;font-size:.55rem;font-weight:600;" />
          <input type="email" id="overviewReviewerEmail" name="reviewer_email" placeholder="Email (optional)" style="flex:1;min-width:160px;padding:.55rem .6rem;border:1px solid #ccd8da;border-radius:10px;font-size:.55rem;font-weight:600;" />
        </div>
  <textarea id="overviewReviewText" name="review_text" placeholder="Share your experience *" rows="3" required style="width:100%;padding:.6rem .65rem;border:1px solid #ccd8da;border-radius:10px;font-size:.55rem;font-weight:600;resize:vertical;"></textarea>
        <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
          <button type="submit" class="btn" style="padding:.55rem .95rem;font-size:.6rem;">Submit Review</button>
          <span id="overviewReviewStatus" style="font-size:.55rem;font-weight:600;color:#45626b;"></span>
        </div>
      </form>
    </div>
    <div id="overviewReviewsContainer" style="width:100%;"></div>
  </div>`;
  // Always re-attach review form listeners after modal rebuild
  setTimeout(()=>{
    setupOverviewReviewForm();
  }, 120);
// Always re-attach overview review form listeners after modal rebuild
function setupOverviewReviewForm() {
  const btn = document.getElementById('showReviewFormBtn');
  const formWrap = document.getElementById('overviewReviewFormWrap');
  if(btn && formWrap){
    btn.onclick = function(){
      formWrap.style.display = formWrap.style.display === 'none' ? 'block' : 'none';
      if(formWrap.style.display === 'block'){
        formWrap.scrollIntoView({behavior:'smooth',block:'center'});
      }
    };
  }
  setupOverviewStars();
  const form = document.getElementById('overviewReviewForm');
  if(form){
    form.onsubmit = async function(e){
      e.preventDefault();
      const statusEl = document.getElementById('overviewReviewStatus');
      statusEl.style.color = '#45626b';
      statusEl.textContent = 'Submitting...';
      if (!activeItinerary || !activeItinerary.id) {
        statusEl.style.color = '#a33';
        statusEl.textContent = 'Error: No itinerary selected.';
        return;
      }
      const payload = {
        itinerary_id: activeItinerary.id,
        reviewer_name: document.getElementById('overviewReviewerName').value.trim(),
        reviewer_email: document.getElementById('overviewReviewerEmail').value.trim(),
        rating: parseInt(document.getElementById('overviewReviewRating').value) || 0,
        review_text: document.getElementById('overviewReviewText').value.trim()
      };
      if (!payload.rating) {
        statusEl.style.color = '#a33';
        statusEl.textContent = 'Select a rating';
        return;
      }
      try {
        const res = await fetch('/tripko-system/tripko-backend/api/reviews/submit_itinerary_review.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed');
        statusEl.style.color = '#0f8b92';
        statusEl.textContent = 'Review submitted!';
        form.reset();
        document.getElementById('overviewReviewRating').value = 0;
        setupOverviewStars();
        // Refresh reviews inline
        setTimeout(async () => {
          try {
            const res = await fetch(`/tripko-system/tripko-backend/api/reviews/get_itinerary_reviews.php?itinerary_id=${activeItinerary.id}`);
            const data = await res.json();
            if (data.success && data.data) {
              activeItinerary.avg_rating = data.data.average_rating;
              activeItinerary.review_count = data.data.total_reviews;
              activeItinerary.reviews = data.data.reviews;
              activeItinerary.rating_breakdown = data.data.rating_breakdown;
            }
          } catch (err) { }
          buildOverview();
        }, 500);
      } catch (err) {
        statusEl.style.color = '#a33';
        statusEl.textContent = err.message || 'Error submitting review';
      }
    };
  }
}
  wrap.innerHTML = `
    <div class='detail-modal-header'>
      <img src='${imgPath}' alt='${activeItinerary.title}' />
      <h2>${activeItinerary.title}</h2>
    </div>
    <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:1.2rem;">
      <h3 style="margin:0;font-size:.85rem;font-weight:800;color:#0f2f35;">Overview</h3>
      ${ratingBlock}
      <p style="margin:0;font-size:.62rem;line-height:1.25rem;color:#27454f;">${safeDesc}</p>
      <div class="itinerary-meta-pills"><span>${activeItinerary.total_days} Day${activeItinerary.total_days===1?'':'s'}</span>${activeItinerary.town_name?`<span>${activeItinerary.town_name}</span>`:''}</div>
      ${dayBreakdownHtml}
      ${mapHtml}
      ${reviewsHtml}
    </div>
  `;
  // Render map in overview
  setTimeout(()=>{
    const mapDiv = document.getElementById('overviewMapContainer');
    if(mapDiv && typeof L !== 'undefined'){
      mapDiv.innerHTML = '';
      const map = L.map(mapDiv, {zoomControl:true, attributionControl:false});
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map);
      const layer = L.layerGroup().addTo(map);
      let first=null;
      if(activeItinerary.days){
        activeItinerary.days.forEach(d=> d.items.forEach(itm=>{
          if(itm.lat && itm.lng){
            const mk=L.marker([itm.lat,itm.lng],{title:itm.name});
            mk.bindPopup(`<strong>${itm.name}</strong>`);
            mk.addTo(layer);
            if(!first) first=[itm.lat,itm.lng];
          }
        }));
      }
      if(first) map.setView(first,11);
      setTimeout(()=> map.invalidateSize(), 250);
    }
    // Render reviews in overview
    const reviewsDiv = document.getElementById('overviewReviewsContainer');
    if(reviewsDiv){
      reviewsDiv.innerHTML = '<div style="padding:.75rem 0;font-size:.6rem;color:#456268;">Loading reviews...</div>';
      fetch(`/tripko-system/tripko-backend/api/reviews/get_itinerary_reviews.php?itinerary_id=${activeItinerary.id}`)
        .then(res=>res.json())
        .then(data=>{
          if(!data.success){ reviewsDiv.innerHTML = `<div style=\'font-size:.6rem;color:#a33;'>${data.message||'Failed to load reviews.'}</div>`; return; }
          const info = data.data;
          const avg = info.average_rating || 0; const total = info.total_reviews || 0;
          let breakdownHtml='';
          if(total){ breakdownHtml = Object.entries(info.rating_breakdown).sort((a,b)=> parseInt(b[0])-parseInt(a[0]))
              .map(([star,obj])=>`<div style='display:flex;align-items:center;gap:.4rem;font-size:.55rem;'>
                  <span style='width:34px;font-weight:700;color:#0f2f35;'>${star}★</span>
                  <div style='flex:1;height:6px;background:#edf2f3;border-radius:4px;overflow:hidden;'><div style='height:100%;width:${obj.percentage}%;background:#0f8b92;'></div></div>
                  <span style='font-weight:600;color:#45626b;'>${obj.count}</span>
                </div>`).join(''); }
          const headerHtml = `<div style='display:flex;flex-direction:column;gap:.7rem;'>
              <div style='display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;'>
                <div style='display:flex;align-items:center;gap:.45rem;background:#0f2f35;color:#fff;padding:.55rem .8rem;border-radius:16px;font-size:.8rem;font-weight:800;'>
                  <i class='fa-solid fa-star' style='color:#f6b400;font-size:.9rem;'></i>${total?avg.toFixed(1):'—'}<span style='font-size:.55rem;font-weight:600;opacity:.8;'>/5</span>
                </div>
                <div style='font-size:.6rem;font-weight:600;color:#45626b;'>${total?`Based on ${total} review${total===1?'':'s'}`:'Be the first to review this itinerary'}</div>
              </div>
              ${breakdownHtml?`<div style='display:flex;flex-direction:column;gap:.4rem;'>${breakdownHtml}</div>`:''}
            </div>`;
          if(!total){
            reviewsDiv.innerHTML = headerHtml + `<div style='padding:.4rem 0;font-size:.58rem;color:#456068;'>No reviews yet.${info.note? ' <em>'+info.note+'</em>':''}</div>`;
          } else {
            const list = info.reviews.map(r=>`<div style='border:1px solid #e1ecee;border-radius:14px;padding:.65rem .75rem;background:#fff;display:flex;gap:.7rem;'>
                <div style='width:38px;height:38px;border-radius:50%;background:#0f8b92;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;'>${r.reviewer_initial}</div>
                <div style='flex:1;display:flex;flex-direction:column;gap:.3rem;'>
                  <div style='display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;'>
                    <strong style='font-size:.65rem;color:#0f2f35;'>${r.reviewer_name}</strong>
                    <span style='display:inline-flex;align-items:center;gap:2px;background:#0f2f35;color:#fff;font-size:.55rem;font-weight:700;padding:.2rem .4rem;border-radius:8px;'>
                      <i class='fa-solid fa-star' style='color:#f6b400;'></i>${r.rating}
                    </span>
                    <span style='font-size:.5rem;font-weight:600;color:#52727a;'>${r.date}</span>
                  </div>
                  <p style='margin:0;font-size:.58rem;line-height:1.1rem;color:#27454f;'>${r.review_text}</p>
                </div>
              </div>`).join('');
            reviewsDiv.innerHTML = headerHtml + `<div style='display:flex;flex-direction:column;gap:.7rem;margin-top:.5rem;'>${list}</div>` + (info.pagination && info.pagination.has_more ? `<button id='loadMoreItineraryReviewsOverview' class='btn outline' style='align-self:flex-start;margin-top:.6rem;padding:.45rem .85rem;font-size:.55rem;'>Load more</button>`:'');
            const loadMoreBtn=document.getElementById('loadMoreItineraryReviewsOverview');
            if(loadMoreBtn){ loadMoreBtn.addEventListener('click', async ()=>{ const nextPage=(info.pagination.current_page||1)+1; loadMoreBtn.disabled=true; loadMoreBtn.textContent='Loading...'; try{ const res=await fetch(`/tripko-system/tripko-backend/api/reviews/get_itinerary_reviews.php?itinerary_id=${activeItinerary.id}&page=${nextPage}`); if(res.ok){ const d=await res.json(); if(d.success){ d.data.reviews.forEach(r=> info.reviews.push(r)); info.pagination=d.data.pagination; info.total_reviews=d.data.total_reviews; info.average_rating=d.data.average_rating; info.rating_breakdown=d.data.rating_breakdown; /* re-render */ reviewsDiv.innerHTML = ''; setTimeout(()=>{ reviewsDiv.innerHTML = headerHtml + `<div style='display:flex;flex-direction:column;gap:.7rem;margin-top:.5rem;'>${list}</div>` + (info.pagination && info.pagination.has_more ? `<button id='loadMoreItineraryReviewsOverview' class='btn outline' style='align-self:flex-start;margin-top:.6rem;padding:.45rem .85rem;font-size:.55rem;'>Load more</button>`:''); }, 50); } } }catch(err){ console.error(err); loadMoreBtn.textContent='Error'; } }); }
          }
        });
    }
  }, 150);
}
function buildDays(){ const pane=document.getElementById('itineraryDays'); if(!pane) return; if(!activeItinerary.days.length){ pane.innerHTML = `<div style='font-size:.6rem;color:#446068;'>No day breakdown yet.</div>`; return; } pane.innerHTML = activeItinerary.days.map(d=>`<div class='day-group' style='margin-bottom:.8rem;'><h6 style='margin:0 0 .4rem;font-size:.65rem;font-weight:800;letter-spacing:.7px;color:#0f2f35;'>DAY ${d.day} ${d.title?('- '+d.title):''}</h6><ul class='day-items' style='list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.3rem;'>${d.items.map(itm=>`<li style='font-size:.6rem;font-weight:600;color:#21404a;display:flex;align-items:center;gap:.35rem;'><span class='dot' style='display:inline-block;width:8px;height:8px;border-radius:50%;background:#0f8b92;'></span>${itm.name}</li>`).join('')}</ul></div>`).join(''); }

function initMapIfNeeded(){ if(mapInitialized) return; if(typeof L==='undefined'){ setTimeout(initMapIfNeeded,200); return; } mapModalInstance = L.map('itineraryMap',{zoomControl:true, attributionControl:false}); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(mapModalInstance); mapModalLayer=L.layerGroup().addTo(mapModalInstance); document.getElementById('mapLoadingInline').style.display='none'; mapInitialized=true; refreshItineraryMap(); }
function refreshItineraryMap(){ if(!mapInitialized || !mapModalLayer) return; mapModalLayer.clearLayers(); if(!activeItinerary) return; let first=null; activeItinerary.days.forEach(d=> d.items.forEach(itm=>{ if(itm.lat && itm.lng){ const mk=L.marker([itm.lat,itm.lng],{title:itm.name}); mk.bindPopup(`<strong>${itm.name}</strong>`); mk.addTo(mapModalLayer); if(!first) first=[itm.lat,itm.lng]; } })); if(first) mapModalInstance.setView(first,11); setTimeout(()=> mapModalInstance.invalidateSize(), 250); }

function setActivePane(id){ ['itineraryOverview','itineraryDays','itineraryMapPane','itineraryReviews','itineraryBooking'].forEach(pid=>{ const el=document.getElementById(pid); if(el) el.classList.remove('active'); }); const target=document.getElementById(id); if(target){ target.classList.add('active'); if(id==='itineraryMapPane'){ initMapIfNeeded(); refreshItineraryMap(); } if(id==='itineraryBooking'){ loadItineraryBooking(); } } document.querySelectorAll('.modal-tab-btn').forEach(btn=> btn.classList.remove('active')); document.querySelectorAll(`.modal-tab-btn[data-pane='${id}']`).forEach(b=> b.classList.add('active')); }
// Reviews loading (lazy)
let itineraryReviewsLoadedFor = null;
async function loadItineraryReviews(){
  if(!activeItinerary) return;
  if(itineraryReviewsLoadedFor === activeItinerary.id) return; // already loaded
  const wrap = document.getElementById('itineraryReviews');
  if(!wrap) return;
  wrap.innerHTML = `<div style="padding:.75rem 0;font-size:.6rem;color:#456268;">Loading reviews...</div>`;
  try {
    const res = await fetch(`/tripko-system/tripko-backend/api/reviews/get_itinerary_reviews.php?itinerary_id=${activeItinerary.id}`);
    if(!res.ok) throw new Error('Failed');
    const data = await res.json();
    if(!data.success){ wrap.innerHTML = `<div style='font-size:.6rem;color:#a33;'>${data.message||'Failed to load reviews.'}</div>`; return; }
    itineraryReviewsLoadedFor = activeItinerary.id;
    renderItineraryReviews(data.data);
  } catch(e){
    console.error('Itinerary reviews error', e);
    wrap.innerHTML = `<div style='font-size:.6rem;color:#a33;'>Error loading reviews.</div>`;
  }
}
function renderItineraryReviews(info){
  const root = document.getElementById('itineraryReviewsRoot'); if(!root) return;
  const listWrap = document.getElementById('itineraryReviewsList');
  const avg = info.average_rating || 0; const total = info.total_reviews || 0;
  let breakdownHtml='';
  if(total){ breakdownHtml = Object.entries(info.rating_breakdown).sort((a,b)=> parseInt(b[0])-parseInt(a[0]))
      .map(([star,obj])=>`<div style='display:flex;align-items:center;gap:.4rem;font-size:.55rem;'>
          <span style='width:34px;font-weight:700;color:#0f2f35;'>${star}★</span>
          <div style='flex:1;height:6px;background:#edf2f3;border-radius:4px;overflow:hidden;'><div style='height:100%;width:${obj.percentage}%;background:#0f8b92;'></div></div>
          <span style='font-weight:600;color:#45626b;'>${obj.count}</span>
        </div>`).join(''); }
  const headerHtml = `<div style='display:flex;flex-direction:column;gap:.7rem;'>
      <div style='display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;'>
        <div style='display:flex;align-items:center;gap:.45rem;background:#0f2f35;color:#fff;padding:.55rem .8rem;border-radius:16px;font-size:.8rem;font-weight:800;'>
          <i class='fa-solid fa-star' style='color:#f6b400;font-size:.9rem;'></i>${total?avg.toFixed(1):'—'}<span style='font-size:.55rem;font-weight:600;opacity:.8;'>/5</span>
        </div>
        <div style='font-size:.6rem;font-weight:600;color:#45626b;'>${total?`Based on ${total} review${total===1?'':'s'}`:'Be the first to review this itinerary'}</div>
      </div>
      ${breakdownHtml?`<div style='display:flex;flex-direction:column;gap:.4rem;'>${breakdownHtml}</div>`:''}
    </div>`;
  if(!total){
    listWrap.innerHTML = headerHtml + `<div style='padding:.4rem 0;font-size:.58rem;color:#456068;'>No reviews yet.${info.note? ' <em>'+info.note+'</em>':''}</div>`;
  } else {
    const list = info.reviews.map(r=>`<div style='border:1px solid #e1ecee;border-radius:14px;padding:.65rem .75rem;background:#fff;display:flex;gap:.7rem;'>
        <div style='width:38px;height:38px;border-radius:50%;background:#0f8b92;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;'>${r.reviewer_initial}</div>
        <div style='flex:1;display:flex;flex-direction:column;gap:.3rem;'>
          <div style='display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;'>
            <strong style='font-size:.65rem;color:#0f2f35;'>${r.reviewer_name}</strong>
            <span style='display:inline-flex;align-items:center;gap:2px;background:#0f2f35;color:#fff;font-size:.55rem;font-weight:700;padding:.2rem .4rem;border-radius:8px;'>
              <i class='fa-solid fa-star' style='color:#f6b400;'></i>${r.rating}
            </span>
            <span style='font-size:.5rem;font-weight:600;color:#52727a;'>${r.date}</span>
          </div>
          <p style='margin:0;font-size:.58rem;line-height:1.1rem;color:#27454f;'>${r.review_text}</p>
        </div>
      </div>`).join('');
    listWrap.innerHTML = headerHtml + `<div style='display:flex;flex-direction:column;gap:.7rem;margin-top:.5rem;'>${list}</div>` + (info.pagination && info.pagination.has_more ? `<button id='loadMoreItineraryReviews' class='btn outline' style='align-self:flex-start;margin-top:.6rem;padding:.45rem .85rem;font-size:.55rem;'>Load more</button>`:'');
  }
  const loadMoreBtn=document.getElementById('loadMoreItineraryReviews');
  if(loadMoreBtn){ loadMoreBtn.addEventListener('click', async ()=>{ const nextPage=(info.pagination.current_page||1)+1; loadMoreBtn.disabled=true; loadMoreBtn.textContent='Loading...'; try{ const res=await fetch(`/tripko-system/tripko-backend/api/reviews/get_itinerary_reviews.php?itinerary_id=${activeItinerary.id}&page=${nextPage}`); if(res.ok){ const d=await res.json(); if(d.success){ d.data.reviews.forEach(r=> info.reviews.push(r)); info.pagination=d.data.pagination; info.total_reviews=d.data.total_reviews; info.average_rating=d.data.average_rating; info.rating_breakdown=d.data.rating_breakdown; renderItineraryReviews(info); } } }catch(err){ console.error(err); loadMoreBtn.textContent='Error'; } }); }
  setupItineraryReviewForm(info);
}

let itineraryReviewFormBound=false;
function setupItineraryReviewForm(info){
  if(itineraryReviewFormBound) return;
  const form=document.getElementById('itineraryReviewForm'); if(!form) return;
  const starsWrap=document.getElementById('itineraryReviewStars'); const ratingInput=document.getElementById('itineraryReviewRating'); const statusEl=document.getElementById('itineraryReviewStatus');
  function paintStars(val){ starsWrap.querySelectorAll('.star').forEach(s=>{ const v=parseInt(s.dataset.val); s.style.color= v<=val? '#f6b400':'#c5d4d7'; s.setAttribute('aria-checked', v===val?'true':'false'); }); }
  starsWrap.addEventListener('click', e=>{ const star=e.target.closest('.star'); if(!star) return; const v=parseInt(star.dataset.val); ratingInput.value=v; paintStars(v); });
  starsWrap.addEventListener('keydown', e=>{ const current=parseInt(ratingInput.value)||0; if(['ArrowRight','ArrowUp'].includes(e.key)){ e.preventDefault(); const nv=Math.min(5,current+1); ratingInput.value=nv; paintStars(nv); } if(['ArrowLeft','ArrowDown'].includes(e.key)){ e.preventDefault(); const nv=Math.max(1,current-1); ratingInput.value=nv; paintStars(nv); } });
  form.addEventListener('submit', async (e)=>{ e.preventDefault(); statusEl.style.color='#45626b'; statusEl.textContent='Submitting...'; const payload={ itinerary_id: activeItinerary.id, reviewer_name: document.getElementById('itineraryReviewerName').value.trim(), reviewer_email: document.getElementById('itineraryReviewerEmail').value.trim(), rating: parseInt(ratingInput.value)||0, review_text: document.getElementById('itineraryReviewText').value.trim() }; if(!payload.rating){ statusEl.style.color='#a33'; statusEl.textContent='Select a rating'; return; } try{ const res= await fetch('/tripko-system/tripko-backend/api/reviews/submit_itinerary_review.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }); const data= await res.json(); if(!res.ok || !data.success){ throw new Error(data.message||'Failed'); } statusEl.style.color='#0f8b92'; statusEl.textContent='Review submitted!'; const newReview={ reviewer_initial: payload.reviewer_name.charAt(0).toUpperCase(), reviewer_name: payload.reviewer_name, rating: payload.rating, date: new Date().toLocaleDateString(undefined,{month:'long',day:'2-digit',year:'numeric'}), review_text: payload.review_text }; info.reviews.unshift(newReview); info.total_reviews=(info.total_reviews||0)+1; info.average_rating = ((info.average_rating||0)*(info.total_reviews-1) + payload.rating)/info.total_reviews; if(info.rating_breakdown && info.rating_breakdown[payload.rating]){ info.rating_breakdown[payload.rating].count +=1; const totalForPct = Object.values(info.rating_breakdown).reduce((a,b)=> a + b.count,0); Object.keys(info.rating_breakdown).forEach(st=>{ const c=info.rating_breakdown[st].count; info.rating_breakdown[st].percentage = totalForPct? Math.round((c/totalForPct)*100):0; }); } form.reset(); ratingInput.value=0; paintStars(0); renderItineraryReviews(info); }catch(err){ console.error(err); statusEl.style.color='#a33'; statusEl.textContent= err.message || 'Error submitting review'; } });
  paintStars(0);
  itineraryReviewFormBound=true;
}

// Events
const refreshBtn = document.getElementById('refreshItineraries');
if(refreshBtn){
  refreshBtn.addEventListener('click', function() {
    const cat=document.getElementById('itineraryCategoryFilter'); if(cat) cat.value='';
    const days=document.getElementById('itineraryDaysFilter'); if(days) days.value='';
    const sort=document.getElementById('itinerarySort'); if(sort) sort.value='newest';
    renderItineraryCards();
  });
}
const itinerarySortSel = document.getElementById('itinerarySort');
if(itinerarySortSel){ itinerarySortSel.addEventListener('change', renderItineraryCards); }
const itineraryDaysSel = document.getElementById('itineraryDaysFilter');
if(itineraryDaysSel){ itineraryDaysSel.addEventListener('change', renderItineraryCards); }
const closeModalBtn = document.getElementById('closeModal');
if(closeModalBtn){ closeModalBtn.addEventListener('click', closeItineraryModal); }

// Modal tab switching
document.addEventListener('click', (e)=>{
  const btn = e.target.closest('.modal-tab-btn');
  if(!btn) return;
  const pane=btn.getAttribute('data-pane');
  if(pane==='itineraryDays'){
    // Scroll to day breakdown section in overview
    setActivePane('itineraryOverview');
    setTimeout(()=>{
      const breakdown = document.getElementById('overviewDayBreakdown');
      if(breakdown){
        breakdown.scrollIntoView({behavior:'smooth', block:'start'});
      }
    }, 100);
  } else if(pane==='itineraryMapPane'){
    // Scroll to map section in overview
    setActivePane('itineraryOverview');
    setTimeout(()=>{
      const mapSection = document.getElementById('overviewMapSection');
      if(mapSection){
        mapSection.scrollIntoView({behavior:'smooth', block:'start'});
      }
    }, 100);
  } else if(pane==='itineraryReviews'){
    // Scroll to reviews section in overview
    setActivePane('itineraryOverview');
    setTimeout(()=>{
      const reviewsSection = document.getElementById('overviewReviewsSection');
      if(reviewsSection){
        reviewsSection.scrollIntoView({behavior:'smooth', block:'start'});
      }
    }, 100);
  } else {
    setActivePane(pane);
  }
});
// Booking logic
let itineraryBookingLoadedFor = null; let bookingDataCache = {};
async function loadItineraryBooking(){
  if(!activeItinerary) return; if(itineraryBookingLoadedFor===activeItinerary.id) return; const wrap=document.getElementById('bookingContent'); if(!wrap) return; wrap.textContent='Loading booking info...';
  try {
    const res = await fetch(`/tripko-system/tripko-backend/api/itinerary/get_itinerary_booking.php?itinerary_id=${activeItinerary.id}`); if(!res.ok) throw new Error('network'); const data = await res.json(); if(!data.success){ wrap.textContent=data.message||'Failed to load.'; return; } bookingDataCache[activeItinerary.id]=data.data; itineraryBookingLoadedFor=activeItinerary.id; renderBookingPane();
  } catch(err){ console.error(err); wrap.textContent='Error loading booking info.'; }
}
function renderBookingPane(){ const wrap=document.getElementById('bookingContent'); if(!wrap) return; const info=bookingDataCache[activeItinerary.id]; if(!info){ wrap.textContent='No booking data.'; return; }
  const tiersHtml = info.tiers && info.tiers.length ? info.tiers.map(t=>`<tr><td style='padding:.3rem .4rem;border-bottom:1px solid #e5ecee;'>${t.min_pax} - ${t.max_pax||t.min_pax}</td><td style='padding:.3rem .4rem;border-bottom:1px solid #e5ecee;font-weight:600;'>₱${Number(t.price_per_adult).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td></tr>`).join('') : '<tr><td colspan="2" style="padding:.4rem;font-size:.55rem;color:#566f75;">No pricing tiers yet.</td></tr>';
  const cancellation = info.cancellation_policy_text ? `<div style='font-size:.55rem;line-height:1rem;background:#f4fafb;padding:.55rem .65rem;border-radius:12px;margin-top:.6rem;'><strong style='display:block;font-size:.6rem;margin-bottom:.35rem;color:#0f2f35;'>Cancellation policy</strong>${info.cancellation_policy_text}</div>` : '';
  const rnp = info.reserve_now_pay_later ? `<div style='font-size:.55rem;line-height:1rem;background:#f6f9f9;padding:.55rem .65rem;border-radius:12px;margin-top:.6rem;'><strong style='display:block;font-size:.6rem;margin-bottom:.35rem;color:#0f2f35;'>Reserve now & pay later</strong>Secure your spot while staying flexible.</div>`:'';
  wrap.innerHTML = `<div style='display:flex;flex-direction:column;gap:1rem;'>
    <div>
      <div style='display:flex;flex-wrap:wrap;gap:.8rem;align-items:center;'>
        <div style='font-size:.75rem;font-weight:800;color:#0f2f35;'>From <span style='color:#0f8b92;'>₱${info.from_price_per_adult? Number(info.from_price_per_adult).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}):'—'}</span> <span style='font-size:.55rem;font-weight:600;color:#45626b;'>per adult</span></div>
      </div>
      <form id='bookingCalcForm' style='margin-top:.8rem;display:flex;flex-direction:column;gap:.6rem;max-width:420px;'>
        <label style='font-size:.55rem;font-weight:700;letter-spacing:.5px;color:#173b43;display:flex;flex-direction:column;gap:.25rem;'>Select date
          <input type='date' id='bookingDate' required style='padding:.55rem .6rem;border:1px solid #cdd8da;border-radius:10px;font-size:.6rem;font-weight:600;'>
        </label>
        <label style='font-size:.55rem;font-weight:700;letter-spacing:.5px;color:#173b43;display:flex;flex-direction:column;gap:.25rem;'>Travelers (Adults)
          <input type='number' id='bookingTravelers' min='${info.min_travelers}' value='${info.min_travelers}' ${info.max_travelers?`max='${info.max_travelers}'`:''} required style='padding:.55rem .6rem;border:1px solid #cdd8da;border-radius:10px;font-size:.6rem;font-weight:600;'>
        </label>
        <div id='bookingPriceSummary' style='font-size:.6rem;color:#0f2f35;font-weight:600;'>Select date & travelers to see price.</div>
        <button type='submit' class='btn' style='align-self:flex-start;padding:.55rem .9rem;font-size:.6rem;'>Reserve Now</button>
      </form>
      ${cancellation}
      ${rnp}
      <div style='margin-top:1rem;'>
         <h4 style='margin:0 0 .4rem;font-size:.65rem;font-weight:800;color:#0f2f35;'>Pricing Tiers</h4>
         <table style='width:100%;border-collapse:collapse;font-size:.55rem;'>
           <thead><tr><th style='text-align:left;padding:.35rem .4rem;border-bottom:2px solid #dbe5e7;font-size:.55rem;'>Group Size</th><th style='text-align:left;padding:.35rem .4rem;border-bottom:2px solid #dbe5e7;font-size:.55rem;'>Price / Adult</th></tr></thead>
           <tbody>${tiersHtml}</tbody>
         </table>
      </div>
    </div>
  </div>`;
  const form=document.getElementById('bookingCalcForm'); form.addEventListener('submit', e=>{ e.preventDefault(); computeBookingPrice(info); });
  document.getElementById('bookingTravelers').addEventListener('input', ()=> computeBookingPrice(info));
}
function computeBookingPrice(info){ const travelersEl=document.getElementById('bookingTravelers'); const dateEl=document.getElementById('bookingDate'); const out=document.getElementById('bookingPriceSummary'); if(!travelersEl||!dateEl||!out) return; const pax=parseInt(travelersEl.value)||0; if(!pax){ out.textContent='Enter traveler count.'; return; } if(!dateEl.value){ out.textContent='Select a date.'; return; }
  // choose tier where pax between min_pax and max_pax (or max_pax==0 / treat 0 as open-ended)
  const tier = (info.tiers||[]).find(t=> pax>=t.min_pax && (t.max_pax===0 || pax<=t.max_pax));
  if(!tier){ out.textContent='No tier matches this group size.'; return; }
  const pricePerAdult = tier.price_per_adult; const total = pricePerAdult * pax; out.innerHTML = `${pax} Adult${pax===1?'':'s'} x ₱${pricePerAdult.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}<br><strong style='font-size:.65rem;'>Total ₱${total.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</strong><div style='font-size:.5rem;color:#52727a;margin-top:.3rem;'>(Price includes taxes and booking fees)</div>`; }

// No tab switching needed - single itineraries panel only
loadData();

// ============================================================================
// ITINERARY DETAILS MODAL - Information Viewing Only
// ============================================================================
let bookingData = null;

async function openBookingModal(itineraryId) {
  const modal = document.getElementById('bookingModal');
  const content = document.getElementById('bookingModalContent');
  
  // Show loading state
  modal.style.display = 'flex';
  content.innerHTML = '<div class="booking-loading"><div class="spinner"></div><p class="loading-text">Loading booking details...</p></div>';
  
  try {
    // Use simplified API endpoint that works with denormalized structure
    const response = await fetch(`/tripko-system/tripko-backend/api/itinerary/booking_details_simple.php?id=${itineraryId}`);
    
    // Check if response is ok
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    // Get text first to debug
    const text = await response.text();
    console.log('API Response:', text);
    
    // Try to parse JSON
    let result;
    try {
      result = JSON.parse(text);
    } catch (parseError) {
      console.error('JSON Parse Error:', parseError);
      throw new Error('Invalid JSON response from server. Check console for details.');
    }
    
    if (!result.success) {
      throw new Error(result.error || 'Failed to load booking details');
    }
    
    // Simplified API returns data directly, not nested
    bookingData = result;
    renderBookingModal();
    
  } catch (error) {
    console.error('Booking modal error:', error);
    content.innerHTML = `
      <div class="booking-loading">
        <p style="color:var(--tripko-coral);font-weight:600;margin-bottom:0.5rem;">Failed to load booking details</p>
        <p style="color:var(--tripko-gray);font-size:0.75rem;margin-bottom:1rem;">${error.message}</p>
        <button onclick="closeBookingModal()" style="padding:0.5rem 1rem;background:var(--tripko-turquoise);color:white;border:none;border-radius:4px;cursor:pointer;font-weight:600;">Close</button>
      </div>`;
  }
}

function closeBookingModal() {
  document.getElementById('bookingModal').style.display = 'none';
  document.body.style.overflow = 'auto';
  bookingData = null;
}

function renderBookingModal() {
  const data = bookingData;
  const content = document.getElementById('bookingModalContent');
  
  // Simplified API structure
  const itinerary = data.itinerary || {};
  const pricing = data.pricing || {};
  const trustSignals = data.trust_signals || {};
  const reviewBreakdown = (data.reviews && data.reviews.breakdown) || {};
  const recentReviews = (data.reviews && data.reviews.recent_reviews) || [];
  
  // Get hero image
  const heroImage = (data.photos && data.photos.length) 
    ? `/tripko-system/uploads/${data.photos[0].image_path}` 
    : itinerary.image_path 
      ? `/tripko-system/uploads/${itinerary.image_path}` 
      : '/tripko-system/tripko-frontend/images/placeholder.jpg';
  
  // Build highlights
  const highlightsHtml = (data.highlights || []).map(h => `
    <div class="highlight-item">
      <div class="highlight-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div class="highlight-text">${h}</div>
    </div>
  `).join('');
  
  // Build what's included
  const includedHtml = (data.whats_included || []).map(item => `
    <li><i class="fa-solid fa-check"></i> ${item}</li>
  `).join('');
  
  // Build what's excluded
  const excludedHtml = (data.whats_excluded || []).map(item => `
    <li><i class="fa-solid fa-xmark"></i> ${item}</li>
  `).join('');
  
  // Build day-by-day timeline
  const daysHtml = (data.days || []).map(day => {
    const itemsHtml = (day.items || []).map(item => `
      <div class="timeline-item">
        <div class="timeline-item-name">${item.name}</div>
        <div class="timeline-item-meta">
          ${item.start_time ? `<span><i class="fa-regular fa-clock"></i> ${item.start_time}</span>` : ''}
          ${item.duration_minutes ? `<span><i class="fa-regular fa-hourglass-half"></i> ${item.duration_minutes} mins</span>` : ''}
        </div>
        ${item.notes ? `<div class="timeline-item-notes">${item.notes}</div>` : ''}
      </div>
    `).join('');
    
    return `
      <div class="timeline-day">
        <div class="timeline-day-marker">${day.day}</div>
        <div class="timeline-day-title">${day.title || `Day ${day.day}`}</div>
        <div class="timeline-items">${itemsHtml}</div>
      </div>
    `;
  }).join('');
  
  // Build FAQs
  const faqsHtml = (data.faqs || []).map((faq, index) => `
    <div class="faq-item" data-faq="${index}">
      <div class="faq-question">
        <span>${faq.question}</span>
        <div class="faq-icon"><i class="fa-solid fa-chevron-down"></i></div>
      </div>
      <div class="faq-answer">
        <div class="faq-answer-content">${faq.answer}</div>
      </div>
    </div>
  `).join('');
  
  // Build review breakdown
  const totalReviews = reviewBreakdown.total || 0;
  const avgRating = reviewBreakdown.average || 0;
  const distribution = reviewBreakdown.distribution || [];
  const reviewBarsHtml = distribution.map(item => {
    return `
      <div class="review-bar-row">
        <div class="review-bar-label">${item.stars} ★</div>
        <div class="review-bar-track">
          <div class="review-bar-fill" style="width: ${item.percentage}%"></div>
        </div>
        <div class="review-bar-count">${item.count}</div>
      </div>
    `;
  }).join('');
  
  // Trust badges
  const trustBadgesHtml = [];
  if (trustSignals.mobile_ticket) trustBadgesHtml.push('<div class="trust-badge"><i class="fa-solid fa-mobile-screen-button"></i> Mobile Ticket</div>');
  if (trustSignals.instant_confirmation) trustBadgesHtml.push('<div class="trust-badge"><i class="fa-solid fa-bolt"></i> Instant Confirmation</div>');
  if (trustSignals.free_cancellation) trustBadgesHtml.push('<div class="trust-badge"><i class="fa-solid fa-rotate-left"></i> Free Cancellation</div>');
  
  const html = `
    <button class="booking-modal-close" onclick="closeBookingModal()">
      <i class="fa-solid fa-xmark"></i>
    </button>
    
    <div class="booking-hero">
      <img src="${heroImage}" alt="${itinerary.name}" class="booking-hero-image">
      <h1 class="booking-hero-title">${itinerary.name}</h1>
      ${data.photos && data.photos.length > 1 ? `<div class="booking-hero-photo-badge"><i class="fa-regular fa-images"></i> ${data.photos.length} Photos</div>` : ''}
    </div>
    
    <div class="booking-info-bar">
      <div class="booking-info-item">
        <div class="booking-info-icon"><i class="fa-regular fa-clock"></i></div>
        <div class="booking-info-text">
          <div class="booking-info-label">Duration</div>
          <div class="booking-info-value">${itinerary.total_days || 1} Day${itinerary.total_days > 1 ? 's' : ''}</div>
        </div>
      </div>
      
      <div class="booking-info-item">
        <div class="booking-info-icon"><i class="fa-solid fa-list"></i></div>
        <div class="booking-info-text">
          <div class="booking-info-label">Activities</div>
          <div class="booking-info-value">${itinerary.total_items || 0} Stops</div>
        </div>
      </div>
      
      <div class="booking-info-item">
        <div class="booking-info-icon"><i class="fa-solid fa-location-dot"></i></div>
        <div class="booking-info-text">
          <div class="booking-info-label">Location</div>
          <div class="booking-info-value">${itinerary.town || 'Pangasinan'}</div>
        </div>
      </div>
      
      ${trustBadgesHtml.length ? `<div class="booking-trust-badges">${trustBadgesHtml.join('')}</div>` : ''}
    </div>
    
    <div class="booking-layout">
      <div class="booking-content">
        
        ${data.highlights && data.highlights.length ? `
        <div class="booking-section">
          <h2 class="booking-section-title"><i class="fa-solid fa-sparkles"></i> Highlights</h2>
          <div class="highlights-grid">${highlightsHtml}</div>
        </div>
        ` : ''}
        
        ${itinerary.description ? `
        <div class="booking-section">
          <h2 class="booking-section-title"><i class="fa-solid fa-align-left"></i> About</h2>
          <p style="font-size: 0.85rem; line-height: 1.6; color: var(--tripko-gray);">${itinerary.description}</p>
        </div>
        ` : ''}
        
        ${data.whats_included && data.whats_included.length || data.whats_excluded && data.whats_excluded.length ? `
        <div class="booking-section">
          <h2 class="booking-section-title"><i class="fa-solid fa-list-check"></i> What's Included</h2>
          <div class="included-excluded-grid">
            ${data.whats_included && data.whats_included.length ? `
            <div class="included-box">
              <div class="box-title"><i class="fa-solid fa-check"></i> Included</div>
              <ul class="item-list">${includedHtml}</ul>
            </div>
            ` : ''}
            ${data.whats_excluded && data.whats_excluded.length ? `
            <div class="excluded-box">
              <div class="box-title"><i class="fa-solid fa-xmark"></i> Not Included</div>
              <ul class="item-list">${excludedHtml}</ul>
            </div>
            ` : ''}
          </div>
        </div>
        ` : ''}
        
        ${data.days && data.days.length ? `
        <div class="booking-section">
          <h2 class="booking-section-title"><i class="fa-solid fa-route"></i> Day-by-Day Itinerary</h2>
          
          <div id="itineraryRouteMap" style="width: 100%; height: 350px; margin-bottom: var(--space-xl); border: 1px solid var(--color-border);"></div>
          
          <div class="itinerary-timeline">${daysHtml}</div>
        </div>
        ` : ''}
        
        ${totalReviews > 0 ? `
        <div class="booking-section">
          <h2 class="booking-section-title"><i class="fa-solid fa-star"></i> Reviews</h2>
          <div class="review-breakdown">
            <div class="review-score-box">
              <div class="review-score-number">${parseFloat(avgRating).toFixed(1)}</div>
              <div class="review-score-stars">★★★★★</div>
              <div class="review-score-label">${totalReviews} review${totalReviews === 1 ? '' : 's'}</div>
            </div>
            <div class="review-bars">${reviewBarsHtml}</div>
          </div>
          
          ${recentReviews.length > 0 ? `
          <div class="review-cards">
            ${recentReviews.map(review => `
              <div class="review-card">
                <div class="review-card-header">
                  <div class="review-card-avatar">
                    ${review.reviewer_name ? review.reviewer_name.charAt(0).toUpperCase() : 'U'}
                  </div>
                  <div class="review-card-info">
                    <div class="review-card-name">${review.reviewer_name || 'Anonymous'}</div>
                    <div class="review-card-date">${new Date(review.review_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</div>
                  </div>
                  <div class="review-card-rating">
                    ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                  </div>
                </div>
                ${review.review_text ? `
                <div class="review-card-text">${review.review_text}</div>
                ` : ''}
              </div>
            `).join('')}
          </div>
          ` : ''}
        </div>
        ` : ''}
        
        ${data.faqs && data.faqs.length ? `
        <div class="booking-section">
          <h2 class="booking-section-title"><i class="fa-solid fa-circle-question"></i> Frequently Asked Questions</h2>
          <div class="faq-list">${faqsHtml}</div>
        </div>
        ` : ''}
        
      </div>
      
      <div class="booking-widget">
        <div class="widget-price-header">
          <div class="widget-price-from">Starting From</div>
          <div class="widget-price-amount">
            <span class="widget-price-currency">₱</span>${parseFloat(pricing.base_price || 0).toLocaleString('en-PH', {minimumFractionDigits: 0, maximumFractionDigits: 0})}
          </div>
          <div class="widget-price-per">per person</div>
          ${pricing.price_note ? `<div style="font-size: 0.65rem; color: var(--tripko-gray); margin-top: 0.5rem;">${pricing.price_note}</div>` : ''}
        </div>
        
        <div style="margin-top: 1.5rem; padding: 1rem; background: linear-gradient(135deg, rgba(0, 184, 169, 0.08), rgba(72, 202, 228, 0.08)); border-radius: var(--radius-md); border: 1px solid rgba(0, 184, 169, 0.15);">
          <h3 style="margin: 0 0 0.5rem; font-size: 0.85rem; font-weight: 700; color: var(--tripko-ocean);">
            <i class="fa-solid fa-building"></i> Book with Tourism Office
          </h3>
          <p style="margin: 0; font-size: 0.7rem; line-height: 1.4; color: var(--tripko-gray);">
            Visit the ${itinerary.town || 'local'} Tourism Office to inquire about availability and make reservations for this itinerary.
          </p>
        </div>
        
        ${data.meeting_point && data.meeting_point.name ? `
        <div style="margin-top: 1rem; padding: 0.8rem; background: var(--tripko-sand); border-radius: var(--radius-md);">
          <div style="font-size: 0.65rem; font-weight: 700; color: var(--tripko-ocean); margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.5px;">
            <i class="fa-solid fa-location-dot"></i> Meeting Point
          </div>
          <div style="font-size: 0.75rem; color: var(--tripko-dark); font-weight: 600;">${data.meeting_point.name}</div>
          ${data.meeting_point.instructions ? `<div style="font-size: 0.7rem; color: var(--tripko-gray); margin-top: 0.3rem;">${data.meeting_point.instructions}</div>` : ''}
        </div>
        ` : ''}
      </div>
    </div>
  `;
  
  content.innerHTML = html;
  
  // Attach event listeners
  attachBookingEventListeners();
  
  // Initialize route map
  initializeRouteMap();
}

function initializeRouteMap() {
  const mapContainer = document.getElementById('itineraryRouteMap');
  if (!mapContainer || typeof L === 'undefined') return;
  
  // Wait a bit for container to be rendered
  setTimeout(() => {
    const data = bookingData;
    if (!data || !data.days || !data.days.length) return;
    
    // Create map
    const map = L.map('itineraryRouteMap').setView([16.0, 120.3], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);
    
    // Collect all spots with coordinates
    const spots = [];
    let dayNumber = 1;
    let itemIndex = 0;
    
    data.days.forEach(day => {
      day.items.forEach((item, index) => {
        // Generate demo coordinates around Bolinao area (16.0°N, 119.9°E)
        // Spread items in a route-like pattern
        const baseLat = 16.0 + (itemIndex * 0.015);
        const baseLng = 119.9 + (itemIndex * 0.02);
        
        spots.push({
          name: item.name,
          lat: baseLat + (Math.random() * 0.01 - 0.005),
          lng: baseLng + (Math.random() * 0.01 - 0.005),
          time: item.start_time,
          duration: item.duration_minutes,
          day: dayNumber,
          travelTime: item.travel_time
        });
        
        itemIndex++;
      });
      dayNumber++;
    });
    
    // Add markers with numbers
    spots.forEach((spot, i) => {
      const marker = L.marker([spot.lat, spot.lng], {
        icon: L.divIcon({
          className: 'route-marker',
          html: `<div style="background: var(--color-primary); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.75rem; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">${i + 1}</div>`,
          iconSize: [30, 30],
          iconAnchor: [15, 15]
        })
      }).addTo(map);
      
      marker.bindPopup(`
        <div style="font-size: 0.8125rem; line-height: 1.6;">
          <strong style="display: block; margin-bottom: 0.25rem;">${spot.name}</strong>
          ${spot.time ? `<div style="color: var(--color-secondary);"><i class="fa-regular fa-clock"></i> ${spot.time}</div>` : ''}
          ${spot.duration ? `<div style="color: var(--color-secondary);"><i class="fa-regular fa-hourglass-half"></i> ${spot.duration} mins</div>` : ''}
          ${spot.travelTime && i > 0 ? `<div style="color: var(--color-accent); margin-top: 0.25rem;"><i class="fa-solid fa-car"></i> ${spot.travelTime} mins from previous</div>` : ''}
        </div>
      `);
    });
    
    // Draw route lines
    if (spots.length > 1) {
      const routeCoords = spots.map(s => [s.lat, s.lng]);
      L.polyline(routeCoords, {
        color: 'var(--color-accent)',
        weight: 3,
        opacity: 0.7,
        dashArray: '10, 5'
      }).addTo(map);
    }
    
    // Fit bounds to show all markers
    if (spots.length > 0) {
      const bounds = L.latLngBounds(spots.map(s => [s.lat, s.lng]));
      map.fitBounds(bounds, { padding: [30, 30] });
    }
    
    // Fix map rendering
    setTimeout(() => map.invalidateSize(), 100);
  }, 100);
}

function attachBookingEventListeners() {
  // FAQ accordion
  document.querySelectorAll('.faq-item').forEach(item => {
    const question = item.querySelector('.faq-question');
    question.addEventListener('click', () => {
      const isActive = item.classList.contains('active');
      // Close all
      document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
      // Open clicked if wasn't active
      if (!isActive) item.classList.add('active');
    });
  });
}

// Add booking button to itinerary cards
function renderItineraryCards(){
  const wrap=document.getElementById('itineraryCards');
  if(!wrap) return;
  const list = filteredItineraries();
  if(!list.length){
    wrap.innerHTML='<div style="font-size:.65rem;color:#446068;">No public itineraries yet.</div>';
    return;
  }
  const sort=document.getElementById('itinerarySort').value;
  if(sort==='days_desc') list.sort((a,b)=> b.total_days - a.total_days);
  else if(sort==='days_asc') list.sort((a,b)=> a.total_days - b.total_days);
  else if(sort==='newest') list.sort((a,b)=> b.id - a.id);
  wrap.innerHTML = list.map(it=>{
    let img = it.image || '/tripko-system/tripko-frontend/images/placeholder.jpg';
    if(img.startsWith('/uploads/')){
      const fileOnly=img.split('/').pop();
      img='/tripko-system/uploads/'+encodeURIComponent(fileOnly);
    }
    const rating = (it.avg_rating && it.review_count)? `<div style='position:absolute;top:6px;left:6px;background:rgba(15,47,53,.85);color:#fff;font-size:.55rem;font-weight:700;padding:.25rem .4rem;border-radius:10px;display:flex;align-items:center;gap:.25rem;'><i class='fa-solid fa-star' style='color:#f6b400;'></i>${Number(it.avg_rating).toFixed(1)}<span style='opacity:.75;'>(${it.review_count})</span></div>`:'';
    return `<div class=\"itinerary-card\" style=\"position:relative;display:flex;flex-direction:column;gap:.5rem;border:1px solid #dfe6e8;border-radius:20px;overflow:hidden;background:#fff;box-shadow:var(--shadow-sm);\">
      <div style=\"position:relative;aspect-ratio:4/3;background:#eef2f3;background-image:url('${img}');background-size:cover;background-position:center;\">${rating}</div>
      <div style=\"padding:.65rem .75rem .8rem;display:flex;flex-direction:column;gap:.45rem;\">
        <h5 style=\"margin:0;font-size:.8rem;font-weight:800;color:#0f2f35;\">${it.title}</h5>
        <div style=\"display:flex;gap:.4rem;flex-wrap:wrap;align-items:center;font-size:.55rem;font-weight:600;letter-spacing:.5px;color:#45626b;\">
          <span class=\"itinerary-days-badge\">${it.total_days} DAY${it.total_days===1?'':'S'}</span>
          <span>${it.town_name||''}</span>
        </div>
        <div style=\"display:flex;gap:.4rem;margin-top:.2rem;\">
          <button onclick=\"event.stopPropagation(); openItineraryModal(${it.id});\" class=\"btn\" style=\"flex:1;padding:.45rem;font-size:.55rem;background:var(--btn-secondary);color:var(--text-dark);\">Overview</button>
          <button onclick=\"event.stopPropagation(); openBookingModal(${it.id});\" class=\"btn\" style=\"flex:1;padding:.45rem;font-size:.55rem;background:linear-gradient(135deg, #00b8a9, #48cae4);color:#fff;font-weight:700;\">Full Details</button>
        </div>
      </div>
    </div>`;
  }).join('');
}

// Override the old card click listener - now only buttons trigger actions
</script>

<!-- Booking Modal -->
<div id="bookingModal" class="booking-modal">
  <div class="booking-modal-content" id="bookingModalContent">
    <!-- Content will be dynamically loaded -->
  </div>
</div>

</body>
</html>