<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../tripko-backend/check_session.php';
if (!isLoggedIn()) { header('Location: SignUp_LogIn_Form.php'); exit; }
if (isAdmin()) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TripKo Pangasinan - Discover Pangasinan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" onerror="this.onerror=null;this.href='/tripko-system/tripko-frontend/css/fa-local.css'" />
    <link rel="stylesheet" href="/tripko-system/tripko-frontend/css/user-shared.css" />
    <link rel="stylesheet" href="/tripko-system/tripko-frontend/css/homepage.css" />
</head>
<body>
<?php include_once __DIR__ . '/includes/navbar.php'; if(function_exists('renderNavbar')) renderNavbar(); ?>

<section class="hero">
    <div class="hero-slider" id="heroSlider"></div>
    <div class="hero-bg" aria-hidden="true"></div>
    <div class="hero-content">
        <h1 class="hero-title">Discover Pangasinan</h1>
        <p class="hero-subtitle">Uncover hidden gems, breathtaking landscapes, and unforgettable experiences in the Pearl of the Orient</p>
        <div class="search-widget">
            <div class="search-tabs">
                <button class="search-tab active" data-tab="places">PLACES</button>
                <button class="search-tab" data-tab="experiences">EXPERIENCES</button>
                <button class="search-tab" data-tab="hotels">HOTELS</button>
                <button class="search-tab" data-tab="restaurants">FOOD</button>
            </div>
            <div class="search-form">
                <input id="heroSearch" type="text" class="search-input" placeholder="Where do you want to go?" />
                <button class="search-btn" id="searchBtn"><i class="fas fa-search"></i> Search</button>
            </div>
            <div class="quick-actions">
                <a class="quick-action" href="map.php"><i class="fas fa-map"></i> Map</a>
                <a class="quick-action" href="user side/things-to-do.php"><i class="fas fa-route"></i> Things to Do</a>
                <a class="quick-action" href="places-to-go.php"><i class="fas fa-camera"></i> Spots</a>
                <a class="quick-action" href="festivals-to-go.php"><i class="fas fa-calendar"></i> Festivals</a>
            </div>
        </div>
    </div>
</section>

<section class="section alt" id="destinations">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Popular Destinations</h2>
            <p class="section-subtitle">Discover the most loved places in Pangasinan</p>
        </div>
        <div class="destinations-grid" id="destinationsGrid"></div>
    </div>
</section>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>DISCOVER</h3>
            <a href="places-to-go.php">Tourist Spots</a>
            <a href="festivals-to-go.php">Festivals</a>
            <a href="churches-to-go.php">Churches</a>
            <a href="caves-to-go.php">Caves</a>
        </div>
        <div class="footer-section">
            <h3>PLAN</h3>
            <a href="user side/things-to-do.php">Things to Do</a>
            <a href="map.php">Interactive Map</a>
            <a href="islands-to-go.php">Islands</a>
            <a href="waterfalls-to-go.php">Waterfalls</a>
        </div>
        <div class="footer-section">
            <h3>SUPPORT</h3>
            <a href="user side/contact-us.php">Contact Us</a>
            <a href="../about-us.php">About TripKo</a>
            <a href="#">Help Center</a>
            <a href="#">Privacy Policy</a>
        </div>
        <div class="footer-section">
            <h3>ACCOUNT</h3>
            <a href="../SignUp_LogIn_Form.php">Profile</a>
            <a href="#">Saved</a>
            <a href="#">Preferences</a>
            <a href="/tripko-system/tripko-backend/logout.php">Logout</a>
        </div>
    </div>
    <div class="footer-bottom">&copy; 2025 TripKo Pangasinan. All rights reserved.</div>
</footer>

<script>
// Handle tab switching
document.querySelectorAll('.search-tab').forEach(tab => tab.addEventListener('click', () => {
    document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    const ph = {
        places: 'Where do you want to go?',
        experiences: 'What do you want to do?',
        hotels: 'Find hotels and accommodations...',
        restaurants: 'Discover local cuisine...'
    };
    document.getElementById('heroSearch').placeholder = ph[tab.dataset.tab];
}));

// Search actions
document.getElementById('heroSearch').addEventListener('keydown', e => { if(e.key === 'Enter') performSearch(); });
document.getElementById('searchBtn').addEventListener('click', performSearch);
function performSearch(){
    const q = document.getElementById('heroSearch').value.trim();
    const tab = document.querySelector('.search-tab.active').dataset.tab;
    if(!q) return; // optionally ignore empty
    if(tab === 'places') window.location.href = `places-to-go.php?search=${encodeURIComponent(q)}`;
    else if(tab === 'experiences') window.location.href = `user side/things-to-do.php?search=${encodeURIComponent(q)}`;
    else window.location.href = `map.php?search=${encodeURIComponent(q)}`;
}

