<?php
session_start();
require_once('../../../tripko-backend/config/check_session.php');
checkAdminSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TripKo Pangasinan Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js with required dependencies -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
  <link rel="stylesheet" href="../../file_css/dashboard.css" />
  <style>
    body {
        font-family: 'poppins';
        font-size: 17px;
    }

    .text-2xl {
        font-family: 'poppins';
        font-size: 17px;
    }

    nav a, 
    .nav-link {
      font-size: 17px;
    }
    .text-lg,
    .font-semibold,
    .font-medium,
    h3,
    .text-lg {
      font-size: 17px;
    }
    p {
        font-family: 'poppins';
    }

    .stats-card h3 {
      font-size: 17px;
    }

    .font-semibold,
    .font-medium,
    p {
        font-size: 17px;
    }

     #transportDropdown a {
        font-size: 17px;
    }

    .text-sm {
        font-size: 17px; /* Slightly smaller for labels */
    }

    .chart-container {
        font-family: 'poppins';
    }

    .chart-container {
      position: relative;
      height: 300px;
      width: 100%;
    }
    canvas#tourismChart, canvas#transportChart {
      width: 100% !important;
      height: 100% !important;
    }
    canvas#transportChart {
      position: absolute !important;
      top: 0;
      left: 0;
    }
  </style>
  <script>
document.addEventListener('DOMContentLoaded', () => {
    const transportDropdown = document.getElementById('transportDropdown');
    const transportDropdownIcon = document.getElementById('transportDropdownIcon');

    // Close transport dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#transportDropdown') && !e.target.closest('[onclick*="toggleTransportDropdown"]')) {
            transportDropdown?.classList.add('hidden');
            if (transportDropdownIcon) {
                transportDropdownIcon.style.transform = 'rotate(0deg)';
            }
        }
        
        // Close admin dropdown when clicking outside
        if (!e.target.closest('#adminDropdown')) {
            document.getElementById('adminDropdownContent')?.classList.add('hidden');
        }
    });
});

