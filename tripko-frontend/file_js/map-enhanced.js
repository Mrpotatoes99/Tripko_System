/* TripAdvisor-Inspired Enhanced Map Interface */
const INITIAL_CENTER = [120.3333, 15.9000]; // Pangasinan
const INITIAL_ZOOM = 9;

let map;
let allFeatures = [];
let filteredFeatures = [];
let markersById = new Map();
let poiMarkersById = new Map();
let userLocationMarker = null;
let currentSelectedSpot = null;

// Search and filtering
let searchTimeout;
let activeFilters = new Set(['spot', 'festival', 'terminal', 'town', 'itinerary']);

// POI cache
let poiCache = {};
let isLoadingPOIs = false;
const POI_CACHE_EXPIRY = 6 * 60 * 60 * 1000; // 6 hours

// DOM elements
const searchInput = document.getElementById('searchInput');
const filterChips = document.getElementById('filterChips');
const resultsList = document.getElementById('resultsList');
const resultsCount = document.getElementById('resultsCount');
const sortSelect = document.getElementById('sortSelect');
const locateBtn = document.getElementById('locateBtn');
const clearRouteBtn = document.getElementById('clearRouteBtn');

// Initialize
init();

function init() {
  console.log('Initializing TripAdvisor-style map...');
  
  initializeMap();
  setupEventListeners();
  loadMapData();
}

function initializeMap() {
  map = new maplibregl.Map({
    container: 'map',
    style: {
      version: 8,
      glyphs: 'https://demotiles.maplibre.org/font/{fontstack}/{range}.pbf',
      sources: {
        'osm-tiles': {
          type: 'raster',
          tiles: [
            'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'https://b.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'https://c.tile.openstreetmap.org/{z}/{x}/{y}.png'
          ],
          tileSize: 256,
          attribution: '© OpenStreetMap contributors'
        }
      },
      layers: [
        { id: 'osm', type: 'raster', source: 'osm-tiles' }
      ]
    },
    center: INITIAL_CENTER,
    zoom: INITIAL_ZOOM
  });

  map.addControl(new maplibregl.NavigationControl(), 'top-left');
  
  map.on('load', () => {
    console.log('Map loaded successfully');
  });
  
  map.on('error', (e) => {
    console.error('Map error:', e);
  });
}

function setupEventListeners() {
  // Search functionality
  searchInput.addEventListener('input', handleSearch);
  
  // Sort functionality
  sortSelect.addEventListener('change', handleSort);
  
  // Map controls
  locateBtn.addEventListener('click', locateUser);
  clearRouteBtn.addEventListener('click', clearRoute);
  
  // Custom map controls
  document.getElementById('zoomInBtn')?.addEventListener('click', () => map.zoomIn());
  document.getElementById('zoomOutBtn')?.addEventListener('click', () => map.zoomOut());
  document.getElementById('fullscreenBtn')?.addEventListener('click', toggleFullscreen);
}

async function loadMapData() {
  try {
    resultsCount.textContent = 'Loading...';
    
    const response = await fetch('/tripko-system/tripko-backend/api/map/markers.php');
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    
    const data = await response.json();
    allFeatures = data.features || [];
    
    console.log(`Loaded ${allFeatures.length} features`);
    
    initializeFilters();
    applyFiltersAndSearch();
    renderMarkers();
    
  } catch (error) {
    console.error('Failed to load map data:', error);
    resultsCount.textContent = 'Error loading data';
    resultsList.innerHTML = `
      <div style="padding: 2rem; text-align: center; color: #ef4444;">
        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
        <div>Failed to load map data</div>
        <div style="font-size: 0.875rem; margin-top: 0.5rem;">Please check your connection and try again</div>
      </div>
    `;
  }
}

