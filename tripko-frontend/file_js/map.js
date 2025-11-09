/* MapLibre Map Initialization & Feature Logic */
const INITIAL_CENTER = [120.3333, 15.9000]; // Pangasinan approx lon, lat
const INITIAL_ZOOM = 9;

let map;
let allFeatures = [];
let markersById = new Map();
let activeCategories = new Set(['spot','festival','terminal','town','itinerary']);
let userLocationMarker = null;
let routeLayerId = 'route-line';
let routeSourceId = 'route-source';
let lastRouteProvider = null;

// POI (Points of Interest) management
let poiMarkersById = new Map();
let poiCache = {}; // { spotId: { restaurants: [...], lodging: [...], timestamp: Date } }
let currentSelectedSpot = null;
let isLoadingPOIs = false;
const POI_CACHE_EXPIRY = 6 * 60 * 60 * 1000; // 6 hours

const resultsEl = document.getElementById('results');
const searchInput = document.getElementById('searchInput');
const routeInfoEl = document.getElementById('routeInfo');
const clearRouteBtn = document.getElementById('clearRouteBtn');

init();

function init(){
  console.log('Initializing Pangasinan map...');
  
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
        { id: 'osm', type: 'raster', source: 'osm-tiles', minzoom: 0, maxzoom: 19 }
      ]
    },
    center: INITIAL_CENTER,
    zoom: INITIAL_ZOOM,
    attributionControl: true
  });
  
  console.log('Map created, adding controls...');
  map.addControl(new maplibregl.NavigationControl(), 'top-left');
  map.addControl(new maplibregl.ScaleControl({maxWidth:120,unit:'metric'}));

  map.on('load', () => {
    console.log('Map loaded, setting up features...');
    fetchMarkers();
    setupFilters();
    setupSearch();
    setupGeolocation();
    renderLegend();
    registerServiceWorker();
  });
  
  map.on('error', (e) => {
    console.error('Map error:', e);
  });
  
  clearRouteBtn.addEventListener('click', clearRoute);
}

async function fetchMarkers(){
  try {
    const res = await fetch('/tripko-system/tripko-backend/api/map/markers.php');
    if(!res.ok) throw new Error('Marker fetch failed: '+res.status);
    const data = await res.json();
    allFeatures = data.features || [];
    renderMarkers();
    updateResultsList(allFeatures);
  } catch(err){
    console.error('Failed to fetch markers:', err);
    // Show error in results panel
    const resultsEl = document.getElementById('results');
    if(resultsEl) resultsEl.innerHTML = '<div style="color: #ef4444; padding: 1rem;">Failed to load map data. Please refresh the page.</div>';
  }
}

function renderMarkers(){
  console.log('Rendering markers, total features:', allFeatures.length);
  // Clear existing
  markersById.forEach(m => m.remove());
  markersById.clear();
  
  let renderedCount = 0;
  for(const f of allFeatures){
    if(!activeCategories.has(f.properties.category)) continue;
    const el = document.createElement('div');
    el.className = 'marker';
    el.style.width='20px';el.style.height='20px';el.style.borderRadius='50%';
    el.style.background = colorForCategory(f.properties.category);
    el.style.border='2px solid #fff';
    el.style.boxShadow='0 0 0 1px #111';
    el.title = f.properties.name;
    const marker = new maplibregl.Marker(el)
      .setLngLat(f.geometry.coordinates)
      .setPopup(new maplibregl.Popup({offset:16}).setDOMContent(buildPopupContent(f)))
      .addTo(map);
    markersById.set(f.properties.id, marker);
    renderedCount++;
  }
  console.log('Rendered markers:', renderedCount);
}