function toggleTransportDropdown(event) {
    event.preventDefault();
    const dropdown = document.getElementById('transportDropdown');
    const icon = document.getElementById('transportDropdownIcon');
    dropdown.classList.toggle('hidden');
    icon.style.transform = dropdown.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

function toggleAdminDropdown(event) {
    event.preventDefault();
    const dropdown = document.getElementById('adminDropdownContent');
    dropdown.classList.toggle('hidden');
}
  </script>
</head>
<body class="bg-white text-gray-900">
  <div class="flex min-h-screen">
  <!-- Sidebar -->
  <?php 
    // Adjusted include path: "includes" is sibling of this Super Admin folder under file_html
    $sidebarPath = dirname(__DIR__) . '/includes/sidebar.php';
    if (file_exists($sidebarPath)) {
      include $sidebarPath; 
    } else {
      echo '<!-- Sidebar include missing: ' . htmlspecialchars($sidebarPath) . ' -->';
    }
  ?>

    <!-- Main content -->
    <main class="flex-1 bg-[#F3F1E7] p-6">
      <header class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3 text-gray-900 font-normal text-base">
          <button aria-label="Menu" class="focus:outline-none">
            <i class="fas fa-bars text-lg"></i>
          </button>
          <span class="ml-3">Dashboard Overview</span>
        </div>
        <div class="flex items-center gap-4">
          <select id="dashboardPeriod" class="rounded-full border border-gray-400 bg-[#F3F1E7] py-1.5 px-4 text-gray-600">
            <option value="7">Last 7 days</option>
            <option value="30" selected>Last 30 days</option>
            <option value="90">Last 3 months</option>
            <option value="365">Last year</option>
          </select>
          <div class="relative" id="adminDropdown">
            <button aria-label="Admin Profile" class="text-black text-xl focus:outline-none flex items-center gap-2" onclick="toggleAdminDropdown(event)">
              <i class="fas fa-user-circle"></i>
              <span class="text-sm font-medium">Administrator</span>
              <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div id="adminDropdownContent" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
              <a href="../../../tripko-backend/config/confirm_logout.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span class="ml-2">Sign Out</span>
              </a>
            </div>
          </div>
        </div>
      </header>

      <!-- Stats Cards -->
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md stats-card">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-blue-100 rounded-full">
              <i class="fas fa-users text-[#255D8A] text-xl"></i>
            </div>
            <span class="text-sm font-medium text-gray-400">Total Visitors</span>
          </div>
          <h3 class="text-4xl font-medium text-gray-700" id="totalVisitors">Loading...</h3>
          <p class="text-sm text-gray-500 mt-2" id="visitorsTrend"></p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md stats-card">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-green-100 rounded-full">
              <i class="fas fa-map-marker-alt text-green-600 text-xl"></i>
            </div>
            <span class="text-sm font-medium text-gray-400">Popular Destination</span>
          </div>
          <h3 class="text-4xl font-medium text-gray-700" id="popularSpot">Loading...</h3>
          <p class="text-sm text-gray-500 mt-2" id="spotVisits"></p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md stats-card">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-purple-100 rounded-full">
              <i class="fas fa-user-plus text-purple-600 text-xl"></i>
            </div>
            <span class="text-sm font-medium text-gray-400">New Users</span>
          </div>
          <h3 class="text-4xl font-medium text-gray-700" id="newUsers">Loading...</h3>
          <p class="text-sm text-gray-500 mt-2" id="usersTrend"></p>
        </div>
      </section>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Tourism Trends -->
        <div class="bg-white p-6 rounded-lg shadow-md stats-card">
          <h3 class="text-lg font-medium mb-4 text-[#255D8A]">Tourism Trends</h3>
          <div class="chart-container relative">
            <canvas id="tourismChart"></canvas>
            <div id="tourismChartError" class="error-message hidden"></div>
            <div id="tourismChartLoading" class="loading-overlay">
              <div class="text-gray-500">Loading...</div>
            </div>
          </div>
        </div>

        <!-- Transportation Analytics -->
        <div class="bg-white p-6 rounded-lg shadow-md stats-card">
          <h3 class="text-lg font-medium mb-4 text-[#255D8A]">Transportation Distribution</h3>
          <div class="relative h-[300px] w-full">
            <canvas id="transportChart" style="z-index: 1;"></canvas>
            <div id="transportChartLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 z-10 hidden">
              <div class="text-gray-500">Loading...</div>
            </div>
            <div id="transportChartError" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 z-10 hidden">
              <div class="text-red-500"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activities Section -->
      <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Spots -->
        <div class="bg-white p-6 rounded-lg shadow-md">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-[#255D8A]">Recent Tourist Spots</h3>
            <a href="tourist_spot.php" class="text-[#255D8A] hover:underline text-sm">View All</a>
          </div>
          <div class="space-y-4" id="recentSpots">
            Loading...
          </div>
        </div>

        <!-- Recent Routes -->
        <div class="bg-white p-6 rounded-lg shadow-md">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-[#255D8A]">Recent Routes</h3>
            <a href="terminal-routes.html" class="text-[#255D8A] hover:underline text-sm">View All</a>
          </div>
          <div class="space-y-4" id="recentRoutes">
            Loading...
          </div>
        </div>

        <!-- Recent Users (Hidden until user system is implemented) -->
        <div class="bg-white p-6 rounded-lg shadow-md hidden">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Recent Users</h3>
            <a href="#" class="text-[#255D8A] hover:underline text-sm">View All</a>
          </div>
          <div class="space-y-4" id="recentUsers">
            <div class="text-gray-500">User tracking coming soon</div>
          </div>
        </div>
      </section>
      <!-- Main content section end -->
      </main>
    </div>

    <script>
      let charts = {
        tourism: null,
        transport: null
      };

      function destroyCharts() {
        Object.values(charts).forEach(chart => {
          if (chart) {
            chart.destroy();
          }
        });
      }

      function updateElement(id, content) {
        const element = document.getElementById(id);
        if (element) {
          element.innerHTML = content;
        }
      }

      function updateTourismChart(data) {
        const ctx = document.getElementById('tourismChart').getContext('2d');
        const gradientFill = ctx.createLinearGradient(0, 0, 0, 400);
        gradientFill.addColorStop(0, 'rgba(37, 93, 138, 0.3)');
        gradientFill.addColorStop(1, 'rgba(37, 93, 138, 0.02)');

        charts.tourism = new Chart(ctx, {
          type: 'line',
          data: {
            labels: data.map(d => d.month),
            datasets: [{
              label: 'Monthly Visitors',
              data: data.map(d => d.count),
              borderColor: '#255D8A',
              backgroundColor: gradientFill,
              tension: 0.4,
              fill: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#255D8A',
                titleFont: { weight: '600' },
                bodyColor: '#666',
                bodyFont: { size: 13 },
                borderColor: '#ddd',
                borderWidth: 1,
                padding: 12,
                displayColors: false,
                callbacks: {
                  label: (context) => `Visitors: ${context.parsed.y.toLocaleString()}`
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: { 
                  callback: value => value.toLocaleString(),
                  font: { size: 11 }
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.05)'
                }
              },
              x: {
                ticks: { 
                  font: { size: 11 }
                },
                grid: {
                  display: false
                }
              }
            }
          }
        });
      }

      function updateTransportChart(data) {
        const ctx = document.getElementById('transportChart').getContext('2d');
        charts.transport = new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: data.map(d => d.type),
            datasets: [{
              data: data.map(d => d.count),
              backgroundColor: [
                '#255D8A',
                '#37799E',
                '#4A96B2',
                '#5DB3C6',
                '#70D0DA'
              ]
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
              legend: { 
                position: 'bottom',
                labels: { 
                  font: { size: 11 },
                  padding: 20,
                  generateLabels: (chart) => {
                    const data = chart.data;
                    const total = data.datasets[0].data.reduce((sum, value) => sum + value, 0);
                    return data.labels.map((label, i) => ({
                      text: `${label} (${data.datasets[0].data[i]} routes, ${Math.round((data.datasets[0].data[i]/total)*100)}%)`,
                      fillStyle: chart.data.datasets[0].backgroundColor[i],
                      index: i
                    }));
                  }
                }
              }
            }
          }
        });
      }

      function updateRecentSection(id, items) {
        const container = document.getElementById(id);
        if (!container) return;

        container.innerHTML = items.map(item => `
          <div class="p-4 bg-gray-50 rounded-lg">
            <h4 class="font-medium text-gray-900">${item.title}</h4>
            <p class="text-sm text-gray-600 mt-1">${item.subtitle}</p>
            <p class="text-xs text-gray-500 mt-2">${item.date}</p>
          </div>
        `).join('');
      }

      async function loadDashboardData() {
        let timeoutId;
        try {
          console.log('Starting dashboard data load...');
              
          // Show loading states
          document.querySelectorAll('.loading-overlay').forEach(el => el.classList.remove('hidden'));
          document.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));

          // Show loading in stats cards
          ['totalVisitors', 'popularSpot', 'popularRoute', 'newUsers'].forEach(id => {
            updateElement(id, 'Loading...');
          });
          ['visitorsTrend', 'spotVisits', 'routeUsage', 'usersTrend'].forEach(id => {
            updateElement(id, '');
          });

          // Set request timeout
          const controller = new AbortController();
          timeoutId = setTimeout(() => controller.abort(), 30000); // 30 sec timeout

          destroyCharts();

          const period = document.getElementById('dashboardPeriod').value;
          // Corrected path depth (Super Admin is two levels below project root relative to tripko-backend link target)
          const apiUrl = `../../../tripko-backend/api/reports/get_reports.php?period=${period}`;
          console.log('Fetching from URL:', apiUrl);
          
          const response = await fetch(apiUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'Cache-Control': 'no-cache'
            },
            signal: controller.signal
          });
          
          console.log('Response status:', response.status);
          console.log('Response headers:', Object.fromEntries(response.headers));
          
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const responseText = await response.text();
          console.log('Raw response:', responseText);

          if (!responseText.trim()) {
            throw new Error('Empty response received from server');
          }

          let data;
          try {
            data = JSON.parse(responseText);
            console.log('Parsed data:', data);
          } catch (parseError) {
            console.error('Error parsing JSON response:', parseError);
            throw new Error('Invalid JSON response: ' + parseError.message);
          }

          if (!data.success) {
            throw new Error(data.message || 'Failed to load dashboard data');
          }

          if (!data.data) {
            throw new Error('Missing data property in response');
          }

          // Clear error states
          document.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));

          // Process tourism data
          console.log('Processing tourism data:', data.data.tourism);
          const tourismChartLoading = document.getElementById('tourismChartLoading');
          if (tourismChartLoading) {
            tourismChartLoading.classList.add('hidden');
          }
            
          if (data.data.tourism?.monthlyData?.length > 0) {
            console.log('Updating tourism chart with data:', data.data.tourism.monthlyData);
            updateTourismChart(data.data.tourism.monthlyData);
          } else {
            console.warn('No monthly tourism data available');
            const tourismContainer = document.getElementById('tourismChart')?.parentElement;
            if (tourismContainer) {
              tourismContainer.innerHTML = '<div class="flex items-center justify-center h-64 text-gray-500">No visitor data available</div>';
            }
          }

          // Process transport data
          console.log('Processing transport data:', data.data.transport);
          const transportChartLoading = document.getElementById('transportChartLoading');
          if (transportChartLoading) {
            transportChartLoading.classList.add('hidden');
          }

          if (data.data.transport?.typeDistribution?.length > 0) {
            console.log('Updating transport chart with data:', data.data.transport.typeDistribution);
            updateTransportChart(data.data.transport.typeDistribution);
          } else {
            console.warn('No transport distribution data available');
            const transportContainer = document.getElementById('transportChart')?.parentElement;
            if (transportContainer) {
              transportContainer.innerHTML = '<div class="flex items-center justify-center h-64 text-gray-500">No transport data available</div>';
            }
          }

          // Update statistics cards with proper null checks
          updateElement('totalVisitors', (data.data.tourism?.totalVisitors ?? 0).toLocaleString());
          
          const trend = data.data.tourism?.visitorTrend ?? 0;
          updateElement('visitorsTrend', trend !== 0 ? `
            <span class="${trend >= 0 ? 'text-green-600' : 'text-red-600'}">
            ${trend >= 0 ? '↑' : '↓'} ${Math.abs(trend).toFixed(1)}%
            </span> vs previous period` : 'No trend data');

          updateElement('popularSpot', data.data.tourism?.popularSpot || 'No data');
          updateElement('spotVisits', data.data.tourism?.popularSpotLocation ? 
            `Location: ${data.data.tourism.popularSpotLocation}` : '');
            
          updateElement('popularRoute', data.data.transport?.popularRoute?.name || 'No data');
          if (data.data.transport?.popularRoute?.fromTown && data.data.transport?.popularRoute?.toTown) {
            updateElement('routeUsage', 
              `${data.data.transport.popularRoute.fromTown} → ${data.data.transport.popularRoute.toTown}`);
          } else {
            updateElement('routeUsage', '');
          }

          // Update recent sections
          if (data.data.recentSpots?.length > 0) {
            updateRecentSection('recentSpots', data.data.recentSpots.map(spot => ({
              title: spot.name,
              subtitle: spot.location,
              date: spot.added_date
            })));
          }

          if (data.data.recentRoutes?.length > 0) {
            updateRecentSection('recentRoutes', data.data.recentRoutes.map(route => ({
              title: route.name,
              subtitle: `${route.from_town} → ${route.to_town}`,
              date: route.added_date
            })));
          }
        } catch (error) {
          console.error('Dashboard data load error:', error);
          
          // Show error messages in charts
          const errorMessage = error.name === 'AbortError' ? 'Request timed out' : error.message;
          ['tourismChart', 'transportChart'].forEach(id => {
            const container = document.getElementById(id)?.parentElement;
            if (container) {
              container.innerHTML = `
                <div class="flex items-center justify-center h-64">
                  <div class="text-center text-red-500">
                    <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                    <p>${errorMessage}</p>
                    <button onclick="loadDashboardData()" class="mt-4 px-4 py-2 bg-[#255D8A] text-white rounded hover:bg-[#1e4d70]">
                      Try Again
                    </button>
                  </div>
                </div>
              `;
            }
          });

          // Show error in stats cards
          ['totalVisitors', 'popularSpot', 'popularRoute', 'newUsers'].forEach(id => {
            updateElement(id, 'Error loading data');
          });
          ['visitorsTrend', 'spotVisits', 'routeUsage', 'usersTrend'].forEach(id => {
            updateElement(id, '');
          });

          // Show error in recent sections
          ['recentSpots', 'recentRoutes'].forEach(id => {
            const container = document.getElementById(id);
            if (container) {
              container.innerHTML = `
                <div class="text-red-500">
                  <i class="fas fa-exclamation-circle mr-2"></i>
                  Failed to load data
                </div>
              `;
            }
          });
        } finally {
          if (timeoutId) clearTimeout(timeoutId);
          document.querySelectorAll('.loading-overlay').forEach(el => el.classList.add('hidden'));
        }
      }

      // Initialize dashboard on page load
      document.addEventListener('DOMContentLoaded', () => {
        loadDashboardData();
      });

      // Reload data when period changes
      document.getElementById('dashboardPeriod').addEventListener('change', () => {
        loadDashboardData();
      });
    </script>
  </body>
</html>