function initializeFilters() {
  const categories = [
    { key: 'spot', label: 'Tourist Spots', icon: 'fas fa-map-marker-alt', color: '#2563eb' },
    { key: 'festival', label: 'Festivals', icon: 'fas fa-calendar-alt', color: '#db2777' },
    { key: 'terminal', label: 'Terminals', icon: 'fas fa-bus', color: '#f59e0b' },
    { key: 'town', label: 'Towns', icon: 'fas fa-building', color: '#10b981' },
    { key: 'itinerary', label: 'Itineraries', icon: 'fas fa-route', color: '#7c3aed' }
  ];

  filterChips.innerHTML = categories.map(cat => {
    const count = allFeatures.filter(f => f.properties.category === cat.key).length;
    const isActive = activeFilters.has(cat.key);
    
    return `
      <div class="filter-chip ${isActive ? 'active' : ''}" 
           data-category="${cat.key}"
           style="--chip-color: ${cat.color}">
        <i class="${cat.icon}"></i>
        ${cat.label}
        <span class="count-badge">${count}</span>
      </div>
    `;
  }).join('');

  // Add click handlers
  filterChips.addEventListener('click', (e) => {
    const chip = e.target.closest('.filter-chip');
    if (!chip) return;
    
    const category = chip.dataset.category;
    
    if (activeFilters.has(category)) {
      activeFilters.delete(category);
      chip.classList.remove('active');
    } else {
      activeFilters.add(category);
      chip.classList.add('active');
    }
    
    applyFiltersAndSearch();
  });
}

function handleSearch(e) {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFiltersAndSearch();
  }, 300);
}

function applyFiltersAndSearch() {
  const searchTerm = searchInput.value.toLowerCase().trim();
  
  filteredFeatures = allFeatures.filter(feature => {
    const props = feature.properties;
    
    // Category filter
    if (!activeFilters.has(props.category)) return false;
    
    // Search filter
    if (searchTerm) {
      const searchFields = [
        props.name,
        props.municipality,
        props.description,
        props.category
      ].filter(Boolean).join(' ').toLowerCase();
      
      if (!searchFields.includes(searchTerm)) return false;
    }
    
    return true;
  });
  
  renderResultsList();
  renderMarkers();
}

function renderResultsList() {
  resultsCount.textContent = `${filteredFeatures.length} places found`;
  
  if (filteredFeatures.length === 0) {
    resultsList.innerHTML = `
      <div style="padding: 2rem; text-align: center; color: #64748b;">
        <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem;"></i>
        <div>No places found</div>
        <div style="font-size: 0.875rem; margin-top: 0.5rem;">Try adjusting your filters or search terms</div>
      </div>
    `;
    return;
  }

  resultsList.innerHTML = filteredFeatures.map(feature => {
    const props = feature.properties;
    const categoryInfo = getCategoryInfo(props.category);
    
    return `
      <div class="result-card" data-feature-id="${props.id}">
        ${props.image ? `<img src="${props.image}" alt="${props.name}" class="card-image" onerror="this.style.display='none'">` : ''}
        <div class="card-title">${escapeHtml(props.name)}</div>
        <div class="card-location">
          <i class="fas fa-map-marker-alt"></i>
          ${props.municipality || 'Pangasinan'}
        </div>
        ${props.description ? `<div class="card-description">${escapeHtml(props.description.slice(0, 120))}${props.description.length > 120 ? '...' : ''}</div>` : ''}
        <div class="card-category" style="background: ${categoryInfo.color}20; color: ${categoryInfo.color};">
          <i class="${categoryInfo.icon}"></i>
          ${categoryInfo.label}
        </div>
      </div>
    `;
  }).join('');

  // Add click handlers
  resultsList.addEventListener('click', (e) => {
    const card = e.target.closest('.result-card');
    if (!card) return;
    
    const featureId = parseInt(card.dataset.featureId);
    const feature = filteredFeatures.find(f => f.properties.id === featureId);
    if (feature) {
      focusOnFeature(feature);
    }
  });
}

