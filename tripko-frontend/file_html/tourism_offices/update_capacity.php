
<?php
require_once('../../../tripko-backend/config/Database.php');
require_once('../../../tripko-backend/config/check_session.php');
checkTourismOfficerSession();

$database = new Database();
$conn = $database->getConnection();
if (!$conn) { die("Database connection failed"); }
$user_id = $_SESSION['user_id'];
$query = "SELECT t.name as town_name, t.town_id FROM towns t INNER JOIN user u ON u.town_id = t.town_id WHERE u.user_id = ?";
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
    <title>Update Capacity - <?php echo htmlspecialchars($town_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Kameron', serif; }
        .sidebar-link {
            transition: background 0.2s, color 0.2s;
        }
        .sidebar-link.active, .sidebar-link:hover {
            background: #1e4d70;
            color: #fff;
        }
        .sidebar-link i {
            transition: color 0.2s;
        }
        .sidebar-link.active i, .sidebar-link:hover i {
            color: #ffd700;
        }
        .card {
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .card:hover {
            box-shadow: 0 8px 32px 0 rgba(34, 60, 80, 0.2);
            transform: translateY(-2px) scale(1.02);
        }
    </style>
</head>
<body class="bg-[#F3F1E8]">
    <!-- Navigation -->
    <nav class="bg-[#255D4F] text-white px-6 py-3 fixed w-full top-0 z-50 shadow-md">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center space-x-4">
                <span class="text-2xl font-extrabold tracking-tight">TripKo Admin</span>
                <span class="hidden sm:inline text-lg font-medium opacity-80">| <?php echo htmlspecialchars($town_name); ?></span>
            </div>
            <div class="relative group">
                <button class="flex items-center space-x-2 focus:outline-none">
                    <span class="hidden md:inline font-semibold">Account</span>
                    <i class="fas fa-user-circle text-2xl"></i>
                    <i class="fas fa-chevron-down ml-1 text-xs"></i>
                </button>
                <div class="absolute right-0 mt-2 w-40 bg-white text-gray-800 rounded shadow-lg opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-200 z-50">
                    <a href="#" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                    <a href="../../../tripko-backend/config/confirm_logout.php" class="block px-4 py-2 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i>Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="fixed left-0 top-16 h-full w-64 bg-[#255D4F] text-white py-6 px-3 flex flex-col shadow-lg z-40">
        <nav class="flex-1 space-y-2">
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/dashboard.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-home mr-3"></i>Dashboard
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourist_spots.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-umbrella-beach mr-3"></i>Tourist Spots
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/festivals.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-calendar-alt mr-3"></i>Festivals
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/itineraries.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-map-marked-alt mr-3"></i>Itineraries
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/update_capacity.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold active bg-[#1e4d70]">
                <i class="fas fa-users-cog mr-3"></i>Update Capacity
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-cash-register mr-3"></i>Tourism Fee Log
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log_list.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-file-alt mr-3"></i>Fee Log Report
            </a>
        </nav>
        <div class="mt-auto pt-6 border-t border-[#1e4d70]">
            <a href="/tripko-system/tripko-backend/config/confirm_logout.php" class="flex items-center px-4 py-2 text-sm hover:text-[#ffd700] transition-colors">
                <i class="fas fa-sign-out-alt mr-2"></i>Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 mt-16 px-6 py-10 min-h-screen bg-[#F3F1E8]">
        <div class="max-w-7xl mx-auto">
            <!-- Hero Section -->
            <section class="hero flex flex-col md:flex-row items-center justify-between mb-12 relative">
                <div class="z-10">
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">Update Tourist Spot Capacity</h1>
                    <h2 class="text-2xl md:text-3xl font-semibold mb-2">Municipality of <?php echo htmlspecialchars($town_name); ?></h2>
                    <p class="text-lg font-medium opacity-90 mb-6">Easily manage and update the capacity of your town's tourist spots for better visitor experience and safety.</p>
                </div>
                <img src="https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=800&q=80" alt="Tourism Hero" class="hero-img hidden md:block rounded-2xl shadow-lg" />
            </section>

            <div class="card bg-white rounded-2xl shadow-lg p-10 mb-10">
                <h1 class="text-3xl font-extrabold mb-8 text-[#255D4F] flex items-center"><i class="fas fa-users-cog mr-3"></i>Tourist Spot Capacities</h1>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="spotsTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Capacity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Capacity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% Used</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody id="spotsTableBody">
                            <tr><td colspan="6" class="text-center py-4 text-gray-400">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal -->
            <div id="capacityModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
                <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md relative">
                    <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
                    <h2 class="text-2xl font-bold mb-4 text-[#255D4F]">Update Capacity</h2>
                    <form id="capacityForm" class="space-y-4">
                        <input type="hidden" id="modalSpotId" name="spot_id">
                        <div>
                            <label for="modalMaxCapacity" class="block text-sm font-medium text-gray-700 mb-1">Maximum Capacity</label>
                            <input type="number" id="modalMaxCapacity" name="max_capacity" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="modalCurrentCapacity" class="block text-sm font-medium text-gray-700 mb-1">Current Capacity</label>
                            <input type="number" id="modalCurrentCapacity" name="current_capacity" min="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>
                        <button type="submit" class="w-full py-2 px-4 bg-[#255D4F] text-white font-semibold rounded-lg shadow-md hover:bg-[#1e4d70] transition duration-200">Save</button>
                        <div class="success text-green-600 font-semibold text-center" id="successMsg" style="display:none;"></div>
                        <div class="error text-red-600 font-semibold text-center" id="errorMsg" style="display:none;"></div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script>
    // Fetch all tourist spots and their capacities
    function fetchSpotsAndCapacities() {
        fetch('/tripko-system/tripko-backend/api/tourist_spot_capacity/read.php?town_id=<?php echo $town_id; ?>')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('spotsTableBody');
                tbody.innerHTML = '';
                if (data.success && data.records && data.records.length > 0) {
                    data.records.forEach(spot => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap">${spot.name}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${spot.category}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${spot.current_capacity}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${spot.max_capacity}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${spot.max_capacity > 0 ? ((spot.current_capacity/spot.max_capacity)*100).toFixed(1) : '0'}%</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button class="bg-[#255D4F] text-white px-3 py-1 rounded hover:bg-[#1e4d70]" onclick="openModal(${spot.spot_id}, ${spot.max_capacity}, ${spot.current_capacity})">Edit</button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-400">No tourist spots found.</td></tr>';
                }
            });
    }
    fetchSpotsAndCapacities();

    // Modal logic
    function openModal(spot_id, max_capacity, current_capacity) {
        document.getElementById('capacityModal').classList.remove('hidden');
        document.getElementById('modalSpotId').value = spot_id || '';
        document.getElementById('modalMaxCapacity').value = max_capacity || '';
        document.getElementById('modalCurrentCapacity').value = current_capacity || '';
        document.getElementById('successMsg').style.display = 'none';
        document.getElementById('errorMsg').style.display = 'none';
    }
    function closeModal() {
        document.getElementById('capacityModal').classList.add('hidden');
    }

    // Handle modal form submit
    document.getElementById('capacityForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const spot_id = parseInt(document.getElementById('modalSpotId').value, 10);
        const max_capacity = parseInt(document.getElementById('modalMaxCapacity').value, 10);
        const current_capacity = parseInt(document.getElementById('modalCurrentCapacity').value, 10);
        console.log('Submitting:', { spot_id, max_capacity, current_capacity });
        if (!spot_id || isNaN(max_capacity) || isNaN(current_capacity)) {
            alert('All fields are required and must be numbers.');
            return;
        }
        fetch('/tripko-system/tripko-backend/api/tourist_spot_capacity/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ spot_id, max_capacity, current_capacity })
        })
        .then(res => res.json())
        .then(data => {
            console.log('API response:', data);
            if (data.success) {
                document.getElementById('successMsg').style.display = 'block';
                document.getElementById('successMsg').textContent = data.message;
                document.getElementById('errorMsg').style.display = 'none';
                fetchSpotsAndCapacities();
                setTimeout(closeModal, 1200);
            } else {
                document.getElementById('successMsg').style.display = 'none';
                document.getElementById('errorMsg').style.display = 'block';
                document.getElementById('errorMsg').textContent = data.message || 'Update failed';
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            document.getElementById('successMsg').style.display = 'none';
            document.getElementById('errorMsg').style.display = 'block';
            document.getElementById('errorMsg').textContent = 'Network or server error.';
        });
    });
    </script>
</body>
</html>