function buildPopupContent(feature){
  const wrap = document.createElement('div');
  wrap.className='popup';
  const p = feature.properties;
  wrap.innerHTML = `<h3>${escapeHtml(p.name)}</h3>` +
    (p.image ? `<img loading="lazy" data-src="${p.image}" alt="${escapeHtml(p.name)}"/>` : '') +
    (p.municipality? `<div><strong>Municipality:</strong> ${escapeHtml(p.municipality)}</div>`:'') +
    (p.description? `<p>${escapeHtml(p.description).slice(0,300)}${p.description.length>300?'…':''}</p>`:'') +
    (p.contact? `<div><strong>Contact:</strong> ${escapeHtml(p.contact)}</div>`:'');
  
  const navBtn = document.createElement('button');
  navBtn.textContent='Navigate';
  navBtn.className='navigate-btn';
  navBtn.addEventListener('click',()=>{
    if(!userLocationMarker){
      locateUser().then(pos=>{
        requestRoute(pos.coords.longitude,pos.coords.latitude,feature.geometry.coordinates[0],feature.geometry.coordinates[1]);
      });
    } else {
      const lngLat = userLocationMarker.getLngLat();
      requestRoute(lngLat.lng,lngLat.lat,feature.geometry.coordinates[0],feature.geometry.coordinates[1]);
    }
  });
  wrap.appendChild(navBtn);

  // Add nearby POI buttons for tourist spots only
  if(p.category === 'spot'){
    const poiContainer = document.createElement('div');
    poiContainer.className = 'poi-buttons';
    poiContainer.style.cssText = 'margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;';
    
    const restaurantBtn = document.createElement('button');
    restaurantBtn.textContent = '🍽️ Restaurants';
    restaurantBtn.className = 'poi-btn restaurant-btn';
    restaurantBtn.style.cssText = 'font-size: 0.85em; padding: 4px 8px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer;';
    restaurantBtn.addEventListener('click', () => fetchNearbyPOIs('restaurant', feature));

    const lodgingBtn = document.createElement('button');
    lodgingBtn.textContent = '🏨 Lodging';
    lodgingBtn.className = 'poi-btn lodging-btn';
    lodgingBtn.style.cssText = 'font-size: 0.85em; padding: 4px 8px; background: #0d9488; color: white; border: none; border-radius: 4px; cursor: pointer;';
    lodgingBtn.addEventListener('click', () => fetchNearbyPOIs('lodging', feature));

    const clearBtn = document.createElement('button');
    clearBtn.textContent = '✕ Clear POIs';
    clearBtn.className = 'poi-btn clear-btn';
    clearBtn.style.cssText = 'font-size: 0.85em; padding: 4px 8px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer;';
    clearBtn.addEventListener('click', clearPOIs);

    poiContainer.appendChild(restaurantBtn);
    poiContainer.appendChild(lodgingBtn);
    poiContainer.appendChild(clearBtn);

    // Add status area for loading messages
    const statusDiv = document.createElement('div');
    statusDiv.className = 'poi-status';
    statusDiv.style.cssText = 'margin-top: 4px; font-size: 0.8em; color: #6b7280; min-height: 1em;';

    wrap.appendChild(poiContainer);
    wrap.appendChild(statusDiv);
  }

  return wrap;
}

function setupFilters(){
  console.log('Setting up filters...');
  const filterElements = document.querySelectorAll('#filters input[type=checkbox]');
  console.log('Found filter elements:', filterElements.length);
  
  filterElements.forEach(cb=>{
    console.log('Filter checkbox:', cb.getAttribute('data-cat'), 'checked:', cb.checked);
    cb.addEventListener('change',()=>{
      const cat = cb.getAttribute('data-cat');
      console.log('Filter changed:', cat, 'checked:', cb.checked);
      if(cb.checked) activeCategories.add(cat); else activeCategories.delete(cat);
      console.log('Active categories:', Array.from(activeCategories));
      renderMarkers();
      filteredResults();
    });
  });
}

function setupSearch(){
  console.log('Setting up search functionality...');
  let t;
  searchInput.addEventListener('input',(e)=>{
    console.log('Search input:', e.target.value);
    clearTimeout(t); 
    t=setTimeout(()=>{
      filteredResults();
    },200);
  });
}

function filteredResults(){
  const q = searchInput.value.trim().toLowerCase();
  const filtered = allFeatures.filter(f=>{
    if(!activeCategories.has(f.properties.category)) return false;
    if(!q) return true;
    const p = f.properties;
    const hay = [p.name, p.municipality, (p.tags||[]).join(' ')].filter(Boolean).join(' ').toLowerCase();
    return hay.includes(q);
  });
  // Re-render markers with current categories already handled
  updateResultsList(filtered);
}

function updateResultsList(list){
  resultsEl.innerHTML='';
  list.slice(0,500).forEach(f=>{
    const div = document.createElement('div');
    div.className='result-item';
    div.textContent = f.properties.name + (f.properties.municipality? ` (${f.properties.municipality})`:'');
    div.addEventListener('click',()=>{
      map.flyTo({center:f.geometry.coordinates,zoom:14});
      const marker = markersById.get(f.properties.id);
      if(marker) marker.togglePopup();
    });
    resultsEl.appendChild(div);
  });
}

function setupGeolocation(){
  document.getElementById('locateBtn').addEventListener('click',()=>{
    locateUser();
  });
}

function locateUser(){
  return new Promise((resolve,reject)=>{
    if(!navigator.geolocation){
      alert('Geolocation not supported');
      return reject(new Error('No geolocation'));
    }
    navigator.geolocation.getCurrentPosition(pos=>{
      const {latitude, longitude} = pos.coords;
      if(!userLocationMarker){
        userLocationMarker = new maplibregl.Marker({color:'#10b981'})
          .setLngLat([longitude, latitude])
          .setPopup(new maplibregl.Popup().setHTML('<strong>You are here</strong>'))
          .addTo(map);
      } else {
        userLocationMarker.setLngLat([longitude, latitude]);
      }
      map.flyTo({center:[longitude, latitude], zoom:13});
      resolve(pos);
    },err=>{
      console.warn('Geolocation error', err);
      reject(err);
    },{enableHighAccuracy:true,timeout:10000,maximumAge:60000});
  });
}