function renderMarkers() {
  // Clear existing markers
  markersById.forEach(marker => marker.remove());
  markersById.clear();

  filteredFeatures.forEach(feature => {
    const props = feature.properties;
    const categoryInfo = getCategoryInfo(props.category);
    
    // Create marker element
    const el = document.createElement('div');
    el.className = 'custom-marker';
    el.style.cssText = `
      width: 32px;
      height: 32px;
      background: ${categoryInfo.color};
      border: 3px solid #fff;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.25);
      transition: all 0.2s;
    `;
    el.innerHTML = `<i class="${categoryInfo.icon}"></i>`;
    el.title = props.name;
    
    // Hover effects
    el.addEventListener('mouseenter', () => {
      el.style.transform = 'scale(1.2)';
      el.style.zIndex = '1000';
    });
    el.addEventListener('mouseleave', () => {
      el.style.transform = 'scale(1)';
      el.style.zIndex = 'auto';
    });

    // Create popup content
    const popup = new maplibregl.Popup({ offset: 25 })
      .setHTML(createPopupContent(feature));

    // Create marker
    const marker = new maplibregl.Marker(el)
      .setLngLat(feature.geometry.coordinates)
      .setPopup(popup)
      .addTo(map);

    markersById.set(props.id, marker);
  });
}

function createPopupContent(feature) {
  const props = feature.properties;
  const categoryInfo = getCategoryInfo(props.category);
  
  let content = `
    <div class="enhanced-popup" style="min-width: 280px;">
      <div style="margin-bottom: 12px;">
        ${props.image ? `<img src="${props.image}" style="width: 100%; height: 160px; object-fit: cover; border-radius: 8px; margin-bottom: 12px;" onerror="this.style.display='none'">` : ''}
        <h3 style="margin: 0; color: #1e293b; font-size: 18px; font-weight: 600;">${escapeHtml(props.name)}</h3>
        <div style="color: #64748b; font-size: 14px; margin-top: 4px;">
          <i class="fas fa-map-marker-alt"></i>
          ${props.municipality || 'Pangasinan'}
        </div>
      </div>
      
      ${props.description ? `<p style="color: #475569; font-size: 14px; line-height: 1.4; margin-bottom: 12px;">${escapeHtml(props.description.slice(0, 200))}${props.description.length > 200 ? '...' : ''}</p>` : ''}
      
      ${props.contact ? `<div style="color: #64748b; font-size: 13px; margin-bottom: 8px;"><i class="fas fa-phone"></i> ${escapeHtml(props.contact)}</div>` : ''}
      
      <div style="display: flex; gap: 8px; margin-bottom: 12px;">
        <button onclick="navigateToFeature(${props.id})" style="flex: 1; padding: 8px 12px; background: #0f766e; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">
          <i class="fas fa-directions"></i> Get Directions
        </button>
      </div>
  `;

  // Add POI buttons for tourist spots
  if (props.category === 'spot') {
    content += `
      <div style="border-top: 1px solid #e2e8f0; padding-top: 12px;">
        <div style="font-weight: 600; margin-bottom: 8px; color: #1e293b;">Nearby Places</div>
        <div style="display: flex; gap: 6px;">
          <button onclick="fetchNearbyPOIs('restaurant', ${props.id})" style="flex: 1; padding: 6px 8px; background: #ef4444; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">
            🍽️ Restaurants
          </button>
          <button onclick="fetchNearbyPOIs('lodging', ${props.id})" style="flex: 1; padding: 6px 8px; background: #0d9488; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">
            🏨 Lodging
          </button>
          <button onclick="clearPOIs()" style="padding: 6px 8px; background: #6b7280; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">
            ✕ Clear
          </button>
        </div>
        <div id="poi-status-${props.id}" style="margin-top: 8px; font-size: 12px; color: #64748b; min-height: 16px;"></div>
      </div>
    `;
  }

  content += '</div>';
  return content;
}

function getCategoryInfo(category) {
  const categoryMap = {
    'spot': { label: 'Tourist Spot', icon: 'fas fa-map-marker-alt', color: '#2563eb' },
    'festival': { label: 'Festival', icon: 'fas fa-calendar-alt', color: '#db2777' },
    'terminal': { label: 'Terminal', icon: 'fas fa-bus', color: '#f59e0b' },
    'town': { label: 'Town', icon: 'fas fa-building', color: '#10b981' },
    'itinerary': { label: 'Itinerary', icon: 'fas fa-route', color: '#7c3aed' }
  };
  return categoryMap[category] || { label: 'Unknown', icon: 'fas fa-question', color: '#64748b' };
}

