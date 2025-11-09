<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php?error=session');
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile - TripKo Pangasinan</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/tripko-system/tripko-frontend/css/profile.css" />
</head>
<body>
<?php include_once __DIR__ . '/../includes/navbar.php'; if(function_exists('renderNavbar')) renderNavbar(); ?>

<!-- Dark Mode Toggle -->
<button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode" title="Toggle theme">
  <i class="bx bxs-moon"></i>
</button>

<div class="profile-layout">
  <aside class="profile-sidebar" aria-label="User profile navigation">
    <div class="avatar-wrapper">
      <div class="avatar-circle" id="profileInitials" data-editable="true" title="Click to upload avatar">--</div>
      <input type="file" id="user_profile_photo" name="user_profile_photo" accept="image/*" style="display:none;" />
      <div class="avatar-name" id="profileFullName">Loading...</div>
      <div class="avatar-username" id="profileUsername">@username</div>
    </div>
    <div class="stats-row" id="statsRow">
      <div class="stat-card"><div class="stat-num" id="statTrips">0</div><div class="stat-label">TRIPS</div></div>
      <div class="stat-card"><div class="stat-num" id="statPlaces">0</div><div class="stat-label">PLACES</div></div>
      <div class="stat-card"><div class="stat-num" id="statReviews">0</div><div class="stat-label">REVIEWS</div></div>
    </div>
    <nav class="nav-section" id="profileNav" role="tablist" aria-orientation="vertical">
      <button class="profile-nav-btn active" data-target="dashboard" role="tab" aria-selected="true"><i class="fa-solid fa-chart-line icon"></i><span>Dashboard</span></button>
      <button class="profile-nav-btn" data-target="itineraries" role="tab"><i class="fa-solid fa-route icon"></i><span>Itineraries</span></button>
      <button class="profile-nav-btn" data-target="saved" role="tab"><i class="fa-solid fa-heart icon"></i><span>Saved Places</span></button>
      <button class="profile-nav-btn" data-target="reviews" role="tab"><i class="fa-solid fa-star icon"></i><span>My Reviews</span></button>
      <button class="profile-nav-btn" data-target="settings" role="tab"><i class="fa-solid fa-gear icon"></i><span>Settings</span></button>
      <button class="profile-nav-btn" onclick="logout()"><i class="fa-solid fa-right-from-bracket icon"></i><span>Logout</span></button>
    </nav>
  </aside>
  <main class="profile-main" id="profileMain">
    <section class="panel active" id="panel-dashboard" data-panel="dashboard">
      <div class="panel-header">
        <h2 class="panel-title"><span class="emoji">👋</span> <span id="welcomeHeading">Welcome back!</span></h2>
  <a href="/tripko-system/tripko-frontend/file_html/user%20side/map.html" class="btn" id="planTripBtn"><i class="fa-solid fa-plus"></i> Plan Trip</a>
      </div>
      <div class="metric-grid">
        <div class="metric-card"><div class="metric-icon"><i class="fa-solid fa-map-location-dot"></i></div><div class="metric-label">Places Visited</div><div class="metric-value" id="dashPlaces">0</div><div class="metric-sub">Tracked</div></div>
        <div class="metric-card"><div class="metric-icon"><i class="fa-solid fa-route"></i></div><div class="metric-label">Itineraries</div><div class="metric-value" id="dashItineraries">0</div><div class="metric-sub">Created</div></div>
        <div class="metric-card"><div class="metric-icon"><i class="fa-solid fa-camera"></i></div><div class="metric-label">Photos Shared</div><div class="metric-value" id="dashPhotos">0</div><div class="metric-sub">Uploaded</div></div>
        <div class="metric-card"><div class="metric-icon"><i class="fa-solid fa-star"></i></div><div class="metric-label">Reviews</div><div class="metric-value" id="dashReviews">0</div><div class="metric-sub">Submitted</div></div>
      </div>
      <div class="activity-placeholder" id="recentActivity">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <h4>No Recent Activity</h4>
        <p style="font-size:.8rem;">Your latest trips, reviews and saves will appear here once you start exploring.</p>
  <a href="/tripko-system/tripko-frontend/file_html/user%20side/places-to-go.php" class="btn outline" style="margin-top:.4rem;"><i class="fa-solid fa-compass"></i> Explore Now</a>
      </div>
    </section>
    <section class="panel" id="panel-itineraries" data-panel="itineraries">
      <div class="panel-header"><h2 class="panel-title"><span class="emoji">🗺️</span> My Itineraries</h2><button class="btn" onclick="showCreateItineraryModal()"><i class="fa-solid fa-plus"></i> New Itinerary</button></div>
      <div class="simple-grid" id="itinerariesGrid">
        <div class="simple-card"><h4>Beach Adventure (Sample)</h4><p>3-day coastal exploration across iconic Pangasinan spots.</p></div>
      </div>
    </section>
    <section class="panel" id="panel-saved" data-panel="saved">
  <div class="panel-header"><h2 class="panel-title"><span class="emoji">❤️</span> Saved Places</h2><a href="/tripko-system/tripko-frontend/file_html/user%20side/places-to-go.php" class="btn outline"><i class="fa-solid fa-magnifying-glass"></i> Discover More</a></div>
      <div class="simple-grid" id="savedGrid"><div class="simple-card"><h4>Hundred Islands (Sample)</h4><p>Island hopping biodiversity and ocean vistas.</p></div></div>
    </section>
    <section class="panel" id="panel-reviews" data-panel="reviews">
      <div class="panel-header"><h2 class="panel-title"><span class="emoji">⭐</span> My Reviews</h2><button class="btn" onclick="showWriteReviewModal()"><i class="fa-solid fa-plus"></i> Write Review</button></div>
      <div id="reviewsList"><div class="review-item"><div class="review-top"><div class="review-place">Hundred Islands</div><div class="review-rating">★★★★★</div></div><div class="review-text">Amazing island chain experience – crystal water and great guides.</div><div class="review-date">2 weeks ago</div></div></div>
    </section>
    <section class="panel" id="panel-settings" data-panel="settings">
      <div class="panel-header"><h2 class="panel-title"><span class="emoji">⚙️</span> Account Settings</h2></div>
      <form id="profileForm" enctype="multipart/form-data" style="max-width:860px;">
        <div class="form-grid">
          <div class="form-group"><label class="form-label" for="first_name">First Name</label><input class="input" type="text" id="first_name" name="first_name" placeholder="First name" /></div>
          <div class="form-group"><label class="form-label" for="last_name">Last Name</label><input class="input" type="text" id="last_name" name="last_name" placeholder="Last name" /></div>
          <div class="form-group"><label class="form-label" for="email">Email</label><input class="input" type="email" id="email" name="email" placeholder="you@example.com" /></div>
          <div class="form-group"><label class="form-label" for="contact_number">Contact Number</label><input class="input" type="text" id="contact_number" name="contact_number" placeholder="09xx xxx xxxx" /></div>
          <div class="form-group"><label class="form-label" for="user_profile_dob">Date of Birth</label><input class="input" type="date" id="user_profile_dob" name="user_profile_dob" /></div>
        </div>
        <div class="form-group" style="margin-top:.5rem;"><label class="form-label" for="bio">Bio</label><textarea id="bio" name="bio" class="input" placeholder="Tell us about yourself..."></textarea><div class="helper">A short description that represents you (optional)</div></div>
        <div style="display:flex;gap:.8rem;align-items:center;margin-top:1.1rem;flex-wrap:wrap;">
          <button type="submit" class="btn" id="saveProfileBtn"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
          <button type="button" class="btn outline" onclick="loadProfile()"><i class="fa-solid fa-rotate"></i> Reset</button>
          <span id="saveStatus" style="font-weight:600;font-size:.85rem;"></span>
        </div>
      </form>

      <hr style="margin:2rem 0;opacity:.25;">

      <div style="max-width:860px;">
        <h3 style="margin:0 0 1rem 0;color:#10323c;">Change Password</h3>
        <button class="btn" id="openChangePw"><i class="fa-solid fa-key"></i> Change Password</button>
        <div id="changePwModal" class="modal">
          <div class="modal-content">
            <div class="modal-header"><h3 class="modal-title">Change Password</h3><button class="modal-close" id="cpwClose">&times;</button></div>
            <div id="cpwStepSend" style="display:block;">
              <p>Well send a 6-digit code to your email to confirm this action.</p>
              <div style="display:flex;gap:.8rem;justify-content:flex-end;">
                <button class="btn" id="cpwSendCode">Send Code</button>
              </div>
              <div id="cpwStatus" style="min-height:20px;font-size:.9rem;margin-top:8px"></div>
            </div>
            <form id="cpwForm" style="display:none;margin-top:10px;">
              <div class="form-grid">
                <div class="form-group"><label class="form-label" for="cpw_current">Current Password</label><input class="input" type="password" id="cpw_current" name="current_password" autocomplete="current-password" required></div>
                <div class="form-group"><label class="form-label" for="cpw_new">New Password</label><input class="input" type="password" id="cpw_new" name="new_password" autocomplete="new-password" required></div>
                <div class="form-group"><label class="form-label" for="cpw_confirm">Confirm New Password</label><input class="input" type="password" id="cpw_confirm" name="confirm_password" autocomplete="new-password" required></div>
                <div class="form-group"><label class="form-label" for="cpw_otp">Verification Code</label><input class="input" type="text" id="cpw_otp" name="otp" maxlength="6" inputmode="numeric" placeholder="6-digit code" required></div>
              </div>
              <div style="display:flex;gap:.8rem;align-items:center;margin-top:1.1rem;flex-wrap:wrap;">
                <button type="submit" class="btn" id="cpwSubmit">Update Password</button>
                <button type="button" class="btn outline" id="cpwResend">Resend Code</button>
                <span id="cpwStatus2" style="font-weight:600;font-size:.85rem;"></span>
              </div>
              <p style="font-size:.8rem;color:#365057;margin-top:.6rem;">After a successful change, a confirmation email will be sent to your account email.</p>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>

