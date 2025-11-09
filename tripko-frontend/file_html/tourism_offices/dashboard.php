<?php
require_once('../../../tripko-backend/config/Database.php');
require_once('../../../tripko-backend/config/check_session.php');
checkTourismOfficerSession();

// Establish database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed");
}

// Get the tourism officer's municipality info
$user_id = $_SESSION['user_id'];
$query = "SELECT t.name as town_name, t.town_id 
          FROM towns t 
          INNER JOIN user u ON u.town_id = t.town_id 
          WHERE u.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$town_data = $result->fetch_assoc();

$town_name = $town_data['town_name'];
$town_id = $town_data['town_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourism Officer Dashboard - <?php echo htmlspecialchars($town_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/tourism-dashboard.css" />
</head>
<body>
    <!-- Navigation -->
    <nav class="tourism-nav">
        <div class="nav-brand">
            <h1>TripKo Tourism</h1>
            <span class="nav-location">| <?php echo htmlspecialchars($town_name); ?></span>
        </div>
        <div class="nav-actions">
            <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode" title="Toggle theme">
                <i class="bx bxs-moon"></i>
            </button>
            <div class="user-menu">
                <button class="user-menu-button">
                    <i class="fas fa-user-circle"></i>
                    <span>Account</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-menu-dropdown">
                    <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="/tripko-system/tripko-backend/config/confirm_logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="tourism-sidebar">
        <nav class="sidebar-nav">
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/dashboard.php" class="sidebar-link active">
                <i class="fas fa-home"></i>Dashboard
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourist_spots.php" class="sidebar-link">
                <i class="fas fa-umbrella-beach"></i>Tourist Spots
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/festivals.php" class="sidebar-link">
                <i class="fas fa-calendar-alt"></i>Festivals
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/itineraries.php" class="sidebar-link">
                <i class="fas fa-map-marked-alt"></i>Itineraries
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/update_capacity.php" class="sidebar-link">
                <i class="fas fa-users-cog"></i>Update Capacity
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log.php" class="sidebar-link">
                <i class="fas fa-cash-register"></i>Tourism Fee Log
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log_list.php" class="sidebar-link">
                <i class="fas fa-file-alt"></i>Fee Log Report
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="tourism-main">
        <div class="main-container">
            <!-- Hero Section -->
            <section class="hero">
                <div class="hero-content">
                    <h1>Welcome, Tourism Officer</h1>
                    <h2>Municipality of <?php echo htmlspecialchars($town_name); ?></h2>
                    <p>Manage your town's tourism, showcase its beauty, and track all activities in one place.</p>
                    <div class="hero-actions">
                        <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourist_spots.php?action=add" class="hero-button">
                            <i class="fas fa-plus-circle"></i> Add Tourist Spot
                        </a>
                        <a href="#" onclick="generateReport(); return false;" class="hero-button secondary">
                            <i class="fas fa-chart-bar"></i> Generate Report
                        </a>
                    </div>
                </div>
            </section>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <!-- Tourist Spots Card -->
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Tourist Spots</h3>
                        <span class="stat-icon"><i class="fas fa-umbrella-beach"></i></span>
                    </div>
                    <p class="stat-value" id="touristSpotCount">0</p>
                    <p class="stat-label">Total locations</p>
                </div>

                <!-- Festivals Card -->
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Festivals</h3>
                        <span class="stat-icon"><i class="fas fa-calendar-alt"></i></span>
                    </div>
                    <p class="stat-value" id="festivalCount">0</p>
                    <p class="stat-label">Annual events</p>
                </div>

                <!-- Itineraries Card -->
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Itineraries</h3>
                        <span class="stat-icon"><i class="fas fa-map-marked-alt"></i></span>
                    </div>
                    <p class="stat-value" id="itineraryCount">0</p>
                    <p class="stat-label">Travel routes</p>
                </div>

                <!-- Visitor Stats Card -->
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Monthly Visitors</h3>
                        <span class="stat-icon"><i class="fas fa-users"></i></span>
                    </div>
                    <p class="stat-value" id="visitorCount">0</p>
                    <p class="stat-label">Website views</p>
                </div>
            </div>

            <!-- Recent Activity & Quick Actions -->
            <div class="content-grid">
                <!-- Recent Activity -->
                <section class="content-card">
                    <h2>Recent Activity</h2>
                    <div class="activity-list" id="recentActivity">
                        <!-- Loading skeleton -->
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text"></div>
                    </div>
                    <div class="pagination-container" id="activityPagination" style="display: none;">
                        <button class="pagination-btn" id="prevPage" disabled>
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <div class="pagination-info">
                            <span id="pageInfo">Page 1 of 1</span>
                        </div>
                        <button class="pagination-btn" id="nextPage" disabled>
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </section>

                <!-- Quick Actions -->
                <section class="content-card">
                    <h2>Quick Actions</h2>
                    <div class="actions-grid">
                        <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourist_spots.php?action=add" class="action-button">
                            <i class="fas fa-plus-circle"></i> Add Tourist Spot
                        </a>
                        <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/festivals.php?action=add" class="action-button">
                            <i class="fas fa-plus-circle"></i> Add Festival
                        </a>
                        <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/itineraries.php?action=add" class="action-button">
                            <i class="fas fa-plus-circle"></i> Create Itinerary
                        </a>
                        <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/update_capacity.php" class="action-button">
                            <i class="fas fa-users-cog"></i> Update Capacity
                        </a>
                        <a href="#" onclick="generateReport(); return false;" class="action-button">
                            <i class="fas fa-chart-bar"></i> Generate Report
                        </a>
                        <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log.php" class="action-button">
                            <i class="fas fa-cash-register"></i> Log Tourism Fee
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        // Dark Mode Toggle
        function initDarkMode() {
            const toggle = document.getElementById('darkModeToggle');
            const savedTheme = localStorage.getItem('tripko-tourism-theme');
            
            if (savedTheme === 'dark') {
                document.body.classList.add('dark');
                toggle.innerHTML = '<i class="bx bxs-sun"></i>';
            }
            
            toggle.addEventListener('click', () => {
                document.body.classList.toggle('dark');
                const isDark = document.body.classList.contains('dark');
                toggle.innerHTML = isDark ? '<i class="bx bxs-sun"></i>' : '<i class="bx bxs-moon"></i>';
                localStorage.setItem('tripko-tourism-theme', isDark ? 'dark' : 'light');
            });
        }

        // Function to load dashboard statistics
        async function loadDashboardStats() {
            try {
                const response = await fetch(`/tripko-system/tripko-backend/api/tourism_officers/dashboard_stats.php?town_id=<?php echo $town_id; ?>`, { credentials: 'same-origin' });
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('touristSpotCount').textContent = data.stats.tourist_spots || 0;
                    document.getElementById('festivalCount').textContent = data.stats.festivals || 0;
                    document.getElementById('itineraryCount').textContent = data.stats.itineraries || 0;
                    document.getElementById('visitorCount').textContent = data.stats.monthly_visitors || 0;
                }
            } catch (error) {
                console.error('Error loading dashboard stats:', error);
            }
        }

        // Function to load recent activity
        let currentPage = 1;
        let totalPages = 1;
        
        async function loadRecentActivity(page = 1) {
            try {
                const response = await fetch(`/tripko-system/tripko-backend/api/tourism_officers/recent_activity.php?town_id=<?php echo $town_id; ?>&page=${page}&limit=5`, { credentials: 'same-origin' });
                const data = await response.json();
                
                const activityContainer = document.getElementById('recentActivity');
                const paginationContainer = document.getElementById('activityPagination');
                
                if (data.success && data.activities && data.activities.length > 0) {
                    activityContainer.innerHTML = '';
                    
                    data.activities.forEach(activity => {
                        const activityElement = document.createElement('div');
                        activityElement.className = 'activity-item';
                        activityElement.innerHTML = `
                            <div class="activity-title">${activity.title}</div>
                            <div class="activity-description">${activity.description}</div>
                            <div class="activity-time">${activity.timestamp}</div>
                        `;
                        activityContainer.appendChild(activityElement);
                    });
                    
                    // Update pagination
                    if (data.pagination) {
                        currentPage = data.pagination.current_page;
                        totalPages = data.pagination.total_pages;
                        
                        if (totalPages > 1) {
                            paginationContainer.style.display = 'flex';
                            document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
                            document.getElementById('prevPage').disabled = currentPage <= 1;
                            document.getElementById('nextPage').disabled = currentPage >= totalPages;
                        } else {
                            paginationContainer.style.display = 'none';
                        }
                    }
                } else {
                    activityContainer.innerHTML = '<div class="activity-item"><div class="activity-title">No recent activity</div><div class="activity-description">Activity will appear here as you manage your tourism content.</div></div>';
                    paginationContainer.style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading recent activity:', error);
                const activityContainer = document.getElementById('recentActivity');
                activityContainer.innerHTML = '<div class="activity-item"><div class="activity-title">Unable to load activity</div><div class="activity-description">Please refresh the page to try again.</div></div>';
            }
        }

        // Function to generate report
        function generateReport() {
            window.location.href = `/tripko-system/tripko-frontend/file_html/tourism_offices/report.php?town_id=<?php echo $town_id; ?>`;
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', () => {
            initDarkMode();
            loadDashboardStats();
            loadRecentActivity();
            
            // Pagination event listeners
            document.getElementById('prevPage').addEventListener('click', () => {
                if (currentPage > 1) {
                    loadRecentActivity(currentPage - 1);
                }
            });
            
            document.getElementById('nextPage').addEventListener('click', () => {
                if (currentPage < totalPages) {
                    loadRecentActivity(currentPage + 1);
                }
            });
        });
    </script>
</body>
</html>