function focusOnFeature(feature) {
  const coords = feature.geometry.coordinates;
  map.flyTo({
    center: coords,
    zoom: 14,
    duration: 1000
  });
  
  // Open popup
  const marker = markersById.get(feature.properties.id);
  if (marker) {
    marker.togglePopup();
  }
  
  // Highlight result card
  document.querySelectorAll('.result-card').forEach(card => {
    card.classList.remove('highlighted');
  });
  const targetCard = document.querySelector(`[data-feature-id="${feature.properties.id}"]`);
  if (targetCard) {
    targetCard.classList.add('highlighted');
    targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function handleSort() {
  const sortBy = sortSelect.value;
  
  filteredFeatures.sort((a, b) => {
    switch (sortBy) {
      case 'name':
        return a.properties.name.localeCompare(b.properties.name);
      case 'category':
        return a.properties.category.localeCompare(b.properties.category);
      case 'distance':
        // TODO: Implement distance sorting when user location is available
        return 0;
      default:
        return 0;
    }
  });
  
  renderResultsList();
}

function locateUser() {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by this browser.');
    return;
  }

  locateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Locating...';
  
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const { latitude, longitude } = position.coords;
      
      if (userLocationMarker) {
        userLocationMarker.remove();
      }
      
      userLocationMarker = new maplibregl.Marker({ color: '#10b981' })
        .setLngLat([longitude, latitude])
        .setPopup(new maplibregl.Popup().setHTML('<strong>You are here</strong>'))
        .addTo(map);
      
      map.flyTo({
        center: [longitude, latitude],
        zoom: 13
      });
      
      locateBtn.innerHTML = '<i class="fas fa-location-dot"></i> Find Me';
    },
    (error) => {
      console.error('Geolocation error:', error);
      locateBtn.innerHTML = '<i class="fas fa-location-dot"></i> Find Me';
      alert('Unable to retrieve your location. Please try again.');
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
  );
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen();
  } else {
    document.exitFullscreen();
  }
}

function clearRoute() {
  // TODO: Implement route clearing
  clearRouteBtn.classList.add('hidden');
}

// POI Functions
async function fetchNearbyPOIs(type, featureId) {
  if (isLoadingPOIs) return;
  
  const feature = allFeatures.find(f => f.properties.id === featureId);
  if (!feature) return;
  
  const [lon, lat] = feature.geometry.coordinates;
  currentSelectedSpot = featureId;
  
  const statusEl = document.getElementById(`poi-status-${featureId}`);
  if (statusEl) statusEl.textContent = `Loading ${type}s...`;
  
  // Check cache
  const cached = poiCache[featureId];
  if (cached && cached[type] && (Date.now() - cached.timestamp < POI_CACHE_EXPIRY)) {
    renderPOIMarkers(type, cached[type]);
    if (statusEl) statusEl.textContent = `Found ${cached[type].length} ${type}s`;
    return;
  }
  
  isLoadingPOIs = true;
  
  try {
    const radius = 2000;
    const query = buildOverpassQuery(type, lat, lon, radius);
    
    const response = await fetch('https://overpass-api.de/api/interpreter', {
      method: 'POST',
      body: query,
      headers: { 'Content-Type': 'text/plain' }
    });
    
    if (!response.ok) throw new Error(`Overpass API error: ${response.status}`);
    
    const data = await response.json();
    const pois = parseOverpassData(data, type);
    
    // Cache results
    if (!poiCache[featureId]) poiCache[featureId] = { timestamp: Date.now() };
    poiCache[featureId][type] = pois;
    poiCache[featureId].timestamp = Date.now();
    
    renderPOIMarkers(type, pois);
    if (statusEl) statusEl.textContent = `Found ${pois.length} ${type}s`;
    
  } catch (error) {
    console.error('POI fetch error:', error);
    if (statusEl) statusEl.textContent = `Error loading ${type}s`;
  } finally {
    isLoadingPOIs = false;
  }
}

