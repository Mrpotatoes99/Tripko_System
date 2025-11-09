<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Results - TripKo Pangasinan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { primary: '#00a6b8', brand: '#0f766e' } } } };</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link href="https://unpkg.com/maplibre-gl@4.1.1/dist/maplibre-gl.css" rel="stylesheet" />
  <script src="https://unpkg.com/maplibre-gl@4.1.1/dist/maplibre-gl.js"></script>
  <link rel="stylesheet" href="/tripko-system/tripko-frontend/file_css/responsive.css" />
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
      max-width: 1600px;
      margin: 0 auto;
      padding: 0 1rem;
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
    .search-header {
      flex: 1;
      max-width: 600px;
      margin: 0 2rem;
    }
    .search-input {
      width: 100%;
      padding: 0.75rem 1rem 0.75rem 3rem;
      border: 2px solid #e2e8f0;
      border-radius: 50px;
      font-size: 1rem;
      transition: all 0.2s;
      position: relative;
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
    .nav-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
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

    /* Layout */
    .main-container {
      display: flex;
      height: calc(100vh - 70px);
      max-width: 1600px;
      margin: 0 auto;
    }
    
    /* Sidebar */
    .sidebar {
      width: 400px;
      background: white;
      border-right: 1px solid #e2e8f0;
      display: flex;
      flex-direction: column;
      transition: all 0.3s ease;
    }
    .sidebar.collapsed {
      width: 0;
      overflow: hidden;
    }
    
    .filters {
      padding: 1.5rem;
      border-bottom: 1px solid #e2e8f0;
    }
    .filter-group {
      margin-bottom: 1.5rem;
    }
    .filter-title {
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: #1e293b;
    }
    .filter-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    .filter-chip {
      padding: 0.5rem 1rem;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      cursor: pointer;
      font-size: 0.875rem;
      transition: all 0.2s;
    }
    .filter-chip.active {
      background: #00a6b8;
      color: white;
      border-color: #00a6b8;
    }
    .filter-chip:hover {
      background: #e2e8f0;
    }
    .filter-chip.active:hover {
      background: #008a99;
    }
    
    .price-range {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-top: 0.5rem;
    }
    .price-input {
      flex: 1;
      padding: 0.5rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.875rem;
    }
    
    .rating-filter {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    .rating-option {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      cursor: pointer;
      padding: 0.5rem;
      border-radius: 8px;
      transition: background 0.2s;
    }
    .rating-option:hover {
      background: #f1f5f9;
    }
    
    /* Results */
    .results-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .results-header {
      padding: 1rem 1.5rem;
      background: white;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .results-count {
      font-weight: 600;
      color: #1e293b;
    }
    .sort-dropdown {
      padding: 0.5rem 1rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: white;
      cursor: pointer;
    }
    
    .results-content {
      display: flex;
      flex: 1;
      overflow: hidden;
    }
    .results-list {
      width: 50%;
      overflow-y: auto;
      padding: 1rem;
    }
    .result-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      cursor: pointer;
      transition: all 0.2s;
      border: 2px solid transparent;
    }
    .result-card:hover {
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }
    .result-card.selected {
      border-color: #00a6b8;
    }
    
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }
    .card-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 0.25rem;
    }
    .card-category {
      color: #64748b;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .save-btn {
      background: none;
      border: none;
      color: #64748b;
      font-size: 1.25rem;
      cursor: pointer;
      transition: color 0.2s;
    }
    .save-btn:hover, .save-btn.saved {
      color: #ef4444;
    }
    
    .card-rating {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }
    .stars {
      color: #fbbf24;
    }
    .rating-score {
      font-weight: 600;
    }
    .rating-count {
      color: #64748b;
      font-size: 0.875rem;
    }
    
    .card-description {
      color: #475569;
      margin-bottom: 1rem;
      line-height: 1.5;
    }
    
    .card-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }
    .tag {
      padding: 0.25rem 0.75rem;
      background: #f1f5f9;
      color: #475569;
      border-radius: 12px;
      font-size: 0.75rem;
    }
    
    .card-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 1rem;
      border-top: 1px solid #f1f5f9;
    }
    .card-distance {
      color: #64748b;
      font-size: 0.875rem;
    }
    .card-price {
      font-weight: 700;
      color: #00a6b8;
    }
    
    /* Map */
    .map-container {
      width: 50%;
      position: relative;
    }
    .map-container.full-width {
      width: 100%;
    }
    #map {
      width: 100%;
      height: 100%;
    }
    
    .map-controls {
      position: absolute;
      top: 1rem;
      right: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      z-index: 100;
    }
    .map-control-btn {
      width: 40px;
      height: 40px;
      background: white;
      border: none;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }
    .map-control-btn:hover {
      background: #f8fafc;
    }
    
    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: #64748b;
    }
    .empty-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }
    
    /* Loading */
    .loading {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }
    .spinner {
      width: 40px;
      height: 40px;
      border: 3px solid #f1f5f9;
      border-top: 3px solid #00a6b8;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Mobile */
    @media (max-width: 768px) {
      .main-container {
        flex-direction: column;
      }
      .sidebar {
        width: 100%;
        height: auto;
      }
      .results-content {
        flex-direction: column;
      }
      .results-list, .map-container {
        width: 100%;
        height: 50vh;
      }
      .search-header {
        margin: 0 1rem;
      }
      .nav-actions {
        display: none;
      }
    }
  </style>
</head>
<body class="bg-gray-50 text-slate-900">
  <!-- Header -->
  <header class="header">
    <nav class="nav">
      <a href="/tripko-system/tripko-frontend/file_html/user/homepage-new.html" class="logo">TripKo</a>
      <div class="search-header">
        <div style="position: relative;">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="search-input" placeholder="Search destinations, restaurants, hotels..." id="searchInput">
        </div>
      </div>
      <div class="nav-actions">
        <div class="view-toggle">
          <button class="view-btn" id="listViewBtn" onclick="toggleView('list')">
            <i class="fas fa-list"></i>
          </button>
          <button class="view-btn active" id="mapViewBtn" onclick="toggleView('map')">
            <i class="fas fa-map"></i>
          </button>
        </div>
        <button class="map-control-btn" onclick="toggleSidebar()">
          <i class="fas fa-filter"></i>
        </button>
      </div>
    </nav>
  </header>

  <!-- Main Container -->
  <div class="main-container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="filters">
        <div class="filter-group">
          <div class="filter-title">Category</div>
          <div class="filter-chips" id="categoryFilters">
            <div class="filter-chip active" data-category="all">All</div>
            <div class="filter-chip" data-category="tourist_spot">Tourist Spots</div>
            <div class="filter-chip" data-category="restaurant">Restaurants</div>
            <div class="filter-chip" data-category="hotel">Hotels</div>
            <div class="filter-chip" data-category="festival">Festivals</div>
            <div class="filter-chip" data-category="church">Churches</div>
            <div class="filter-chip" data-category="cave">Caves</div>
            <div class="filter-chip" data-category="waterfall">Waterfalls</div>
          </div>
        </div>

        <div class="filter-group">
          <div class="filter-title">Rating</div>
          <div class="rating-filter">
            <label class="rating-option">
              <input type="checkbox" value="5"> 
              <span class="stars">★★★★★</span> 5 Stars
            </label>
            <label class="rating-option">
              <input type="checkbox" value="4"> 
              <span class="stars">★★★★☆</span> 4+ Stars
            </label>
            <label class="rating-option">
              <input type="checkbox" value="3"> 
              <span class="stars">★★★☆☆</span> 3+ Stars
            </label>
          </div>
        </div>

        <div class="filter-group">
          <div class="filter-title">Distance</div>
          <div class="filter-chips" id="distanceFilters">
            <div class="filter-chip active" data-distance="all">Any Distance</div>
            <div class="filter-chip" data-distance="5">Within 5km</div>
            <div class="filter-chip" data-distance="10">Within 10km</div>
            <div class="filter-chip" data-distance="25">Within 25km</div>
          </div>
        </div>

        <div class="filter-group">
          <div class="filter-title">Features</div>
          <div class="filter-chips" id="featureFilters">
            <div class="filter-chip" data-feature="parking">Parking</div>
            <div class="filter-chip" data-feature="wifi">WiFi</div>
            <div class="filter-chip" data-feature="accessible">Accessible</div>
            <div class="filter-chip" data-feature="family">Family Friendly</div>
            <div class="filter-chip" data-feature="pets">Pet Friendly</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Results Area -->
    <main class="results-area">
      <div class="results-header">
        <div class="results-count" id="resultsCount">Searching...</div>
        <select class="sort-dropdown" id="sortSelect">
          <option value="relevance">Sort by Relevance</option>
          <option value="rating">Highest Rating</option>
          <option value="distance">Nearest First</option>
          <option value="name">Alphabetical</option>
          <option value="newest">Newest First</option>
        </select>
      </div>

      <div class="results-content">
        <div class="results-list" id="resultsList">
          <div class="loading">
            <div class="spinner"></div>
          </div>
        </div>

        <div class="map-container" id="mapContainer">
          <div id="map"></div>
          <div class="map-controls">
            <button class="map-control-btn" onclick="centerMap()" title="Recenter Map">
              <i class="fas fa-crosshairs"></i>
            </button>
            <button class="map-control-btn" onclick="toggleMapStyle()" title="Toggle Map Style">
              <i class="fas fa-layer-group"></i>
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    let map;
    let markers = [];
    let allResults = [];
    let filteredResults = [];
    let selectedMarker = null;
    let currentMapStyle = 'streets';
    
    // URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('q') || '';
    const searchType = urlParams.get('type') || 'places';

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('searchInput').value = searchQuery;
      initializeMap();
      loadResults();
      setupEventListeners();
    });

    function initializeMap() {
      map = new maplibregl.Map({
        container: 'map',
        style: 'https://tiles.openfreemap.org/styles/liberty',
        center: [120.2, 16.0],
        zoom: 9
      });

      map.addControl(new maplibregl.NavigationControl(), 'top-left');
    }

    async function loadResults() {
      try {
        // Load from TripKo database
        const response = await fetch('/tripko-system/tripko-backend/api/map/markers.php');
        const data = await response.json();
        
        // Transform data for search results
        allResults = data.features?.map(feature => ({
          id: feature.properties.id,
          name: feature.properties.name,
          description: feature.properties.description || 'Discover this amazing destination in Pangasinan.',
          category: getCategoryDisplayName(feature.properties.category),
          categoryId: feature.properties.category,
          coordinates: feature.geometry.coordinates,
          municipality: feature.properties.municipality || 'Pangasinan',
          rating: (Math.random() * 2 + 3).toFixed(1), // Random rating 3.0-5.0
          reviewCount: Math.floor(Math.random() * 500) + 50,
          distance: calculateDistance(feature.geometry.coordinates),
          price: generatePrice(feature.properties.category),
          image: feature.properties.image || generateImageUrl(feature.properties.name),
          features: generateFeatures(),
          openStatus: getOpenStatus()
        })) || [];

        // Add external POI data for restaurants and hotels
        if (searchType === 'restaurants' || searchType === 'hotels' || searchQuery.toLowerCase().includes('restaurant') || searchQuery.toLowerCase().includes('hotel')) {
          const poiData = await fetchPOIData();
          allResults = [...allResults, ...poiData];
        }

        // Apply initial filters
        applyFilters();
        
      } catch (error) {
        console.error('Failed to load results:', error);
        showEmptyState('Failed to load results. Please try again.');
      }
    }

    async function fetchPOIData() {
      // Simulate POI data (in real implementation, this would use Overpass API)
      return [
        {
          id: 'poi_1',
          name: 'Seaside Grill Restaurant',
          description: 'Fresh seafood and local delicacies with ocean views.',
          category: 'Restaurant',
          categoryId: 'restaurant',
          coordinates: [120.2156, 16.0583],
          municipality: 'Dagupan City',
          rating: '4.6',
          reviewCount: 234,
          distance: '2.3 km',
          price: '₱₱',
          image: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
          features: ['WiFi', 'Parking', 'Family Friendly'],
          openStatus: 'Open'
        },
        {
          id: 'poi_2',
          name: 'Grand Plaza Hotel',
          description: 'Luxury accommodations in the heart of Dagupan.',
          category: 'Hotel',
          categoryId: 'hotel',
          coordinates: [120.2089, 16.0434],
          municipality: 'Dagupan City',
          rating: '4.8',
          reviewCount: 156,
          distance: '1.8 km',
          price: '₱₱₱',
          image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
          features: ['WiFi', 'Parking', 'Accessible', 'Pet Friendly'],
          openStatus: 'Open 24/7'
        }
      ];
    }

    function getCategoryDisplayName(category) {
      const categories = {
        'spot': 'Tourist Spot',
        'festival': 'Festival',
        'terminal': 'Terminal',
        'church': 'Church',
        'cave': 'Cave',
        'waterfall': 'Waterfall',
        'restaurant': 'Restaurant',
        'hotel': 'Hotel'
      };
      return categories[category] || 'Attraction';
    }

    function calculateDistance(coordinates) {
      // Simplified distance calculation
      return (Math.random() * 25 + 0.5).toFixed(1) + ' km';
    }

    function generatePrice(category) {
      const prices = {
        'restaurant': ['₱', '₱₱', '₱₱₱'][Math.floor(Math.random() * 3)],
        'hotel': ['₱₱', '₱₱₱', '₱₱₱₱'][Math.floor(Math.random() * 3)],
        'spot': 'Free',
        'festival': 'Free',
        'church': 'Free',
        'cave': '₱₱',
        'waterfall': '₱'
      };
      return prices[category] || 'Free';
    }

    function generateImageUrl(name) {
      const keywords = ['philippines', 'pangasinan', 'tourist', 'destination', 'travel'];
      const keyword = keywords[Math.floor(Math.random() * keywords.length)];
      return `https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80`;
    }

    function generateFeatures() {
      const allFeatures = ['WiFi', 'Parking', 'Accessible', 'Family Friendly', 'Pet Friendly'];
      const count = Math.floor(Math.random() * 3) + 1;
      return allFeatures.sort(() => 0.5 - Math.random()).slice(0, count);
    }

    function getOpenStatus() {
      const statuses = ['Open', 'Closes at 6 PM', 'Closes at 9 PM', 'Open 24/7'];
      return statuses[Math.floor(Math.random() * statuses.length)];
    }

    function applyFilters() {
      filteredResults = allResults.filter(result => {
        // Search query filter
        if (searchQuery) {
          const searchLower = searchQuery.toLowerCase();
          const matchesSearch = result.name.toLowerCase().includes(searchLower) ||
                               result.description.toLowerCase().includes(searchLower) ||
                               result.municipality.toLowerCase().includes(searchLower) ||
                               result.category.toLowerCase().includes(searchLower);
          if (!matchesSearch) return false;
        }

        // Category filter
        const activeCategory = document.querySelector('#categoryFilters .filter-chip.active')?.dataset.category;
        if (activeCategory && activeCategory !== 'all' && result.categoryId !== activeCategory) {
          return false;
        }

        // Rating filter
        const ratingFilters = Array.from(document.querySelectorAll('.rating-option input:checked')).map(cb => parseInt(cb.value));
        if (ratingFilters.length > 0) {
          const rating = parseFloat(result.rating);
          if (!ratingFilters.some(minRating => rating >= minRating)) return false;
        }

        return true;
      });

      // Sort results
      const sortBy = document.getElementById('sortSelect').value;
      filteredResults.sort((a, b) => {
        switch (sortBy) {
          case 'rating':
            return parseFloat(b.rating) - parseFloat(a.rating);
          case 'distance':
            return parseFloat(a.distance) - parseFloat(b.distance);
          case 'name':
            return a.name.localeCompare(b.name);
          case 'newest':
            return Math.random() - 0.5; // Random for demo
          default:
            return 0;
        }
      });

      renderResults();
      updateMap();
      updateResultsCount();
    }

    function renderResults() {
      const resultsList = document.getElementById('resultsList');
      
      if (filteredResults.length === 0) {
        showEmptyState('No results found. Try adjusting your filters.');
        return;
      }

      resultsList.innerHTML = filteredResults.map(result => `
        <div class="result-card" onclick="selectResult('${result.id}')" data-id="${result.id}">
          <div class="card-header">
            <div>
              <h3 class="card-title">${result.name}</h3>
              <div class="card-category">
                <i class="fas fa-${getCategoryIcon(result.categoryId)}"></i>
                ${result.category} • ${result.openStatus}
              </div>
            </div>
            <button class="save-btn" onclick="toggleSave(event, '${result.id}')">
              <i class="far fa-heart"></i>
            </button>
          </div>
          
          <div class="card-rating">
            <div class="stars">${'★'.repeat(Math.floor(result.rating))}${'☆'.repeat(5 - Math.floor(result.rating))}</div>
            <span class="rating-score">${result.rating}</span>
            <span class="rating-count">(${result.reviewCount} reviews)</span>
          </div>
          
          <p class="card-description">${result.description}</p>
          
          <div class="card-tags">
            ${result.features.map(feature => `<span class="tag">${feature}</span>`).join('')}
          </div>
          
          <div class="card-meta">
            <span class="card-distance">
              <i class="fas fa-map-marker-alt"></i> ${result.municipality} • ${result.distance}
            </span>
            <span class="card-price">${result.price}</span>
          </div>
        </div>
      `).join('');
    }

    function getCategoryIcon(category) {
      const icons = {
        'spot': 'map-marker-alt',
        'festival': 'calendar-alt',
        'terminal': 'bus',
        'church': 'church',
        'cave': 'mountain',
        'waterfall': 'tint',
        'restaurant': 'utensils',
        'hotel': 'bed'
      };
      return icons[category] || 'map-marker-alt';
    }

    function updateMap() {
      // Clear existing markers
      markers.forEach(marker => marker.remove());
      markers = [];

      // Add new markers
      filteredResults.forEach(result => {
        const el = document.createElement('div');
        el.className = 'marker';
        el.style.cssText = `
          background: #00a6b8;
          width: 30px;
          height: 30px;
          border-radius: 50%;
          border: 3px solid white;
          box-shadow: 0 2px 8px rgba(0,0,0,0.3);
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-size: 12px;
          font-weight: bold;
        `;
        el.innerHTML = '<i class="fas fa-' + getCategoryIcon(result.categoryId) + '"></i>';

        const marker = new maplibregl.Marker(el)
          .setLngLat(result.coordinates)
          .addTo(map);

        marker.getElement().addEventListener('click', () => {
          selectResult(result.id);
        });

        markers.push(marker);
      });

      // Fit map to markers
      if (filteredResults.length > 0) {
        const bounds = new maplibregl.LngLatBounds();
        filteredResults.forEach(result => {
          bounds.extend(result.coordinates);
        });
        map.fitBounds(bounds, { padding: 50 });
      }
    }

    function selectResult(id) {
      // Update UI
      document.querySelectorAll('.result-card').forEach(card => {
        card.classList.remove('selected');
      });
      document.querySelector(`[data-id="${id}"]`)?.classList.add('selected');

      // Update map
      const result = filteredResults.find(r => r.id === id);
      if (result) {
        map.flyTo({
          center: result.coordinates,
          zoom: 15,
          duration: 1000
        });

        // Highlight marker
        markers.forEach(marker => {
          marker.getElement().style.background = '#00a6b8';
          marker.getElement().style.transform = 'scale(1)';
        });

        const markerIndex = filteredResults.indexOf(result);
        if (markers[markerIndex]) {
          markers[markerIndex].getElement().style.background = '#ef4444';
          markers[markerIndex].getElement().style.transform = 'scale(1.2)';
        }
      }

      // Scroll to result
      const resultCard = document.querySelector(`[data-id="${id}"]`);
      if (resultCard) {
        resultCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }

    function updateResultsCount() {
      const count = filteredResults.length;
      document.getElementById('resultsCount').textContent = 
        `${count} result${count !== 1 ? 's' : ''} ${searchQuery ? `for "${searchQuery}"` : ''}`;
    }

    function showEmptyState(message) {
      document.getElementById('resultsList').innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-search"></i>
          </div>
          <p>${message}</p>
        </div>
      `;
    }

    function setupEventListeners() {
      // Search input
      const searchInput = document.getElementById('searchInput');
      let searchTimeout;
      searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          const newQuery = e.target.value;
          const newUrl = new URL(window.location);
          newUrl.searchParams.set('q', newQuery);
          window.history.replaceState({}, '', newUrl);
          applyFilters();
        }, 300);
      });

      // Filter chips
      document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', () => {
          const parent = chip.parentElement;
          parent.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
          chip.classList.add('active');
          applyFilters();
        });
      });

      // Rating filters
      document.querySelectorAll('.rating-option input').forEach(input => {
        input.addEventListener('change', applyFilters);
      });

      // Sort dropdown
      document.getElementById('sortSelect').addEventListener('change', applyFilters);
    }

    function toggleView(viewType) {
      const listBtn = document.getElementById('listViewBtn');
      const mapBtn = document.getElementById('mapViewBtn');
      const resultsList = document.querySelector('.results-list');
      const mapContainer = document.querySelector('.map-container');

      if (viewType === 'list') {
        listBtn.classList.add('active');
        mapBtn.classList.remove('active');
        resultsList.style.width = '100%';
        mapContainer.style.display = 'none';
      } else {
        mapBtn.classList.add('active');
        listBtn.classList.remove('active');
        resultsList.style.width = '50%';
        mapContainer.style.display = 'block';
        mapContainer.style.width = '50%';
        setTimeout(() => map.resize(), 100);
      }
    }

    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
      setTimeout(() => map.resize(), 300);
    }

    function centerMap() {
      map.flyTo({
        center: [120.2, 16.0],
        zoom: 9,
        duration: 1000
      });
    }

    function toggleMapStyle() {
      const styles = {
        'streets': 'https://tiles.openfreemap.org/styles/liberty',
        'satellite': 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
      };
      
      currentMapStyle = currentMapStyle === 'streets' ? 'satellite' : 'streets';
      
      if (currentMapStyle === 'satellite') {
        map.addSource('satellite', {
          type: 'raster',
          tiles: [styles.satellite],
          tileSize: 256
        });
        map.addLayer({
          id: 'satellite',
          type: 'raster',
          source: 'satellite'
        }, map.getStyle().layers[0].id);
      } else {
        if (map.getLayer('satellite')) {
          map.removeLayer('satellite');
          map.removeSource('satellite');
        }
      }
    }

    function toggleSave(event, id) {
      event.stopPropagation();
      const btn = event.currentTarget;
      const icon = btn.querySelector('i');
      
      if (btn.classList.contains('saved')) {
        btn.classList.remove('saved');
        icon.className = 'far fa-heart';
      } else {
        btn.classList.add('saved');
        icon.className = 'fas fa-heart';
      }
    }
  </script>
  <script src="/tripko-system/tripko-frontend/file_js/mobile-viewport-fix.js"></script>
</body>
</html>