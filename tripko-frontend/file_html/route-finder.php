<?php
// Modern Route Finder - TripKo Pangasinan
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Route Finder • TripKo Pangasinan</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
  <link rel="stylesheet" href="../file_css/route-finder.css" />
</head>
<body>
  <?php include_once __DIR__ . '/includes/navbar.php'; if(function_exists('renderNavbar')) renderNavbar(); ?>
  
  <main class="route-finder-container">
    <!-- Left Panel: Search & Controls -->
    <aside class="search-panel" id="searchPanel">
      <div class="panel-header">
        <h1><i class="bx bxs-map"></i> Plan Your Route</h1>
        <p class="subtitle">Find the best way to reach any destination in Pangasinan</p>
      </div>

      <div class="search-form">
        <div class="form-group">
          <label for="startInput">
            <i class="bx bxs-map-pin"></i>
            <span>Starting Point</span>
          </label>
          <div class="input-wrapper">
            <input 
              id="startInput" 
              list="startOptions" 
              placeholder="Select terminal or use current location"
              autocomplete="off"
            />
            <datalist id="startOptions"></datalist>
          </div>
          <button id="btnMyLocation" class="location-btn">
            <i class="bx bx-current-location"></i>
            <span>Use My Current Location</span>
          </button>
        </div>

        <div class="form-group">
          <label for="destInput">
            <i class="bx bxs-flag-alt"></i>
            <span>Destination</span>
          </label>
          <div class="input-wrapper">
            <input 
              id="destInput" 
              list="destOptions" 
              placeholder="Search for a tourist spot"
              autocomplete="off"
            />
            <datalist id="destOptions"></datalist>
          </div>
        </div>

        <div class="form-actions">
          <button id="btnFindRoute" class="btn-primary">
            <i class="bx bx-search-alt"></i>
            <span>Find Route</span>
          </button>
          <button id="btnClear" class="btn-secondary">
            <i class="bx bx-reset"></i>
            <span>Clear</span>
          </button>
        </div>
      </div>

      <div id="routeLoader" class="route-loader" aria-hidden="true" hidden>
        <div class="loader-animation">
          <div class="pulse"></div>
          <div class="pulse"></div>
          <div class="pulse"></div>
        </div>
        <p>Finding the best route for you...</p>
      </div>

      <div id="messages" class="alert-container" role="status" aria-live="polite"></div>

      <!-- Route Summary Card -->
      <div class="summary-card" id="summaryCard" style="display:none;">
        <div class="summary-header">
          <h3><i class="bx bxs-info-circle"></i> Route Details</h3>
        </div>
        
        <div class="summary-grid">
          <div class="summary-item highlight">
            <div class="item-icon"><i class="bx bxs-time"></i></div>
            <div class="item-content">
              <span class="item-label">Duration</span>
              <span class="item-value" id="sumDuration">—</span>
            </div>
          </div>
          
          <div class="summary-item highlight">
            <div class="item-icon"><i class="bx bxs-wallet"></i></div>
            <div class="item-content">
              <span class="item-label">Estimated Fare</span>
              <span class="item-value" id="sumFare">—</span>
            </div>
          </div>
          
          <div class="summary-item">
            <div class="item-icon"><i class="bx bxs-map"></i></div>
            <div class="item-content">
              <span class="item-label">Distance</span>
              <span class="item-value" id="sumDistance">—</span>
            </div>
          </div>

          <div class="summary-item">
            <div class="item-icon"><i class="bx bxs-bus"></i></div>
            <div class="item-content">
              <span class="item-label">Transport Mode</span>
              <span class="item-value" id="sumMode">—</span>
            </div>
          </div>
        </div>

        <div class="journey-breakdown">
          <h4><i class="bx bx-trip"></i> Journey Breakdown</h4>
          
          <div class="breakdown-item">
            <i class="bx bx-pin"></i>
            <div>
              <strong>From:</strong>
              <span id="sumStart">—</span>
            </div>
          </div>

          <div class="breakdown-item" id="walkSegment">
            <i class="bx bx-walk"></i>
            <div>
              <strong>Walk to Terminal:</strong>
              <span id="sumWalk">—</span>
            </div>
          </div>

          <div class="breakdown-item">
            <i class="bx bxs-bus-school"></i>
            <div>
              <strong>Via Terminal:</strong>
              <span id="sumServingTerminal">—</span>
            </div>
          </div>

          <div class="breakdown-item">
            <i class="bx bxs-car"></i>
            <div>
              <strong>Vehicle Travel:</strong>
              <span id="sumVehicle">—</span>
            </div>
          </div>

          <div class="breakdown-item">
            <i class="bx bxs-flag-checkered"></i>
            <div>
              <strong>To:</strong>
              <span id="sumDest">—</span>
            </div>
          </div>

          <div class="breakdown-item detail">
            <i class="bx bx-stats"></i>
            <div>
              <strong>Effective Distance:</strong>
              <span id="sumEffectiveKm">—</span>
            </div>
          </div>
        </div>

        <button id="btnStartNav" class="btn-nav" disabled>
          <i class="bx bx-navigation"></i>
          <span>Start Navigation in Google Maps</span>
        </button>
      </div>

      <!-- Attribution Footer -->
      <div class="attribution">
        <p><small>Map data © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors</small></p>
      </div>
    </aside>

    <!-- Right Panel: Map -->
    <div class="map-container">
      <div id="map" role="application" aria-label="Interactive route map"></div>
      
      <!-- Map Controls Overlay -->
      <div class="map-controls">
        <button id="togglePanel" class="map-btn" aria-label="Toggle search panel" title="Toggle panel">
          <i class="bx bx-menu"></i>
        </button>
        <button id="darkModeToggle" class="map-btn" aria-label="Toggle dark mode" title="Toggle theme">
          <i class="bx bxs-moon"></i>
        </button>
      </div>
    </div>
  </main>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
    window.TRIPKO_API_BASE = window.TRIPKO_API_BASE || '../../tripko-backend/api';
  </script>
  <script src="../file_js/route-finder.js"></script>
</body>
</html>