<!-- Create Itinerary Modal -->
<div class="modal" id="createItineraryModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Create New Itinerary</h3>
        <button class="modal-close" onclick="hideCreateItineraryModal()">&times;</button>
      </div>
      <div class="form-group">
        <label class="form-label">Itinerary Name</label>
        <input type="text" class="form-input" placeholder="e.g., Summer Beach Adventure">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-textarea" placeholder="Describe your trip..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Start Date</label>
        <input type="date" class="form-input">
      </div>
      <div class="form-group">
        <label class="form-label">End Date</label>
        <input type="date" class="form-input">
      </div>
      <div style="display: flex; gap: 1rem; justify-content: flex-end;">
        <button class="btn-secondary" onclick="hideCreateItineraryModal()">Cancel</button>
        <button class="btn-primary" onclick="createItinerary()">
          <i class="fas fa-plus"></i>
          Create Itinerary
        </button>
      </div>
    </div>
  </div>

<!-- Write Review Modal -->
<div class="modal" id="writeReviewModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Write a Review</h3>
        <button class="modal-close" onclick="hideWriteReviewModal()">&times;</button>
      </div>
      <div class="form-group">
        <label class="form-label">Place</label>
        <input type="text" class="form-input" placeholder="Search for a place...">
      </div>
      <div class="form-group">
        <label class="form-label">Rating</label>
        <div style="font-size: 1.5rem; color: #fbbf24; margin-top: 0.5rem;">
          <span onclick="setRating(1)">☆</span>
          <span onclick="setRating(2)">☆</span>
          <span onclick="setRating(3)">☆</span>
          <span onclick="setRating(4)">☆</span>
          <span onclick="setRating(5)">☆</span>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Your Review</label>
        <textarea class="form-textarea" placeholder="Share your experience..."></textarea>
      </div>
      <div style="display: flex; gap: 1rem; justify-content: flex-end;">
        <button class="btn-secondary" onclick="hideWriteReviewModal()">Cancel</button>
        <button class="btn-primary" onclick="submitReview()">
          <i class="fas fa-star"></i>
          Submit Review
        </button>
      </div>
    </div>
  </div>