function buildOverpassQuery(type, lat, lon, radius) {
  const amenities = type === 'restaurant' 
    ? 'restaurant|cafe|fast_food|bar|pub'
    : 'hotel|guest_house|hostel|motel';
  
  const tourism = type === 'lodging' ? '|hotel|guest_house|hostel|motel|resort|apartment' : '';
  
  return `[out:json][timeout:25];
(
  node(around:${radius},${lat},${lon})[amenity~"^(${amenities})$"];
  way(around:${radius},${lat},${lon})[amenity~"^(${amenities})$"];
  ${tourism ? `node(around:${radius},${lat},${lon})[tourism~"^(hotel|guest_house|hostel|motel|resort|apartment)$"];` : ''}
  ${tourism ? `way(around:${radius},${lat},${lon})[tourism~"^(hotel|guest_house|hostel|motel|resort|apartment)$"];` : ''}
);
out center 50;`;
}

function parseOverpassData(data, type) {
  return data.elements.slice(0, 50).map(element => {
    const tags = element.tags || {};
    const name = tags.name || tags.brand || (type === 'restaurant' ? 'Restaurant' : 'Lodging');
    
    let lat, lon;
    if (element.type === 'node') {
      lat = element.lat;
      lon = element.lon;
    } else if (element.center) {
      lat = element.center.lat;
      lon = element.center.lon;
    }
    
    return lat && lon ? {
      osm_id: element.id,
      type: type,
      name: name,
      lat: lat,
      lon: lon,
      tags: tags
    } : null;
  }).filter(Boolean);
}

function renderPOIMarkers(type, pois) {
  // Clear existing POI markers of this type
  const keysToRemove = [];
  poiMarkersById.forEach((marker, key) => {
    if (key.startsWith(`${type}_`)) {
      marker.remove();
      keysToRemove.push(key);
    }
  });
  keysToRemove.forEach(key => poiMarkersById.delete(key));
  
  // Add new markers
  pois.forEach(poi => {
    const el = document.createElement('div');
    el.className = `poi-marker ${type}-marker`;
    
    if (type === 'restaurant') {
      el.style.cssText = 'width: 20px; height: 20px; background: #ef4444; border: 2px solid #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);';
      el.innerHTML = '🍽';
    } else {
      el.style.cssText = 'width: 20px; height: 20px; background: #0d9488; border: 2px solid #fff; transform: rotate(45deg); display: flex; align-items: center; justify-content: center; font-size: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);';
      el.innerHTML = '🏨';
    }
    
    const popup = new maplibregl.Popup({ offset: 15 })
      .setHTML(`
        <div style="min-width: 200px;">
          <h4 style="margin: 0 0 8px 0;">${escapeHtml(poi.name)}</h4>
          <div style="font-size: 13px; color: #64748b;">
            ${poi.tags.amenity || poi.tags.tourism || type}
          </div>
          ${poi.tags.cuisine ? `<div style="font-size: 13px;"><strong>Cuisine:</strong> ${escapeHtml(poi.tags.cuisine)}</div>` : ''}
          ${poi.tags.phone ? `<div style="font-size: 13px;"><strong>Phone:</strong> ${escapeHtml(poi.tags.phone)}</div>` : ''}
        </div>
      `);
    
    const marker = new maplibregl.Marker(el)
      .setLngLat([poi.lon, poi.lat])
      .setPopup(popup)
      .addTo(map);
    
    poiMarkersById.set(`${type}_${poi.osm_id}`, marker);
  });
}

function clearPOIs() {
  poiMarkersById.forEach(marker => marker.remove());
  poiMarkersById.clear();
  
  document.querySelectorAll('[id^="poi-status-"]').forEach(el => {
    el.textContent = '';
  });
}

// Utility functions
function escapeHtml(text) {
  if (!text) return '';
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return text.replace(/[&<>"']/g, m => map[m]);
}

// Global functions for popup buttons
window.navigateToFeature = function(featureId) {
  const feature = allFeatures.find(f => f.properties.id === featureId);
  if (!feature) return;
  
  if (userLocationMarker) {
    const userCoords = userLocationMarker.getLngLat();
    const featureCoords = feature.geometry.coordinates;
    // TODO: Implement routing
    console.log('Navigate from', userCoords, 'to', featureCoords);
  } else {
    locateUser();
  }
};

window.fetchNearbyPOIs = fetchNearbyPOIs;
window.clearPOIs = clearPOIs;