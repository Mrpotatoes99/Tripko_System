<?php
session_start();

// Database connection configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "tripko_db";

// Create connection
$password = "";
$conn = new mysqli($host, $username, $password, $database, 3307);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourist Spot Status - TripKo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { primary: '#00a6b8', brand: '#0f766e' } } } };</script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">   
    <link rel="stylesheet" href="../../file_css/navbar.css" /> 
    <link rel="stylesheet" href="/tripko-system/tripko-frontend/file_css/responsive.css" />

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'kameron';
        }

        body {
            line-height: 1.6;
            background-color: #f8fafc;
            min-height: 100vh;
            padding-top: 80px;
        }        .page-banner {
            background: linear-gradient(135deg, #255d8a 0%, #1a365d 100%);
            background-size: cover;
            background-position: center;
            padding: 80px 0;
            margin-bottom: 40px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .page-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../../file_images/waves.svg') bottom center repeat-x;
            opacity: 0.1;
            animation: wave 20s linear infinite;
        }

        @keyframes wave {
            0% {
                background-position-x: 0;
            }
            100% {
                background-position-x: 1000px;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header p {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
            margin: 0 auto;
        }        .filters {
            display: flex;
            gap: 15px;
            margin: -60px auto 40px;
            max-width: 1000px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            z-index: 10;
        }

        .filter-input {
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
            color: #1a365d;
        }

        .filter-input:focus {
            border-color: #255d8a;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 93, 138, 0.1);
        }

        .search-input {
            flex: 1;
        }

        .filter-input::placeholder {
            color: #94a3b8;
        }        .status-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 20px;
        }

        .status-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .status-table th {
            background: #255d8a;
            color: white;
            text-align: left;
            padding: 15px 20px;
            font-weight: 500;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .status-table tr:hover {
            background: #f8fafc;
            transition: background-color 0.3s ease;
        }        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .capacity-table {
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 20px;
        }

        .capacity-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .capacity-table th {
            background: #1a365d;
            color: white;
            text-align: left;
            padding: 15px 20px;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .capacity-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }

        .capacity-table tr:hover {
            background: #f8fafc;
        }

        .capacity-bar {
            width: 200px;
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .capacity-fill {
            height: 100%;
            transition: all 0.5s ease;
            border-radius: 10px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .timestamp {
            font-size: 0.85rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .timestamp i {
            font-size: 0.85rem;
        }        .no-results {
            text-align: center;
            color: #64748b;
            padding: 60px 20px;
            grid-column: 1 / -1;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .no-results i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 15px;
        }

        .no-results p {
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .no-results span {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                margin: -30px 20px 30px;
            }

            .filter-input {
                width: 100%;
            }

            .header h1 {
                font-size: 2rem;
            }

            .header p {
                font-size: 1rem;
                padding: 0 20px;
            }

            .status-grid {
                grid-template-columns: 1fr;
                padding: 10px;
            }

            .page-banner {
                padding: 60px 0;
            }
        }

        /* Loading Animation */
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        .loading {
            animation: pulse 1.5s infinite;
        }
    </style>
</head>
<body class="bg-gray-50 text-slate-900">
        <nav class="navbar">
            <div class="nav-content">
                <div class="logo">
                    TripKo Pangasinan
                </div>
                <div class="nav-links">
                    <a href="../homepage.php"><i class='bx bxs-home'></i> Home</a>
                    <div class="nav-dropdown">
                        <a href="#"><i class='bx bxs-map-alt'></i> Places to Go</a>
                        <div class="nav-dropdown-content">
                            <a href="places-to-go.php">Beaches</a>
                            <a href="islands-to-go.php">Islands</a>
                            <a href="waterfalls-to-go.php">Waterfalls</a>
                            <a href="caves-to-go.php">Caves</a>
                            <a href="churches-to-go.php">Churches</a>
                            <a href="festivals-to-go.php">Festivals</a>
                        </div>
                    </div>
                    <a href="../things-to-do.php"><i class='bx bxs-calendar-star'></i> Things to Do</a>
                    <a href="../routeFinder.php"><i class='bx bxs-bus'></i> Route Finder</a>
                    <a href="tourist_capacity.php"><i class='bx bxs-check-circle'></i> Tourist Spot Status</a>
                    <a href="#"><i class='bx bxs-book-content'></i> Directory</a>
                    <a href="#"><i class='bx bxs-info-circle'></i> About Us</a>
                    <a href="#"><i class='bx bxs-phone'></i> Contact Us</a>
                    <a href="/tripko-system/tripko-backend/logout.php"><i class='bx bx-log-out'></i> Logout</a>
                </div>
                <div class="menu-btn">
                    <i class='bx bx-menu'></i>
                </div>
            </div>
        </nav>
        <div class="page-banner">
        <div class="container">
            <div class="header">
                <h1>Tourist Spot Status</h1>
                <p>Real-time capacity monitoring for Pangasinan's most beautiful destinations. Plan your visit with live updates on tourist spot occupancy.</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="filters">
            <select id="townFilter" class="filter-input">
                <option value="">🏠 All Towns</option>
                <?php
                $townQuery = "SELECT town_id, name FROM towns ORDER BY name";
                $townResult = $conn->query($townQuery);
                while($town = $townResult->fetch_assoc()) {
                    echo "<option value='{$town['town_id']}'>{$town['name']}</option>";
                }
                ?>
            </select>            <select id="capacityFilter" class="filter-input">
                <option value="all">🔍 All Capacities</option>
                <option value="low">🟢 Low Capacity</option>
                <option value="medium">🟡 Medium Capacity</option>
                <option value="high">🔴 High Capacity</option>
            </select>
            <input type="text" id="searchInput" placeholder="🔍 Search tourist spots..." class="filter-input search-input">
        </div>        <div class="status-table">
            <table>
                <thead>
                    <tr>
                        <th>Tourist Spot</th>
                        <th>Location</th>
                        <th>Current/Max</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody id="statusTableBody">
            <?php            // First check if tourist_capacity table exists
            $check_table = "SHOW TABLES LIKE 'tourist_capacity'";
            $table_exists = $conn->query($check_table)->num_rows > 0;

            if (!$table_exists) {
                // If table doesn't exist, create it
                $create_table_sql = "CREATE TABLE IF NOT EXISTS tourist_capacity (
                    capacity_id INT AUTO_INCREMENT PRIMARY KEY,
                    spot_id INT NOT NULL,
                    current_capacity INT DEFAULT 0,
                    max_capacity INT NOT NULL DEFAULT 100,
                    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    updated_by VARCHAR(100),
                    FOREIGN KEY (spot_id) REFERENCES tourist_spots(spot_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                
                if ($conn->query($create_table_sql)) {
                    // Initialize capacity records for existing tourist spots
                    $init_records_sql = "
                    INSERT IGNORE INTO tourist_capacity (spot_id, max_capacity)
                    SELECT spot_id, 100 FROM tourist_spots
                    WHERE spot_id NOT IN (SELECT spot_id FROM tourist_capacity)";
                    
                    $conn->query($init_records_sql);
                }
            }

            $query = "SELECT ts.spot_id, ts.name, t.name as town_name,
                    COALESCE(tc.current_capacity, 0) as current_count,
                    COALESCE(tc.max_capacity, 100) as max_capacity,
                    ROUND((COALESCE(tc.current_capacity, 0) / COALESCE(tc.max_capacity, 100)) * 100, 1) as capacity_percentage,
                    tc.last_updated
                    FROM tourist_spots ts
                    LEFT JOIN towns t ON ts.town_id = t.town_id
                    LEFT JOIN tourist_capacity tc ON ts.spot_id = tc.spot_id
                    WHERE ts.status = 'active'
                    ORDER BY ts.name";
            
            $result = $conn->query($query);
              if ($result->num_rows === 0) {
                echo '<div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No Tourist Spots Found</p>
                        <span>Try adjusting your filters or search terms</span>
                      </div>';
            }

            while($spot = $result->fetch_assoc()) {
                $percentage = $spot['capacity_percentage'] ?? 0;
                $colorClass = '';
                $bgColorClass = '';
                $status = '';
                
                if ($percentage >= 90) {
                    $colorClass = '#dc2626';
                    $status = 'High Capacity';
                } elseif ($percentage >= 75) {
                    $colorClass = '#f59e0b';
                    $status = 'Medium Capacity';
                } else {
                    $colorClass = '#10b981';
                    $status = 'Low Capacity';
                }                echo "<tr class='hover:bg-gray-50' data-town='{$spot['town_name']}' data-percentage='$percentage'>
                    <td class='border border-gray-300 px-4 py-2'>
                        <div class='flex items-center gap-2'>
                            <i class='fas fa-umbrella-beach text-[#255d8a]'></i>
                            <span class='font-medium'>{$spot['name']}</span>
                        </div>
                    </td>
                    <td class='border border-gray-300 px-4 py-2'>
                        <div class='flex items-center gap-2 text-gray-600'>
                            <i class='fas fa-map-marker-alt'></i>
                            {$spot['town_name']}
                        </div>
                    </td>
                    <td class='border border-gray-300 px-4 py-2'>
                        <div class='flex items-center gap-2 text-gray-600'>
                            <i class='fas fa-users'></i>
                            {$spot['current_count']} / {$spot['max_capacity']}
                        </div>
                    </td>
                    <td class='border border-gray-300 px-4 py-2'>
                        <div class='flex items-center gap-3'>
                            <div class='flex-grow bg-gray-200 rounded-full h-2.5 w-24'>
                                <div class='h-2.5 rounded-full' style='width: {$percentage}%; background-color: $colorClass;'></div>
                            </div>
                            <div class='status-badge text-sm' style='background-color: {$colorClass}20; color: $colorClass;'>
                                <i class='fas fa-info-circle'></i>
                                $status ({$percentage}%)
                            </div>
                        </div>
                    </td>
                    <td class='border border-gray-300 px-4 py-2 text-sm text-gray-500'>
                        <div class='flex items-center gap-2'>
                            <i class='far fa-clock'></i>
                            " . ($spot['last_updated'] ? date('F j, Y g:i A', strtotime($spot['last_updated'])) : 'Not available') . "
                        </div>
                    </td>
                </tr>";
            }
            ?>
        </div>
    </div>

    <script>        function filterSpots() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const townFilter = document.getElementById('townFilter').value.toLowerCase();
            const capacityFilter = document.getElementById('capacityFilter').value;
            const rows = document.querySelectorAll('#statusTableBody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                if (row.classList.contains('no-results-row')) return;
                
                const spotName = row.querySelector('td:first-child').textContent.toLowerCase();
                const townName = row.getAttribute('data-town').toLowerCase();
                const percentage = parseFloat(row.getAttribute('data-percentage'));
                
                const matchesSearch = spotName.includes(searchText) || townName.includes(searchText);
                const matchesTown = !townFilter || townName === townFilter;
                const matchesCapacity = capacityFilter === 'all' || 
                    (capacityFilter === 'high' && percentage >= 90) ||
                    (capacityFilter === 'medium' && percentage >= 75 && percentage < 90) ||
                    (capacityFilter === 'low' && percentage < 75);

                if (matchesSearch && matchesTown && matchesCapacity) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show no results message if needed
            const tableBody = document.getElementById('statusTableBody');
            const existingNoResults = tableBody.querySelector('.no-results-row');
            
            if (visibleCount === 0) {
                if (!existingNoResults) {
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results-row';
                    noResultsRow.innerHTML = `
                        <td colspan="5" class="text-center py-8 text-gray-500">
                            <i class="fas fa-search text-4xl mb-4 block"></i>
                            <p class="text-lg mb-2">No Tourist Spots Found</p>
                            <span class="text-sm">Try adjusting your filters or search terms</span>
                        </td>
                    `;
                    tableBody.appendChild(noResultsRow);
                }
            } else if (existingNoResults) {
                existingNoResults.remove();
            }
        }        function createNoResultsElement() {
            const div = document.createElement('div');
            div.className = 'no-results';
            div.innerHTML = `
                <i class="fas fa-search"></i>
                <p>No Tourist Spots Found</p>
                <span>Try adjusting your filters or search terms</span>
            `;
            return div;
        }

        // Add smooth transitions when filtering
        function updateCardVisibility(card, visible) {
            if (visible) {
                card.style.display = 'block';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 50);
            } else {
                card.style.opacity = '0';
                card.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        }

        // Add event listeners to filters
        document.getElementById('searchInput').addEventListener('input', filterSpots);
        document.getElementById('townFilter').addEventListener('change', filterSpots);
        document.getElementById('capacityFilter').addEventListener('change', filterSpots);
    </script>
    <script src="/tripko-system/tripko-frontend/file_js/mobile-viewport-fix.js"></script>
</body>
</html>