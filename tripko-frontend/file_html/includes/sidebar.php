<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$__ROLE = $_SESSION['user_type_id'] ?? null; // 3 = tourism officer
?>
<!-- Sidebar -->
    <aside class="flex flex-col w-72 bg-[#255d8a] text-white dashboard-sidebar">
      <!-- Logo and Brand -->
       <div class="p-6 border-b border-[#1e4d70]">
        <div class="flex items-center space-x-4">
          <div class="p-2 bg-white bg-opacity-10 rounded-lg">
            <i class="fas fa-compass text-3xl"></i>
          </div>
          <div>
            <h1 class="text-2xl font-medium">TripKo</h1>
            <p class="text-sm text-blue-200">Pangasinan Tourism</p>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
       <nav class="flex-1">
        <!-- Dashboard -->
        <a href="/tripko-system/tripko-frontend/file_html/dashboard.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
            <i class="fas fa-home w-6"></i>
            <span class="ml-3">Dashboard</span>
        </a>

        <!-- Tourism Office Section -->
        <div class="mt-6">
          <p class="px-4 text-xs font-semibold text-blue-300 uppercase">Tourism Office</p>
          <div class="mt-3 space-y-2">
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/dashboard.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-building w-6"></i>
              <span class="ml-3">Office Dashboard</span>
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourist_spots.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-umbrella-beach w-6"></i>
              <span class="ml-3">Tourist Spots</span>
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/festivals.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-calendar-alt w-6"></i>
              <span class="ml-3">Festivals</span>
            </a>
            <?php if ($__ROLE == 3): ?>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/set_locations.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-map-pin w-6"></i>
              <span class="ml-3">Set Locations</span>
            </a>
            <?php endif; ?>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/itineraries.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-map-marked-alt w-6"></i>
              <span class="ml-3">Itineraries</span>
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/update_capacity.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-users-cog w-6"></i>
              <span class="ml-3">Update Capacity</span>
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-cash-register w-6"></i>
              <span class="ml-3">Tourism Fee Log</span>
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log_list.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-file-alt w-6"></i>
              <span class="ml-3">Fee Log Report</span>
            </a>
          </div>
        </div>

        <!-- Transportation Section -->
        <div class="mt-6">
          <p class="px-4 text-xs font-semibold text-blue-300 uppercase">Transportation</p>
          <div class="mt-3 space-y-2">
            <button onclick="toggleTransportDropdown(event)" class="w-full flex items-center justify-between px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <div class="flex items-center">
                <i class="fas fa-bus w-6"></i>
                <span class="ml-3">Transport Info</span>
              </div>
              <i class="fas fa-chevron-down text-sm transition-transform duration-200 rotate-180" id="transportDropdownIcon"></i>
            </button>
            <div id="transportDropdown" class="pl-4 space-y-2">
              <a href="/tripko-system/tripko-frontend/file_html/terminal-locations.html" class="flex items-center px-4 py-2 text-blue-200 hover:text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
                <i class="fas fa-map-marker-alt w-6"></i>
                <span class="ml-3">Terminals</span>
              </a>
              <a href="/tripko-system/tripko-frontend/file_html/terminal-routes.html" class="flex items-center px-4 py-2 text-blue-200 hover:text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
                <i class="fas fa-route w-6"></i>
                <span class="ml-3">Routes & Types</span>
              </a>
              <a href="/tripko-system/tripko-frontend/file_html/fare.html" class="flex items-center px-4 py-2 text-blue-200 hover:text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
                <i class="fas fa-money-bill-wave w-6"></i>
                <span class="ml-3">Fare Rates</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Management Section -->
        <div class="mt-6">
          <p class="px-4 text-xs font-semibold text-blue-300 uppercase">Management</p>
          <div class="mt-3 space-y-2">
            <a href="/tripko-system/tripko-frontend/file_html/users.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-users w-6"></i>
              <span class="ml-3">Users</span>
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/reports.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-chart-bar w-6"></i>
              <span class="ml-3">Reports</span>
            </a>
               <a href="towns.php" class="flex items-center px-4 py-3 text-white hover:bg-[#1e4d70] rounded-lg transition-colors group">
              <i class="fas fa-chart-bar w-6"></i>
              <span class="ml-3">Towns</span>
            </a>
          </div>
        </div>
      </nav>

      <!-- User Profile -->
      <div class="p-6 border-t border-[#1e4d70]">
        <div class="flex items-center space-x-4">
          <div class="p-2 bg-white bg-opacity-10 rounded-full">
            <i class="fas fa-user-circle text-2xl"></i>
          </div>
          <div>
            <h3 class="font-medium">Administrator</h3>
            <a href="/tripko-system/tripko-backend/config/confirm_logout.php" class="text-sm text-blue-200 hover:text-white group flex items-center mt-1">
              <i class="fas fa-sign-out-alt mr-2"></i>
              <span>Sign Out</span>
            </a>
          </div>
        </div>
      </div>
    </aside>