async function requestRoute(fromLon,fromLat,toLon,toLat){
  clearRoute();
  // Attempt OpenRouteService first (expects a server-provided key or environment injection)
  let geometry=null, distance=null, duration=null, provider=null;
  try {
    const orsKey = window.ORS_API_KEY || null; // Optionally injected via server template
    if(orsKey){
      const url = `https://api.openrouteservice.org/v2/directions/driving-car?api_key=${orsKey}&start=${fromLon},${fromLat}&end=${toLon},${toLat}`;
      const res = await fetch(url);
      if(!res.ok) throw new Error('ORS status '+res.status);
      const json = await res.json();
      const feat = json.features[0];
      geometry = feat.geometry;
      distance = feat.properties.summary.distance; // meters
      duration = feat.properties.summary.duration; // seconds
      provider='ORS';
    }
  } catch(e){
    console.warn('ORS routing failed, fallback to OSRM', e);
  }
  if(!geometry){
    try {
      const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${fromLon},${fromLat};${toLon},${toLat}?overview=full&geometries=geojson`;
      const res2 = await fetch(osrmUrl);
      if(!res2.ok) throw new Error('OSRM status '+res2.status);
      const json2 = await res2.json();
      const route = json2.routes[0];
      geometry = route.geometry;
      distance = route.distance;
      duration = route.duration;
      provider='OSRM';
    } catch(e2){
      console.error('Both routing providers failed', e2);
      return;
    }
  }
  map.addSource(routeSourceId,{type:'geojson',data:{type:'Feature',geometry,properties:{}}});
  map.addLayer({id:routeLayerId,type:'line',source:routeSourceId,paint:{'line-color':'#f59e0b','line-width':5,'line-opacity':0.85}});
  const bbox = turfBbox({type:'Feature',geometry,properties:{}});
  map.fitBounds([[bbox[0],bbox[1]],[bbox[2],bbox[3]]],{padding:40});
  routeInfoEl.classList.remove('hidden');
  routeInfoEl.innerHTML = `<strong>Route</strong><br>Distance: ${(distance/1000).toFixed(2)} km<br>Duration: ${(duration/60).toFixed(1)} min<br><em>Provider: ${provider}</em>`;
  clearRouteBtn.classList.remove('hidden');
  lastRouteProvider = provider;
}

function clearRoute(){
  if(map && map.getLayer(routeLayerId)){
    map.removeLayer(routeLayerId);
  }
  if(map && map.getSource(routeSourceId)){
    map.removeSource(routeSourceId);
  }
  routeInfoEl.classList.add('hidden');
  routeInfoEl.innerHTML='';
  clearRouteBtn.classList.add('hidden');
  lastRouteProvider = null;
}

function renderLegend(){
  const items = [
    ['spot','#2563eb','Tourist Spot'],
    ['festival','#db2777','Festival'],
  ['terminal','#f59e0b','Terminal'],
  ['itinerary','#7c3aed','Itinerary'],
    ['town','#10b981','Town Center'],
    ['restaurant','#ef4444','Restaurants'],
    ['lodging','#0d9488','Lodging'],
    ['you','#10b981','Your Location'],
    ['route','#f59e0b','Route']
  ];
  const legendEl = document.getElementById('legend');
  legendEl.innerHTML = '<strong>Legend</strong>';
  items.forEach(([key,color,label])=>{
    const div = document.createElement('div');
    div.className='legend-item';
    div.innerHTML = `<span class="legend-swatch" style="background:${color}"></span>${label}`;
    legendEl.appendChild(div);
  });
}

function colorForCategory(cat){
  switch(cat){
    case 'spot': return '#2563eb';
    case 'festival': return '#db2777';
    case 'terminal': return '#f59e0b';
    case 'town': return '#10b981';
    case 'itinerary': return '#7c3aed';
    default: return '#64748b';
  }
}

function escapeHtml(str){
  if(!str) return '';
  const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;' };
  return str.replace(/[&<>\"]/g, ch => map[ch]);
}

// Simple Turf.js bbox replacement (avoid full turf dependency for now)
function turfBbox(feature){
  const coords = feature.geometry.coordinates;
  // Supports LineString only (routing result)
  let minX=Infinity, minY=Infinity, maxX=-Infinity, maxY=-Infinity;
  for(const c of coords){
    const [x,y] = c;
    if(x<minX) minX=x;
    if(y<minY) minY=y;
    if(x>maxX) maxX=x;
    if(y>maxY) maxY=y;
  }
  return [minX, minY, maxX, maxY];
}

function registerServiceWorker(){
  if('serviceWorker' in navigator){
    navigator.serviceWorker.register('../../sw-map.js').catch(console.warn);
  }
}

// Lazy load images in popups when they open
map?.on('popupopen', e=>{
  const imgs = e.popup._content.querySelectorAll('img[data-src]');
  imgs.forEach(img=>{img.setAttribute('src', img.getAttribute('data-src'));});
});

// POI Functions
async function fetchNearbyPOIs(type, spotFeature) {
  if (isLoadingPOIs) return;
  
  const spotId = spotFeature.properties.id;
  const [lon, lat] = spotFeature.geometry.coordinates;
  
  // Update current selection
  currentSelectedSpot = spotId;
  
  // Update status
  const statusEl = document.querySelector('.poi-status');
  if (statusEl) statusEl.textContent = `Loading ${type}s...`;
  
  // Check cache first
  const cached = poiCache[spotId];
  if (cached && cached[type] && (Date.now() - cached.timestamp < POI_CACHE_EXPIRY)) {
    renderPOIMarkers(type, cached[type]);
    if (statusEl) statusEl.textContent = `Found ${cached[type].length} ${type}s`;
    return;
  }
  
  isLoadingPOIs = true;
  
  try {
    const radius = 2000; // 2km radius
    const query = buildOverpassQuery(type, lat, lon, radius);
    const response = await fetch('https://overpass-api.de/api/interpreter', {
      method: 'POST',
      body: query,
      headers: { 'Content-Type': 'text/plain' }
    });
    
    if (!response.ok) throw new Error(`Overpass API error: ${response.status}`);
    
    const data = await response.json();
    const pois = parseOverpassData(data, type);
    
    // Cache the results
    if (!poiCache[spotId]) poiCache[spotId] = { timestamp: Date.now() };
    poiCache[spotId][type] = pois;
    poiCache[spotId].timestamp = Date.now();
    
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
  const pois = [];
  
  for (const element of data.elements) {
    const tags = element.tags || {};
    const name = tags.name || tags.brand || tags['addr:housename'] || 
                 (type === 'restaurant' ? 'Restaurant' : 'Lodging');
    
    let lat, lon;
    if (element.type === 'node') {
      lat = element.lat;
      lon = element.lon;
    } else if (element.center) {
      lat = element.center.lat;
      lon = element.center.lon;
    } else continue; // Skip if no coordinates
    
    pois.push({
      osm_id: element.id,
      type: type,
      name: name,
      lat: lat,
      lon: lon,
      tags: tags
    });
  }
  
  return pois.slice(0, 50); // Limit to prevent map clutter
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
  for (const poi of pois) {
    const el = document.createElement('div');
    el.className = `poi-marker ${type}-marker`;
    
    if (type === 'restaurant') {
      el.style.cssText = 'width: 14px; height: 14px; background: #ef4444; border: 2px solid #fff; border-radius: 50%; box-shadow: 0 0 0 1px #111; font-size: 8px; display: flex; align-items: center; justify-content: center; color: white;';
      el.innerHTML = '🍽';
    } else {
      el.style.cssText = 'width: 14px; height: 14px; background: #0d9488; border: 2px solid #fff; transform: rotate(45deg); box-shadow: 0 0 0 1px #111; font-size: 8px; display: flex; align-items: center; justify-content: center; color: white;';
      el.innerHTML = '🏨';
    }
    
    el.title = poi.name;
    
    const popup = new maplibregl.Popup({ offset: 16 })
      .setHTML(`<div class="poi-popup">
        <h4>${escapeHtml(poi.name)}</h4>
        <div><strong>Type:</strong> ${escapeHtml(poi.tags.amenity || poi.tags.tourism || type)}</div>
        ${poi.tags.cuisine ? `<div><strong>Cuisine:</strong> ${escapeHtml(poi.tags.cuisine)}</div>` : ''}
        ${poi.tags.phone ? `<div><strong>Phone:</strong> ${escapeHtml(poi.tags.phone)}</div>` : ''}
        ${poi.tags.website ? `<div><a href="${escapeHtml(poi.tags.website)}" target="_blank">Website</a></div>` : ''}
      </div>`);
    
    const marker = new maplibregl.Marker(el)
      .setLngLat([poi.lon, poi.lat])
      .setPopup(popup)
      .addTo(map);
    
    poiMarkersById.set(`${type}_${poi.osm_id}`, marker);
  }
}

function clearPOIs() {
  // Remove all POI markers
  poiMarkersById.forEach(marker => marker.remove());
  poiMarkersById.clear();
  
  // Update status
  const statusEl = document.querySelector('.poi-status');
  if (statusEl) statusEl.textContent = '';
  
  currentSelectedSpot = null;
}
