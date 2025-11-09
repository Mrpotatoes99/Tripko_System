(() => {
  const API_BASE = window.TRIPKO_API_BASE || '../../tripko-backend/api';
  const endpoints = {
    spots: `${API_BASE}/tourist_spots.php`,
    terminals: `${API_BASE}/terminals.php`,
    route: `${API_BASE}/route_finder/`,
  };

  // Mobile viewport height fix
  function setViewportHeightVar() {
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
  }
  setViewportHeightVar();
  window.addEventListener('resize', () => {
    setViewportHeightVar();
    if (map) setTimeout(() => map.invalidateSize(), 100);
  });
  window.addEventListener('orientationchange', () => {
    setViewportHeightVar();
    if (map) setTimeout(() => map.invalidateSize(), 150);
  });

  // Leaflet map init
  const map = L.map('map');
  const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Icons
  const iconSpot = L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34],
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', shadowSize: [41, 41]
  });
  const iconTerminal = L.divIcon({
    className: 'terminal-icon',
    html: '<div class="dot blue"></div>',
    iconSize: [16, 16], iconAnchor: [8, 8]
  });

  const state = {
    terminals: [],
    spots: [],
    markers: {
      terminals: L.layerGroup().addTo(map),
      spots: L.layerGroup().addTo(map),
      route: L.layerGroup().addTo(map),
    },
    selection: {
      start: null,
      dest: null,
    },
  };

  // DOM Elements
  const $ = (sel) => document.querySelector(sel);
  const startInput = $('#startInput');
  const destInput = $('#destInput');
  const startOptions = $('#startOptions');
  const destOptions = $('#destOptions');
  const btnMyLocation = $('#btnMyLocation');
  const btnFindRoute = $('#btnFindRoute');
  const btnClear = $('#btnClear');
  const btnStartNav = $('#btnStartNav');
  const togglePanel = $('#togglePanel');
  const searchPanel = $('#searchPanel');
  const darkModeToggle = $('#darkModeToggle');
  const loader = $('#routeLoader');
  const messages = $('#messages');
  const summaryCard = $('#summaryCard');
  const walkSegment = $('#walkSegment');
  const sumStart = $('#sumStart');
  const sumDest = $('#sumDest');
  const sumDistance = $('#sumDistance');
  const sumDuration = $('#sumDuration');
  const sumFare = $('#sumFare');
  const sumServingTerminal = $('#sumServingTerminal');
  const sumWalk = $('#sumWalk');
  const sumVehicle = $('#sumVehicle');
  const sumMode = $('#sumMode');
  const sumEffectiveKm = $('#sumEffectiveKm');

  function showMessage(text, type = 'info') {
    const div = document.createElement('div');
    div.className = `msg ${type}`;
    div.textContent = text;
    messages.appendChild(div);
    setTimeout(() => div.remove(), 6000);
  }

  function setLoading(isLoading) {
    if (!loader) return;
    if (isLoading) {
      loader.hidden = false;
      loader.setAttribute('aria-hidden','false');
    } else {
      loader.hidden = true;
      loader.setAttribute('aria-hidden','true');
    }
  }

  function fitAllMarkers() {
    const bounds = L.latLngBounds([]);
    state.markers.terminals.eachLayer((m) => bounds.extend(m.getLatLng()));
    state.markers.spots.eachLayer((m) => bounds.extend(m.getLatLng()));
    if (bounds.isValid()) {
      map.fitBounds(bounds.pad(0.2));
    } else {
      map.setView([16.15, 119.95], 9);
    }
  }

  function addMarkers() {
    state.markers.terminals.clearLayers();
    state.markers.spots.clearLayers();

    state.terminals.filter(t => t.has_coordinates).forEach(t => {
      const m = L.marker([t.latitude, t.longitude], { icon: iconTerminal })
        .bindPopup(`<b>${t.name}</b><br/>${t.address || ''}`);
      m.on('click', () => {
        state.selection.start = { type: 'terminal', id: t.id, label: t.name, lat: t.latitude, lng: t.longitude };
        startInput.value = t.name;
      });
      state.markers.terminals.addLayer(m);
    });

    state.spots.filter(s => s.has_coordinates).forEach(s => {
      const img = s.image_path ? `<br/><img src="../../uploads/${s.image_path}" alt="${s.name}" style="width:100px;height:auto;border-radius:6px;margin-top:6px;"/>` : '';
      const m = L.marker([s.latitude, s.longitude], { icon: iconSpot })
        .bindPopup(`<b>${s.name}</b><br/>${s.category || ''}${img}`);
      m.on('click', () => {
        state.selection.dest = { type: 'spot', id: s.id, label: s.name, lat: s.latitude, lng: s.longitude };
        destInput.value = s.name;
      });
      state.markers.spots.addLayer(m);
    });
  }

  function populateAutocomplete() {
    startOptions.innerHTML = '';
    destOptions.innerHTML = '';
    state.terminals.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.name;
      startOptions.appendChild(opt);
    });
    state.spots.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.name;
      destOptions.appendChild(opt);
    });
  }

  function resolveStartByName(name) {
    const t = state.terminals.find(x => x.name.toLowerCase() === name.toLowerCase());
    if (t && t.has_coordinates) {
      return { type: 'terminal', id: t.id, label: t.name, lat: t.latitude, lng: t.longitude };
    }
    return null;
  }

  function resolveDestByName(name) {
    const s = state.spots.find(x => x.name.toLowerCase() === name.toLowerCase());
    if (s && s.has_coordinates) {
      return { type: 'spot', id: s.id, label: s.name, lat: s.latitude, lng: s.longitude };
    }
    return null;
  }

  async function loadData() {
    try {
      const [spots, terminals] = await Promise.all([
        fetch(endpoints.spots).then(r => r.json()),
        fetch(endpoints.terminals).then(r => r.json()),
      ]);
      if (!spots.success) throw new Error('Failed to load spots');
      if (!terminals.success) throw new Error('Failed to load terminals');
      state.spots = spots.data;
      state.terminals = terminals.data;
      addMarkers();
      populateAutocomplete();
      fitAllMarkers();
    } catch (e) {
      console.error(e);
      showMessage('Failed to load map data. Please refresh.', 'error');
      map.setView([16.15, 119.95], 9);
    }
  }

  function clearRoute() {
    state.markers.route.clearLayers();
    sumStart.textContent = '—';
    sumDest.textContent = '—';
    sumDistance.textContent = '—';
    sumDuration.textContent = '—';
    sumFare.textContent = '—';
    if (sumServingTerminal) sumServingTerminal.textContent = '—';
    if (sumWalk) sumWalk.textContent = '—';
    if (sumVehicle) sumVehicle.textContent = '—';
    if (sumMode) sumMode.textContent = '—';
    if (sumEffectiveKm) sumEffectiveKm.textContent = '—';
    btnStartNav.disabled = true;
    if (summaryCard) summaryCard.style.display = 'none';
  }

  async function findRoute() {
    messages.innerHTML = '';
    
    if (!state.selection.start && startInput.value.trim()) {
      state.selection.start = resolveStartByName(startInput.value.trim());
    }
    if (!state.selection.dest && destInput.value.trim()) {
      state.selection.dest = resolveDestByName(destInput.value.trim());
    }

    if (!state.selection.dest) {
      showMessage('Please select a destination tourist spot', 'error');
      return;
    }
    if (!state.selection.start) {
      showMessage('Please select a starting terminal or use your location', 'error');
      return;
    }

    const payload = { destination_id: state.selection.dest.id };
    if (state.selection.start.type === 'terminal') {
      payload.start_type = 'terminal';
      payload.start_id = state.selection.start.id;
    } else {
      payload.start_type = 'coords';
      payload.start_lat = state.selection.start.lat;
      payload.start_lng = state.selection.start.lng;
    }

    try {
      setLoading(true);
      const res = await fetch(endpoints.route, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (!data.success) {
        console.error('Route API error:', data);
        if (data.error && location.hostname === 'localhost') {
          showMessage(data.error, 'error');
        }
        throw new Error(data.message || 'No route found');
      }

      const r = data.route;
      clearRoute();
      
      // Draw route
      const line = L.polyline(r.polyline, { color: '#2563eb', weight: 5 }).addTo(state.markers.route);
      L.circleMarker([r.start.latitude, r.start.longitude], { radius: 7, color: '#2563eb' })
        .addTo(state.markers.route).bindPopup('<b>Start:</b> ' + r.start.label);
      L.circleMarker([r.destination.latitude, r.destination.longitude], { radius: 7, color: '#10b981' })
        .addTo(state.markers.route).bindPopup('<b>Destination:</b> ' + r.destination.label);

      if (r.terminals_used && r.terminals_used.serving_terminal) {
        const st = r.terminals_used.serving_terminal;
        if (st && st.latitude != null && st.longitude != null) {
          L.circleMarker([st.latitude, st.longitude], { radius: 6, color: '#f59e0b' })
            .addTo(state.markers.route)
            .bindPopup('<b>Serving Terminal:</b> ' + (st.name || ''));
        }
      }
      map.fitBounds(line.getBounds().pad(0.25));

      // Update summary
      sumStart.textContent = r.start.label;
      sumDest.textContent = r.destination.label;
      sumDistance.textContent = `${r.distance_km.toFixed(2)} km`;
      sumDuration.textContent = `${r.duration_minutes} min`;
      
      if (r.fare) {
        const amt = Number(r.fare.amount);
        const low = Math.max(0, amt * 0.9);
        const high = amt * 1.15;
        sumFare.textContent = `₱${amt.toFixed(2)}`;        
        sumFare.title = `Likely range: ₱${low.toFixed(2)} - ₱${high.toFixed(2)}`;
      } else {
        sumFare.textContent = '—';
      }

      if (r.legs) {
        const serving = r.terminals_used && r.terminals_used.serving_terminal ? r.terminals_used.serving_terminal : null;
        if (sumServingTerminal) sumServingTerminal.textContent = serving ? serving.name : '—';
        
        if (r.legs.terminal_start) {
          if (walkSegment) walkSegment.setAttribute('data-hide', 'true');
          const totalVehKm = (r.legs.start_terminal_to_serving_terminal_km || 0) + (r.legs.serving_terminal_to_destination_km || 0);
          if (sumVehicle) sumVehicle.textContent = `${totalVehKm.toFixed(2)} km • ${r.legs.vehicle_minutes} min`;
        } else {
          if (walkSegment) walkSegment.removeAttribute('data-hide');
          const kmWalk = r.legs.user_to_terminal_km != null ? `${Number(r.legs.user_to_terminal_km).toFixed(2)} km` : '—';
          const minWalk = r.legs.walk_minutes != null ? `${r.legs.walk_minutes} min` : '';
          if (sumWalk) sumWalk.textContent = r.legs.user_to_terminal_km != null ? `${kmWalk} • ${minWalk}` : '—';
          const kmVeh = r.legs.terminal_to_destination_km != null ? `${Number(r.legs.terminal_to_destination_km).toFixed(2)} km` : '—';
          const minVeh = r.legs.vehicle_minutes != null ? `${r.legs.vehicle_minutes} min` : '';
          if (sumVehicle) sumVehicle.textContent = r.legs.terminal_to_destination_km != null ? `${kmVeh} • ${minVeh}` : '—';
        }

        if (r.legs.selected_mode) {
          const sm = r.legs.selected_mode;
          const parts = [];
          if (sm.type_name) parts.push(sm.type_name);
          if (sumMode) sumMode.textContent = sm.type_name || '—';
          if (sumEffectiveKm && sm.effective_km != null) sumEffectiveKm.textContent = `${Number(sm.effective_km).toFixed(2)} km`;
        } else {
          if (sumMode) sumMode.textContent = '—';
        }
      } else {
        if (sumServingTerminal) sumServingTerminal.textContent = '—';
        if (sumWalk) sumWalk.textContent = '—';
        if (sumVehicle) sumVehicle.textContent = '—';
        if (sumMode) sumMode.textContent = '—';
        if (sumEffectiveKm) sumEffectiveKm.textContent = '—';
      }
      
      if (!r.fare) {
        showMessage('No fare data available for this route.', 'warn');
      }
      
      btnStartNav.disabled = false;
      if (summaryCard) summaryCard.style.display = 'block';
    } catch (e) {
      console.error(e);
      showMessage('Could not compute route. Try another destination.', 'error');
    }
    finally { setLoading(false); }
  }

  // Event Handlers
  if (btnMyLocation) {
    btnMyLocation.addEventListener('click', () => {
      messages.innerHTML = '';
      if (!navigator.geolocation) {
        showMessage('Geolocation not supported', 'error');
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const { latitude, longitude } = pos.coords;
          state.selection.start = { type: 'coords', id: null, label: 'My Location', lat: latitude, lng: longitude };
          startInput.value = 'My Location';
          L.circleMarker([latitude, longitude], { radius: 6, color: '#2563eb' })
            .addTo(state.markers.route)
            .bindPopup('<b>You are here</b>');
          map.setView([latitude, longitude], 13);
          showMessage('Location acquired successfully', 'info');
        },
        (err) => {
          console.warn(err);
          showMessage('Location permission denied or unavailable', 'error');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  }

  if (btnFindRoute) btnFindRoute.addEventListener('click', findRoute);
  
  if (btnClear) {
    btnClear.addEventListener('click', () => {
      startInput.value = '';
      destInput.value = '';
      state.selection.start = null;
      state.selection.dest = null;
      clearRoute();
      fitAllMarkers();
    });
  }

  if (btnStartNav) {
    btnStartNav.addEventListener('click', () => {
      const r = { start: state.selection.start, dest: state.selection.dest };
      if (!r.start || !r.dest) return;
      const url = `https://www.google.com/maps/dir/?api=1&origin=${r.start.lat},${r.start.lng}&destination=${r.dest.lat},${r.dest.lng}`;
      window.open(url, '_blank');
    });
  }

  if (togglePanel) {
    togglePanel.addEventListener('click', () => {
      searchPanel.classList.toggle('hidden');
      setTimeout(() => map.invalidateSize(), 300);
    });
  }

  if (darkModeToggle) {
    // Load saved preference
    const savedTheme = localStorage.getItem('tripko-theme');
    if (savedTheme === 'dark') {
      document.body.classList.add('dark');
      darkModeToggle.innerHTML = '<i class="bx bxs-sun"></i>';
    }
    
    darkModeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      const isDark = document.body.classList.contains('dark');
      darkModeToggle.innerHTML = isDark ? '<i class="bx bxs-sun"></i>' : '<i class="bx bxs-moon"></i>';
      localStorage.setItem('tripko-theme', isDark ? 'dark' : 'light');
    });
  }

  // Initialize
  loadData();
})();