// Load sample destinations (reusing markers endpoint)
async function loadDestinations(){
    try {
        const res = await fetch('../../tripko-backend/api/map/markers.php');
        if(!res.ok) return;
        const data = await res.json();
            const spots = (data.features||[]).filter(f => f.properties?.category === 'spot').slice(0,6);
        const grid = document.getElementById('destinationsGrid');
            const normalizeImage = (raw) => {
                if(!raw) return 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=800&q=70';
                // Already absolute http(s)
                if(/^https?:\/\//i.test(raw)) return raw;
                // Ensure leading slash
                let path = raw.startsWith('/') ? raw : '/' + raw;
                // If path like /uploads/... prepend /tripko-system
                if(path.startsWith('/uploads/')) path = '/tripko-system' + path;
                // If missing /uploads/ assume it's just filename in uploads dir
                if(!/\/uploads\//.test(path)) path = '/tripko-system/uploads/' + path.replace(/^\//,'');
                return path;
            };
            grid.innerHTML = spots.map(s => {
                const p = s.properties;
                const img = normalizeImage(p.image);
            return `<div class="destination-card" onclick="location.href='places-to-go.php?id=${p.id}'">
                <div class="card-image" style="background-image:url('${img}')"><span class="card-badge">POPULAR</span></div>
                <div class="card-content">
                    <h3 class="card-title">${p.name}</h3>
                    <p class="card-location"><i class="fas fa-map-marker-alt"></i>${p.municipality||'Pangasinan'}</p>
                    <p class="card-description">${(p.description||'Discover this amazing destination in Pangasinan.').slice(0,90)}...</p>
                    <div class="card-stats">
                        <span class="stat"><i class="fas fa-star"></i>${(4+Math.random()).toFixed(1)}*</span>
                        <span class="stat"><i class="fas fa-camera"></i>${Math.floor(Math.random()*400)+80} photos</span>
                    </div>
                </div>
            </div>`;
        }).join('');
    } catch(err) { console.error('Markers load failed', err); }
}
document.addEventListener('DOMContentLoaded', loadDestinations);

// Hero dynamic background slider (images + optional video)
const heroMedia = [
    {type:'image', src:'/tripko-system/uploads/6813937f65065_hundred-islands-park.jpg', alt:'Hundred Islands National Park'},
    {type:'image', src:'/tripko-system/uploads/681394705bcd7_bolinao3.jpg', alt:'Bolinao Rock Formation'},
    {type:'image', src:'/tripko-system/uploads/681394af174f6_abagatanen-beach.jpg', alt:'Abagatanen Beach Golden Hour'},
    {type:'image', src:'/tripko-system/uploads/681b49ec44a60_bolinao.png', alt:'Bolinao Seascape'},
    {type:'image', src:'/tripko-system/uploads/681b4bb708c82_st.vincent.jpg', alt:'Historic Church Pangasinan'}
];

function initHeroSlider(){
    const container = document.getElementById('heroSlider');
    if(!container) return;
    // Build slides
    container.innerHTML = heroMedia.map((m,i)=>{
        if(m.type==='video') {
            return `<div class="hero-slide${i===0?' active':''}" data-index="${i}"><video src="${m.src}" autoplay muted loop playsinline></video></div>`;
        }
        return `<div class="hero-slide${i===0?' active':''}" data-index="${i}" style="background-image:url('${m.src}')" role="img" aria-label="${m.alt||''}"></div>`;
    }).join('');
    console.log('Hero slides injected:', container.children.length);
    let current = 0;
    const slides = Array.from(container.querySelectorAll('.hero-slide'));
    if(slides.length < 2) return; // nothing to rotate
    setInterval(()=>{
        slides[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
    }, 8000);
}
document.addEventListener('DOMContentLoaded', initHeroSlider);

// Graceful fallback if any hero image fails
document.addEventListener('error', e => {
    const t = e.target;
    if(t.classList && t.classList.contains('hero-slide')){
        t.style.backgroundImage = "url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1500&q=60')";
    }
}, true);

// Explain 404 error in console for debugging
window.addEventListener('error', e => {
    if(e?.target?.tagName === 'IMG' || (e?.target?.tagName === 'VIDEO')){
        console.warn('Media failed to load:', e.target.src);
    }
}, true);
</script>
</body>
</html>
