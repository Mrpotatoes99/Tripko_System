<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Things to Do - TripKo Pangasinan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      font-family: 'Inter', system-ui, sans-serif; 
      line-height: 1.6; 
      color: #1a1a1a;
      background: #f8fafc;
    }

    /* Header */
    .header {
      background: white;
      border-bottom: 1px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .nav {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 70px;
    }
    .logo {
      font-size: 1.5rem;
      font-weight: 800;
      color: #00a6b8;
      text-decoration: none;
    }
    .nav-links {
      display: flex;
      gap: 2rem;
      align-items: center;
    }
    .nav-links a {
      text-decoration: none;
      color: #374151;
      font-weight: 500;
      transition: color 0.2s;
    }
    .nav-links a:hover {
      color: #00a6b8;
    }
    .back-btn {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      background: #f1f5f9;
      color: #374151;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
    }
    .back-btn:hover {
      background: #e2e8f0;
    }

    /* Hero Section */
    .hero {
      background: linear-gradient(135deg, #00a6b8, #0f766e);
      color: white;
      padding: 4rem 2rem;
      text-align: center;
    }
    .hero-content {
      max-width: 800px;
      margin: 0 auto;
    }
    .hero-title {
      font-size: clamp(2.5rem, 6vw, 4rem);
      font-weight: 800;
      margin-bottom: 1rem;
      letter-spacing: -1px;
    }
    .hero-subtitle {
      font-size: 1.25rem;
      opacity: 0.9;
      margin-bottom: 2rem;
    }
    .hero-stats {
      display: flex;
      justify-content: center;
      gap: 2rem;
      flex-wrap: wrap;
    }
    .stat {
      text-align: center;
    }
    .stat-number {
      font-size: 2rem;
      font-weight: 800;
    }
    .stat-label {
      opacity: 0.8;
    }

    /* Search & Filter Section */
    .search-section {
      background: white;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .search-container {
      max-width: 1200px;
      margin: 0 auto;
    }
    .search-bar {
      position: relative;
      margin-bottom: 2rem;
    }
    .search-input {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 1rem;
      transition: all 0.2s;
    }
    .search-input:focus {
      outline: none;
      border-color: #00a6b8;
      box-shadow: 0 0 0 3px rgba(0,166,184,0.1);
    }
    .search-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
    }
    
    .filter-tabs {
      display: flex;
      gap: 1rem;
      overflow-x: auto;
      padding-bottom: 0.5rem;
    }
    .filter-tab {
      white-space: nowrap;
      padding: 0.75rem 1.5rem;
      background: #f1f5f9;
      border: 2px solid transparent;
      border-radius: 50px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }
    .filter-tab.active {
      background: #00a6b8;
      color: white;
      border-color: #00a6b8;
    }
    .filter-tab:hover:not(.active) {
      background: #e2e8f0;
    }

    /* Main Content */
    .main-content {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 2rem 4rem;
    }
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }
    .section-title {
      font-size: 2rem;
      font-weight: 700;
      color: #1e293b;
    }
    .view-toggle {
      display: flex;
      background: #f1f5f9;
      border-radius: 8px;
      padding: 0.25rem;
    }
    .view-btn {
      padding: 0.5rem 1rem;
      border: none;
      background: transparent;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .view-btn.active {
      background: white;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* Tours Grid */
    .tours-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 2rem;
      transition: all 0.3s ease;
    }
    .tours-grid.list-view {
      grid-template-columns: 1fr;
    }
    
    .tour-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      transition: all 0.3s ease;
      cursor: pointer;
      border: 2px solid transparent;
    }
    .tour-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.12);
      border-color: #00a6b8;
    }
    
    /* Grid View Card */
    .tour-card .card-image {
      height: 240px;
      background-size: cover;
      background-position: center;
      position: relative;
    }
    .card-badge {
      position: absolute;
      top: 1rem;
      left: 1rem;
      background: rgba(0,166,184,0.9);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
    }
    .card-favorite {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 40px;
      height: 40px;
      background: rgba(255,255,255,0.9);
      border: none;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }
    .card-favorite:hover, .card-favorite.saved {
      background: #ef4444;
      color: white;
    }
    
    .card-content {
      padding: 1.5rem;
    }
    .card-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 0.75rem;
      line-height: 1.3;
    }
    .card-location {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #64748b;
      margin-bottom: 1rem;
      font-size: 0.875rem;
    }
    .card-description {
      color: #475569;
      margin-bottom: 1.5rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.5;
    }
    .card-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .card-price {
      font-weight: 700;
      color: #00a6b8;
      font-size: 1.1rem;
    }
    .card-rating {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #fbbf24;
    }
    
    /* List View Card */
    .tours-grid.list-view .tour-card {
      display: flex;
      height: 200px;
    }
    .tours-grid.list-view .card-image {
      width: 300px;
      height: 200px;
    }
    .tours-grid.list-view .card-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    /* Detail View */
    .detail-view {
      display: none;
      max-width: 1000px;
      margin: 2rem auto;
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }
    .detail-view.active {
      display: block;
    }
    .detail-header {
      position: relative;
      height: 400px;
      background-size: cover;
      background-position: center;
    }
    .detail-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(transparent, rgba(0,0,0,0.7));
      color: white;
      padding: 3rem;
    }
    .detail-title {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
    }
    .detail-location {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 1.1rem;
      opacity: 0.9;
    }
    .detail-content {
      padding: 3rem;
    }
    .detail-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      margin-bottom: 3rem;
    }
    .info-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      background: #f8fafc;
      border-radius: 12px;
    }
    .info-icon {
      width: 48px;
      height: 48px;
      background: #00a6b8;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.25rem;
    }
    .info-text h4 {
      color: #1e293b;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    .info-text p {
      color: #64748b;
    }
    .itinerary-section {
      background: #f8fafc;
      padding: 2rem;
      border-radius: 16px;
      margin-top: 2rem;
    }
    .itinerary-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .itinerary-steps {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .step {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      padding: 1rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .step-number {
      width: 32px;
      height: 32px;
      background: #00a6b8;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      flex-shrink: 0;
    }
    .step-content {
      flex: 1;
    }
    .step-title {
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 0.25rem;
    }
    .step-description {
      color: #64748b;
      line-height: 1.5;
    }

    /* Action Buttons */
    .detail-actions {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }
    .btn-primary {
      padding: 1rem 2rem;
      background: linear-gradient(135deg, #00a6b8, #0f766e);
      color: white;
      border: none;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0,166,184,0.3);
    }
    .btn-secondary {
      padding: 1rem 2rem;
      background: white;
      color: #00a6b8;
      border: 2px solid #00a6b8;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    .btn-secondary:hover {
      background: #00a6b8;
      color: white;
    }

    /* Loading & Empty States */
    .loading-state, .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: #64748b;
    }
    .loading-icon, .empty-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    .loading-icon {
      color: #00a6b8;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .nav-links {
        display: none;
      }
      .hero {
        padding: 2rem 1rem;
      }
      .hero-stats {
        gap: 1rem;
      }
      .search-section {
        padding: 1rem;
      }
      .main-content {
        padding: 0 1rem 2rem;
      }
      .tours-grid {
        grid-template-columns: 1fr;
      }
      .tours-grid.list-view .tour-card {
        flex-direction: column;
        height: auto;
      }
      .tours-grid.list-view .card-image {
        width: 100%;
        height: 200px;
      }
      .detail-overlay {
        padding: 2rem 1rem;
      }
      .detail-title {
        font-size: 2rem;
      }
      .detail-content {
        padding: 2rem 1rem;
      }
      .detail-info {
        grid-template-columns: 1fr;
      }
      .detail-actions {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <nav class="nav">
      <a href="/tripko-system/tripko-frontend/file_html/user/homepage-new.html" class="logo">TripKo</a>
      <div class="nav-links">
        <a href="/tripko-system/tripko-frontend/file_html/user/homepage-new.html">Home</a>
        <a href="/tripko-system/tripko-frontend/file_html/user/search.html">Search</a>
        <a href="/tripko-system/tripko-frontend/file_html/user/map.html">Explore Map</a>
        <a href="/tripko-system/tripko-frontend/file_html/user/profile.html">My Profile</a>
      </div>
      <a href="javascript:history.back()" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Back
      </a>
    </nav>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <h1 class="hero-title">Things to Do in Pangasinan</h1>
      <p class="hero-subtitle">Discover amazing tours, activities, and experiences in the Pearl of the Orient</p>
      <div class="hero-stats">
        <div class="stat">
          <div class="stat-number" id="toursCount">0</div>
          <div class="stat-label">Tours Available</div>
        </div>
        <div class="stat">
          <div class="stat-number" id="destinationsCount">15+</div>
          <div class="stat-label">Destinations</div>
        </div>
        <div class="stat">
          <div class="stat-number">4.8★</div>
          <div class="stat-label">Average Rating</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Search & Filter Section -->
  <section class="search-section">
    <div class="search-container">
      <div class="search-bar">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search tours, activities, destinations..." id="searchInput">
      </div>
      <div class="filter-tabs" id="filterTabs">
        <div class="filter-tab active" data-category="all">All Tours</div>
        <div class="filter-tab" data-category="adventure">Adventure</div>
        <div class="filter-tab" data-category="cultural">Cultural</div>
        <div class="filter-tab" data-category="nature">Nature</div>
        <div class="filter-tab" data-category="beach">Beach</div>
        <div class="filter-tab" data-category="historical">Historical</div>
        <div class="filter-tab" data-category="food">Food & Dining</div>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <main class="main-content">
    <div class="section-header">
      <h2 class="section-title">Available Tours & Activities</h2>
      <div class="view-toggle">
        <button class="view-btn active" onclick="setView('grid')">
          <i class="fas fa-th"></i>
        </button>
        <button class="view-btn" onclick="setView('list')">
          <i class="fas fa-list"></i>
        </button>
      </div>
    </div>

    <!-- Tours Grid -->
    <div class="tours-grid" id="toursGrid">
      <div class="loading-state">
        <div class="loading-icon">
          <i class="fas fa-compass fa-spin"></i>
        </div>
        <h3>Loading Amazing Tours...</h3>
        <p>Discovering the best experiences Pangasinan has to offer</p>
      </div>
    </div>

    <!-- Detail View -->
    <div class="detail-view" id="detailView">
      <!-- Will be populated by JavaScript -->
    </div>
  </main>

  <script>
    let allTours = [];
    let filteredTours = [];
    let currentView = 'grid';

    // Initialize page
    document.addEventListener('DOMContentLoaded', () => {
      loadTours();
      setupEventListeners();
    });

    // Setup event listeners
    function setupEventListeners() {
      // Search functionality
      const searchInput = document.getElementById('searchInput');
      let searchTimeout;
      searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          filterTours();
        }, 300);
      });

      // Filter tabs
      document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', () => {
          document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
          tab.classList.add('active');
          filterTours();
        });
      });
    }

    // Load tours from API
    async function loadTours() {
      try {
        const response = await fetch('/tripko-system/tripko-backend/api/itineraries/read.php');
        const data = await response.json();
        
        if (data.records && data.records.length > 0) {
          allTours = data.records.map(tour => ({
            ...tour,
            category: getCategoryFromName(tour.name),
            rating: (Math.random() * 2 + 3).toFixed(1),
            reviewCount: Math.floor(Math.random() * 200) + 20,
            price: tour.environmental_fee || 0,
            imageUrl: getImageUrl(tour.image_path),
            highlights: extractHighlights(tour.description)
          }));
          
          document.getElementById('toursCount').textContent = allTours.length;
          filterTours();
        } else {
          showEmptyState();
        }
      } catch (error) {
        console.error('Error loading tours:', error);
        showErrorState();
      }
    }

    // Filter tours based on search and category
    function filterTours() {
      const searchQuery = document.getElementById('searchInput').value.toLowerCase();
      const activeCategory = document.querySelector('.filter-tab.active').dataset.category;

      filteredTours = allTours.filter(tour => {
        const matchesSearch = !searchQuery || 
          tour.name.toLowerCase().includes(searchQuery) ||
          tour.destination.toLowerCase().includes(searchQuery) ||
          tour.description.toLowerCase().includes(searchQuery);

        const matchesCategory = activeCategory === 'all' || tour.category === activeCategory;

        return matchesSearch && matchesCategory;
      });

      renderTours();
    }

    // Render tours in current view
    function renderTours() {
      const grid = document.getElementById('toursGrid');
      
      if (filteredTours.length === 0) {
        showEmptyState('No tours match your criteria. Try adjusting your search or filters.');
        return;
      }

      grid.innerHTML = filteredTours.map(tour => `
        <div class="tour-card" onclick="showTourDetail('${tour.itinerary_id}')">
          <div class="card-image" style="background-image: url('${tour.imageUrl}')">
            <div class="card-badge">${tour.category}</div>
            <button class="card-favorite" onclick="event.stopPropagation(); toggleFavorite('${tour.itinerary_id}')">
              <i class="far fa-heart"></i>
            </button>
          </div>
          <div class="card-content">
            <h3 class="card-title">${tour.name}</h3>
            <div class="card-location">
              <i class="fas fa-map-marker-alt"></i>
              ${tour.destination_name || tour.destination}
            </div>
            <p class="card-description">${tour.description.slice(0, 150)}...</p>
            <div class="card-footer">
              <div class="card-price">
                ${tour.price > 0 ? `₱${tour.price}` : 'Free'}
              </div>
              <div class="card-rating">
                <i class="fas fa-star"></i>
                <span>${tour.rating}</span>
                <span>(${tour.reviewCount})</span>
              </div>
            </div>
          </div>
        </div>
      `).join('');
    }

    // Show tour detail view
    function showTourDetail(tourId) {
      const tour = allTours.find(t => t.itinerary_id == tourId);
      if (!tour) return;

      const detailView = document.getElementById('detailView');
      const grid = document.getElementById('toursGrid');

      detailView.innerHTML = `
        <div class="detail-header" style="background-image: url('${tour.imageUrl}')">
          <div class="detail-overlay">
            <h1 class="detail-title">${tour.name}</h1>
            <div class="detail-location">
              <i class="fas fa-map-marker-alt"></i>
              ${tour.destination_name || tour.destination}
            </div>
          </div>
        </div>
        <div class="detail-content">
          <div class="detail-info">
            <div class="info-item">
              <div class="info-icon">
                <i class="fas fa-star"></i>
              </div>
              <div class="info-text">
                <h4>Rating</h4>
                <p>${tour.rating} out of 5 (${tour.reviewCount} reviews)</p>
              </div>
            </div>
            <div class="info-item">
              <div class="info-icon">
                <i class="fas fa-money-bill"></i>
              </div>
              <div class="info-text">
                <h4>Price</h4>
                <p>${tour.price > 0 ? `₱${tour.price} per person` : 'Free entry'}</p>
              </div>
            </div>
            <div class="info-item">
              <div class="info-icon">
                <i class="fas fa-clock"></i>
              </div>
              <div class="info-text">
                <h4>Duration</h4>
                <p>Full day experience</p>
              </div>
            </div>
            <div class="info-item">
              <div class="info-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="info-text">
                <h4>Group Size</h4>
                <p>Small groups recommended</p>
              </div>
            </div>
          </div>

          <div class="itinerary-section">
            <h3 class="itinerary-title">
              <i class="fas fa-route"></i>
              Tour Itinerary
            </h3>
            <div class="itinerary-steps">
              ${formatItinerarySteps(tour.description)}
            </div>
          </div>

          <div class="detail-actions">
            <button class="btn-primary" onclick="bookTour('${tour.itinerary_id}')">
              <i class="fas fa-calendar-plus"></i>
              Book This Tour
            </button>
            <button class="btn-secondary" onclick="addToItinerary('${tour.itinerary_id}')">
              <i class="fas fa-plus"></i>
              Add to Itinerary
            </button>
            <button class="btn-secondary" onclick="shareTour('${tour.itinerary_id}')">
              <i class="fas fa-share"></i>
              Share Tour
            </button>
            <button class="btn-secondary" onclick="hideDetail()">
              <i class="fas fa-arrow-left"></i>
              Back to Tours
            </button>
          </div>
        </div>
      `;

      grid.style.display = 'none';
      detailView.classList.add('active');
      
      // Scroll to top
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Hide detail view
    function hideDetail() {
      document.getElementById('detailView').classList.remove('active');
      document.getElementById('toursGrid').style.display = 'grid';
    }

    // Set view mode
    function setView(viewType) {
      currentView = viewType;
      const grid = document.getElementById('toursGrid');
      const buttons = document.querySelectorAll('.view-btn');
      
      buttons.forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
      
      if (viewType === 'list') {
        grid.classList.add('list-view');
      } else {
        grid.classList.remove('list-view');
      }
    }

    // Helper functions
    function getCategoryFromName(name) {
      const categories = {
        'beach': ['beach', 'island', 'coast', 'shore'],
        'cultural': ['church', 'festival', 'heritage', 'museum'],
        'nature': ['waterfall', 'cave', 'mountain', 'forest', 'nature'],
        'adventure': ['hiking', 'climbing', 'adventure', 'trek'],
        'historical': ['historical', 'heritage', 'ancient', 'old'],
        'food': ['food', 'restaurant', 'cuisine', 'dining']
      };
      
      const nameLower = name.toLowerCase();
      for (const [category, keywords] of Object.entries(categories)) {
        if (keywords.some(keyword => nameLower.includes(keyword))) {
          return category;
        }
      }
      return 'nature'; // Default category
    }

    function getImageUrl(imagePath) {
      if (!imagePath) {
        return 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
      }
      return `/tripko-system/uploads/${imagePath}`;
    }

    function extractHighlights(description) {
      return description.split('\n').slice(0, 3).map(line => line.trim()).filter(line => line);
    }

    function formatItinerarySteps(description) {
      const steps = description.split('\n').filter(line => line.trim());
      return steps.map((step, index) => `
        <div class="step">
          <div class="step-number">${index + 1}</div>
          <div class="step-content">
            <div class="step-title">Stop ${index + 1}</div>
            <div class="step-description">${step.trim()}</div>
          </div>
        </div>
      `).join('');
    }

    function showEmptyState(message = 'No tours available at the moment.') {
      document.getElementById('toursGrid').innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-search"></i>
          </div>
          <h3>No Tours Found</h3>
          <p>${message}</p>
        </div>
      `;
    }

    function showErrorState() {
      document.getElementById('toursGrid').innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <h3>Unable to Load Tours</h3>
          <p>Please check your connection and try again.</p>
        </div>
      `;
    }

    // Action functions
    function toggleFavorite(tourId) {
      const button = event.currentTarget;
      const icon = button.querySelector('i');
      
      if (icon.classList.contains('far')) {
        icon.className = 'fas fa-heart';
        button.classList.add('saved');
      } else {
        icon.className = 'far fa-heart';
        button.classList.remove('saved');
      }
    }

    function bookTour(tourId) {
      alert('Booking functionality coming soon! You will be able to book tours directly through TripKo.');
    }

    function addToItinerary(tourId) {
      alert('Added to your itinerary! Visit your profile to manage your trip plans.');
    }

    function shareTour(tourId) {
      if (navigator.share) {
        navigator.share({
          title: 'Check out this amazing tour in Pangasinan!',
          text: 'Discover this incredible experience with TripKo',
          url: window.location.href
        });
      } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Link copied to clipboard!');
      }
    }
  </script>
</body>
</html>