<script>
    const API_BASE = '/tripko-system/tripko-backend/api/profile';
    let currentRating = 0;
    // Tab handling
    function activatePanel(name){
      document.querySelectorAll('[data-panel]').forEach(p=>p.classList.remove('active'));
      const target = document.querySelector(`[data-panel="${name}"]`);
      if(target) target.classList.add('active');
      document.querySelectorAll('.profile-nav-btn').forEach(btn=>{
        if(btn.dataset.target===name){btn.classList.add('active');btn.setAttribute('aria-selected','true');}
        else {btn.classList.remove('active');btn.setAttribute('aria-selected','false');}
      });
      history.replaceState(null,'',`#${name}`);
    }
    function initTabs(){
      document.getElementById('profileNav').addEventListener('click',e=>{
        const btn = e.target.closest('.profile-nav-btn');
        if(!btn || !btn.dataset.target) return;
        activatePanel(btn.dataset.target);
      });
      const hash = window.location.hash.replace('#','');
      if(hash) activatePanel(hash); else activatePanel('dashboard');
    }

    // Modal functions
    function showCreateItineraryModal() {
      document.getElementById('createItineraryModal').classList.add('show');
      document.body.style.overflow = 'hidden';
    }

    function hideCreateItineraryModal() {
      document.getElementById('createItineraryModal').classList.remove('show');
      document.body.style.overflow = '';
    }

    function showWriteReviewModal() {
      document.getElementById('writeReviewModal').classList.add('show');
      document.body.style.overflow = 'hidden';
    }

    function hideWriteReviewModal() {
      document.getElementById('writeReviewModal').classList.remove('show');
      document.body.style.overflow = '';
    }

    // Rating function
    function setRating(rating) {
      currentRating = rating;
      const stars = document.querySelectorAll('#writeReviewModal span');
      stars.forEach((star, index) => {
        star.textContent = index < rating ? '★' : '☆';
      });
    }

    // Action functions
    function createItinerary() {
      alert('Itinerary created successfully! You can now add destinations to your trip.');
      hideCreateItineraryModal();
    }

    function submitReview() {
      if (currentRating === 0) {
        alert('Please select a rating.');
        return;
      }
      alert('Review submitted successfully! Thank you for sharing your experience.');
      hideWriteReviewModal();
    }

    function removeSavedPlace(button) {
      if (confirm('Remove this place from your saved list?')) {
        button.closest('.saved-card').remove();
      }
    }

    function logout() {
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = '/tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php';
      }
    }

    // Close modals when clicking outside
    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
      }
    });

    async function loadProfile(){
      try {
        const res = await fetch(`${API_BASE}/get.php`, {headers:{'Accept':'application/json'}});
        if(!res.ok) throw new Error('HTTP '+res.status);
        const data = await res.json();
        if(!data.success) throw new Error(data.message||'Failed loading profile');
        const p = data.profile;
        const initialsEl = document.getElementById('profileInitials');
        initialsEl.textContent = p.initials || '--';
        document.getElementById('profileFullName').textContent = (p.first_name||'') + (p.last_name? ' '+p.last_name:'');
        document.getElementById('profileUsername').textContent = '@'+(p.username||'user');
        document.getElementById('welcomeHeading').textContent = 'Welcome back, ' + (p.first_name || p.username) + '!';
        // Form fields
        ['first_name','last_name','email','contact_number','user_profile_dob','bio'].forEach(id=>{
          const el = document.getElementById(id);
          if(!el) return;
          let val = p[id];
          if(id==='user_profile_dob'){
            if(!val || val==='0000-00-00') { el.value=''; return; }
            // Ensure format yyyy-mm-dd; if not, attempt to parse
            if(!/^\d{4}-\d{2}-\d{2}$/.test(val)){
              try { const d = new Date(val); if(!isNaN(d.getTime())) { val = d.toISOString().slice(0,10); } else { val=''; } } catch(e){ val=''; }
            }
          }
          if(val !== null && val !== undefined) el.value = val || '';
        });
        // avatar preview if photo path provided
        if(p.user_profile_photo){
          const cacheBust = Date.now();
          initialsEl.style.backgroundImage = `url(/tripko-system/uploads/${p.user_profile_photo}?v=${cacheBust})`;
          initialsEl.style.backgroundSize = 'cover';
          initialsEl.style.backgroundPosition = 'center';
          initialsEl.textContent='';
        } else {
          initialsEl.removeAttribute('style');
        }
      } catch(err){
        console.error('Profile load error', err);
        const h = document.getElementById('welcomeHeading');
        if(h) h.textContent = 'Welcome back! (profile load failed)';
      }
    }

    document.getElementById('profileForm').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = document.getElementById('saveProfileBtn');
      const status = document.getElementById('saveStatus');
      status.textContent = 'Saving...';
      btn.disabled = true; btn.style.opacity='0.6';
      try {
        const fd = new FormData(e.target);
        // Append avatar file manually since the file input lives outside the form element
        const avatarInput = document.getElementById('user_profile_photo');
        if(avatarInput && avatarInput.files && avatarInput.files[0]){
          fd.append('user_profile_photo', avatarInput.files[0]);
        }
        const res = await fetch(`${API_BASE}/update.php`, {method:'POST', body: fd});
        const data = await res.json();
        if(!res.ok || !data.success) throw new Error(data.message||'Save failed');
        let msg = 'Saved!';
        if (data.email_changed) {
          msg += ' Check your email to confirm the new address.';
        }
        status.textContent = msg;
        // If a new photo was uploaded, optimistically update avatar with cache-busting; then refresh full profile
        if(data.photo){
          const initialsEl = document.getElementById('profileInitials');
          initialsEl.style.backgroundImage = `url(/tripko-system/uploads/${data.photo}?v=${Date.now()})`;
          initialsEl.style.backgroundSize='cover';
          initialsEl.style.backgroundPosition='center';
          initialsEl.textContent='';
        }
        loadProfile();
        setTimeout(()=>{status.textContent='';},2500);
      } catch(err){
        console.error(err);
        status.textContent = 'Error: '+err.message;
        status.style.color = '#dc2626';
        setTimeout(()=>{status.textContent=''; status.removeAttribute('style');},4000);
      } finally {
        btn.disabled=false; btn.style.opacity='';
      }
    });

    // Avatar click -> open file picker
    document.addEventListener('click', e=>{
      if(e.target.id==='profileInitials'){
        document.getElementById('user_profile_photo').click();
      }
    });
    document.getElementById('user_profile_photo').addEventListener('change', e=>{
      const file = e.target.files[0];
      if(file){
        const url = URL.createObjectURL(file);
        const el = document.getElementById('profileInitials');
        el.style.backgroundImage = `url(${url})`;
        el.style.backgroundSize='cover';
        el.style.backgroundPosition='center';
        el.textContent='';
        // Auto-upload avatar immediately
        uploadAvatarInstant(file);
      }
    });

    function showStatus(msg,type='info',persist=false){
      const status = document.getElementById('saveStatus');
      if(!status) return; 
      status.textContent = msg;
      status.style.color = (type==='error')? '#dc2626' : (type==='success'? '#0f766e':'#10323c');
      status.style.transition='opacity .25s';
      status.style.opacity='1';
      if(!persist){
        clearTimeout(status._timer);
        status._timer = setTimeout(()=>{status.style.opacity='0';setTimeout(()=>{status.textContent='';status.removeAttribute('style');},400);},3500);
      }
    }

    async function uploadAvatarInstant(file){
      try {
        showStatus('Uploading photo...','info',true);
        const fd = new FormData();
        fd.append('user_profile_photo', file);
        const res = await fetch(`${API_BASE}/update.php`, {method:'POST', body: fd});
        let dataText = await res.text();
        let data;
        try { data = JSON.parse(dataText); } catch(parseErr){ throw new Error('Non-JSON response: '+dataText.slice(0,120)); }
        if(!res.ok || !data.success) throw new Error(data.message||'Upload failed');
        if(data.photo){
          const el = document.getElementById('profileInitials');
          el.style.backgroundImage = `url(/tripko-system/uploads/${data.photo}?v=${Date.now()})`;
          el.style.backgroundSize='cover';
          el.style.backgroundPosition='center';
          el.textContent='';
        }
        showStatus('Photo updated','success');
        loadProfile();
      } catch(err){
        console.error('Avatar upload error', err);
        showStatus('Photo error: '+err.message,'error');
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      initTabs();
      loadProfile();
      initDarkMode();
    });
  // simple logout reuse
  function logout(){ window.location.href='/tripko-system/tripko-backend/logout.php'; }

  // Dark Mode Toggle
  function initDarkMode() {
    const toggle = document.getElementById('darkModeToggle');
    const savedTheme = localStorage.getItem('tripko-profile-theme');
    
    if (savedTheme === 'dark') {
      document.body.classList.add('dark');
      toggle.innerHTML = '<i class="bx bxs-sun"></i>';
    }
    
    toggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      const isDark = document.body.classList.contains('dark');
      toggle.innerHTML = isDark ? '<i class="bx bxs-sun"></i>' : '<i class="bx bxs-moon"></i>';
      localStorage.setItem('tripko-profile-theme', isDark ? 'dark' : 'light');
    });
  }

  // Change password UX with code
  (function(){
    const openBtn = document.getElementById('openChangePw');
    const modal = document.getElementById('changePwModal');
    const closeBtn = document.getElementById('cpwClose');
    const sendBtn = document.getElementById('cpwSendCode');
    const stepSend = document.getElementById('cpwStepSend');
    const form = document.getElementById('cpwForm');
    const status1 = document.getElementById('cpwStatus');
    const status2 = document.getElementById('cpwStatus2');
    const resendBtn = document.getElementById('cpwResend');
    function open(){ modal.classList.add('show'); document.body.style.overflow='hidden'; stepSend.style.display='block'; form.style.display='none'; status1.textContent=''; status2.textContent=''; }
    function close(){ modal.classList.remove('show'); document.body.style.overflow=''; }
    openBtn.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    modal.addEventListener('click', (e)=>{ if(e.target===modal) close(); });
    sendBtn.addEventListener('click', async ()=>{
      sendBtn.disabled=true; sendBtn.textContent='Sending...'; status1.textContent='Sending code...'; status1.style.color='#10323c';
      try{
        const fd = new FormData(); fd.append('init','1');
        const res = await fetch(`${API_BASE}/change_password.php`, { method:'POST', body: fd });
        const data = await res.json();
        if (res.status===202 && data.twofa==='required'){
          status1.textContent='Code sent. Check your email.'; status1.style.color='#0f766e';
          stepSend.style.display='none'; form.style.display='block';
        } else if (!data.success) {
          throw new Error(data.message||'Failed to send code');
        }
      } catch(err){ status1.textContent = 'Error: '+err.message; status1.style.color='#dc2626'; }
      finally { sendBtn.disabled=false; sendBtn.textContent='Send Code'; }
    });
    resendBtn.addEventListener('click', ()=>{ sendBtn.click(); });
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = document.getElementById('cpwSubmit');
      status2.textContent='Updating password...'; status2.style.color='#10323c'; btn.disabled=true; btn.style.opacity='.6';
      try{
        const fd = new FormData(form);
        const res = await fetch(`${API_BASE}/change_password.php`, { method:'POST', body: fd });
        const data = await res.json();
        if(!res.ok || !data.success) throw new Error(data.message||'Password change failed');
        status2.textContent='Password updated!'; status2.style.color='#0f766e'; form.reset(); setTimeout(()=>{ close(); }, 1000);
      } catch(err){ status2.textContent='Error: '+err.message; status2.style.color='#dc2626'; }
      finally { btn.disabled=false; btn.style.opacity=''; }
    });
  })();
</script>
</body>
</html>