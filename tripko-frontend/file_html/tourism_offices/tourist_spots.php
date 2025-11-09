<?php
require_once('../../../tripko-backend/config/Database.php');
require_once('../../../tripko-backend/config/check_session.php');
checkTourismOfficerSession();

// Initialize database connection
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

if (!$town_data) {
    die("Error: No town assigned to this tourism officer");
}

$town_name = $town_data['town_name'];
$town_id = $town_data['town_id'];

// Check if we're in add/edit mode
$mode = isset($_GET['action']) ? $_GET['action'] : 'list';
// guarded spot id for edit mode
$spot_id = isset($_GET['id']) ? intval($_GET['id']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourist Spots Management - <?php echo htmlspecialchars($town_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kameron', serif;
            background: linear-gradient(120deg, #f3f1e8 60%, #e0f7fa 100%);
        }
        .hero-tourism {
            background: linear-gradient(90deg, #255D4F 60%, #1e4d70 100%);
            color: #fff;
            border-radius: 2rem;
            box-shadow: 0 8px 32px 0 rgba(34, 60, 80, 0.18);
            padding: 2.5rem 2rem 2rem 2rem;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        .hero-img-tourism {
            position: absolute;
            right: 2rem;
            bottom: 0;
            width: 320px;
            max-width: 40vw;
            opacity: 0.18;
            pointer-events: none;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: background 0.2s, color 0.2s;
            font-weight: 500;
        }
        .sidebar-link.active, .sidebar-link:hover {
            background-color: #1e4d70;
            color: #ffd700;
        }
        .card {
            background: linear-gradient(120deg, #fff 80%, #e0f7fa 100%);
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(34, 60, 80, 0.12);
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .card:hover {
            box-shadow: 0 16px 48px 0 rgba(34, 60, 80, 0.18);
            transform: translateY(-2px) scale(1.03);
        }
        .table-header {
            background: #f9fafb;
        }
        .table-row:hover {
            background: #e0f7fa;
        }
        .btn-primary {
            background: linear-gradient(120deg, #255D4F 60%, #1e4d70 100%);
            color: #fff;
            border-radius: 0.5rem;
            padding: 0.5rem 1.25rem;
            font-weight: 600;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px 0 rgba(34, 60, 80, 0.10);
        }
        .btn-primary:hover {
            background: linear-gradient(120deg, #1e4d70 60%, #255D4F 100%);
            box-shadow: 0 8px 32px 0 rgba(34, 60, 80, 0.18);
        }
        .btn-danger {
            background: #e53e3e;
            color: #fff;
            border-radius: 0.5rem;
            padding: 0.5rem 1.25rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-danger:hover {
            background: #c53030;
        }
        .spot-icon {
            background: linear-gradient(120deg, #255D4F 60%, #1e4d70 100%);
            color: #fff;
            border-radius: 1rem;
            padding: 0.5rem 0.75rem;
            box-shadow: 0 2px 8px 0 rgba(34, 60, 80, 0.10);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="bg-[#255D4F] text-white p-4 fixed w-full top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <span class="text-2xl font-bold">TripKo Tourism Office</span>
                <span class="text-lg">| <?php echo htmlspecialchars($town_name); ?></span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../../../tripko-backend/config/confirm_logout.php" class="hover:text-gray-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>Sign Out
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="fixed left-0 top-16 h-full w-64 bg-[#255D4F] text-white py-6 px-3 flex flex-col shadow-lg z-40">
        <nav class="flex-1 space-y-2">
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/dashboard.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-home mr-3"></i>Dashboard
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourist_spots.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold active bg-[#1e4d70]">
                <i class="fas fa-umbrella-beach mr-3"></i>Tourist Spots
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/festivals.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-calendar-alt mr-3"></i>Festivals
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/itineraries.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-map-marked-alt mr-3"></i>Itineraries
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/update_capacity.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
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
    <div class="ml-64 mt-16 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Hero Section -->
            <section class="hero-tourism flex flex-col md:flex-row items-center justify-between mb-12 relative">
                <div class="z-10">
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">Tourist Spots Management</h1>
                    <h2 class="text-2xl md:text-3xl font-semibold mb-2">Municipality of <?php echo htmlspecialchars($town_name); ?></h2>
                    <p class="text-lg font-medium opacity-90 mb-6">Showcase the beauty of your town and manage all tourist spots in one place.</p>
                    <?php if ($mode === 'list'): ?>
                    <a href="?action=add" class="btn-primary flex items-center gap-2 shadow text-lg"><i class="fas fa-plus-circle"></i> Add New Tourist Spot</a>
                    <?php endif; ?>
                </div>
                <img src="https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=800&q=80" alt="Tourism Hero" class="hero-img-tourism hidden md:block rounded-2xl shadow-lg" />
            </section>

            <?php if ($mode === 'list'): ?>
            <!-- Tourist Spots List -->
            <div class="card overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="touristSpotsList">
                        <!-- Loading state -->
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading tourist spots...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($mode === 'edit' || $mode === 'add'): ?>
            <!-- Add/Edit Form -->
            <div class="card p-8">
                <h2 class="text-2xl font-bold mb-6"><?php echo $mode === 'add' ? 'Add New Tourist Spot' : 'Edit Tourist Spot'; ?></h2>
                <form id="touristSpotForm" class="space-y-6">
                    <input type="hidden" name="town_id" value="<?php echo htmlspecialchars($town_id); ?>">
                    <?php if ($mode === 'edit'): ?>
                    <input type="hidden" name="spot_id" value="<?php echo htmlspecialchars($spot_id); ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <div class="flex items-center gap-2">
                                <span class="spot-icon"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <div class="flex items-center gap-2">
                                <span class="spot-icon"><i class="fas fa-leaf"></i></span>
                                <select name="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                                    <option value="">Select a category</option>
                                    <option value="nature">Nature</option>
                                    <option value="historical">Historical</option>
                                    <option value="cultural">Cultural</option>
                                    <option value="religious">Religious</option>
                                    <option value="adventure">Adventure</option>
                                </select>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <div class="flex items-center gap-2">
                                <span class="spot-icon"><i class="fas fa-location-arrow"></i></span>
                                <input type="text" name="location" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                            <div class="flex items-center gap-2">
                                <span class="spot-icon"><i class="fas fa-phone-alt"></i></span>
                                <input type="tel" name="contact_info" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Operating Hours</label>
                            <input type="text" name="operating_hours" placeholder="e.g., Mon-Sun: 8:00 AM - 5:00 PM" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Entrance Fee</label>
                            <input type="text" name="entrance_fee" placeholder="e.g., ₱100 per person" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>

                        <!-- GPS Coordinates Section -->
                        <div class="md:col-span-2 bg-gradient-to-r from-[#e0f7fa] to-[#f3f1e8] p-6 rounded-lg border-2 border-[#255D4F] border-opacity-20">
                            <div class="flex items-center mb-4">
                                <span class="spot-icon mr-3"><i class="fas fa-map-marked-alt"></i></span>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">GPS Coordinates (For "How to Get There?" Feature)</h3>
                                    <p class="text-sm text-gray-600">Add precise coordinates to enable Google Maps directions for visitors</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-compass mr-1 text-[#255D4F]"></i> Latitude
                                    </label>
                                    <input type="number" name="latitude" step="0.0000001" min="-90" max="90" placeholder="e.g., 16.3864" 
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50"
                                           title="Latitude coordinate (between -90 and 90)">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-compass mr-1 text-[#255D4F]"></i> Longitude
                                    </label>
                                    <input type="number" name="longitude" step="0.0000001" min="-180" max="180" placeholder="e.g., 119.8894" 
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50"
                                           title="Longitude coordinate (between -180 and 180)">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-crosshairs mr-1 text-[#255D4F]"></i> Accuracy
                                    </label>
                                    <select name="accuracy" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                                        <option value="exact">Exact (GPS pinpoint)</option>
                                        <option value="approximate">Approximate (nearby area)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                                    <div class="text-sm text-blue-800">
                                        <p class="font-semibold mb-1">How to get coordinates:</p>
                                        <ol class="list-decimal list-inside space-y-1 text-xs">
                                            <li>Open <a href="https://www.google.com/maps" target="_blank" class="underline font-semibold hover:text-blue-600">Google Maps</a> in a new tab</li>
                                            <li>Find your tourist spot and right-click on the exact location</li>
                                            <li>Click the coordinates at the top (e.g., "16.3864, 119.8894")</li>
                                            <li>Copy and paste the latitude and longitude into the fields above</li>
                                        </ol>
                                        <p class="mt-2 text-blue-700"><strong>Note:</strong> Adding coordinates enables the "How to get there?" button for visitors!</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Images</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md bg-gradient-to-r from-[#e0f7fa] to-[#f3f1e8]">
                                <div class="space-y-1 text-center">
                                    <div class="flex flex-wrap gap-4 mb-4" id="imagePreviewContainer"></div>
                                    <div class="flex text-sm text-gray-600">
                                        <label class="relative cursor-pointer bg-white rounded-md font-medium text-[#255D4F] hover:text-[#1e4d70] focus-within:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#255D4F]">
                                            <span>Upload images</span>
                                            <input type="file" name="images[]" multiple accept="image/*" class="sr-only" onchange="previewImages(this)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB each</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <a href="tourist_spots.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="btn-primary px-4 py-2">
                            <?php echo $mode === 'add' ? 'Create Tourist Spot' : 'Update Tourist Spot'; ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Load tourist spots
        async function loadTouristSpots() {
            const tbody = document.getElementById("touristSpotsList");
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
                    </td>
                </tr>
            `;
            
            try {
                const response = await fetch("../../../tripko-backend/api/tourism_officers/tourist_spots.php", {
                    method: "GET",
                    headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" },
                    credentials: "same-origin"
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log("Fetched data:", data);
                
                if (!data.success || !data.spots || data.spots.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                No tourist spots found. Click "Add New Tourist Spot" to create one.
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                tbody.innerHTML = data.spots.map(spot => `
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-full object-cover" 
                                         src="../../../uploads/${spot.image_path || 'placeholder.jpg'}" 
                                         alt="${spot.name}"
                                         onerror="this.src='../../../assets/images/placeholder.jpg'">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${spot.name}</div>
                                    <div class="text-sm text-gray-500">${spot.location || spot.town_name}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">${spot.category}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                spot.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                            }">${spot.status}</span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <a href="?action=edit&id=${spot.spot_id}" class="text-[#255D4F] hover:text-[#1e4d70] mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button onclick="deleteSpot(${spot.spot_id})" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading tourist spots:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-red-500">
                            <div class="mb-1">Error loading tourist spots: ${error.message}</div>
                            <div class="text-sm">Please try refreshing the page or contact support if the issue persists.</div>
                        </td>
                    </tr>
                `;
            }
        }

        // Image preview handling
        function previewImages(input) {
            const container = document.getElementById('imagePreviewContainer');
            container.innerHTML = '';

            if (input.files) {
                [...input.files].forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = e => {
                            const div = document.createElement('div');
                            div.className = 'relative';
                            div.innerHTML = `
                                <img src="${e.target.result}" alt="preview" class="image-preview rounded shadow-sm">
                                <button type="button" onclick="this.closest('.relative').remove()" 
                                        class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center 
                                               opacity-0 group-hover:opacity-100 transition-opacity duration-200">×</button>
                            `;
                            container.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        }

        // Delete tourist spot
        async function deleteSpot(spotId) {
            if (!confirm('Are you sure you want to delete this tourist spot? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('../../../tripko-backend/api/tourism_officers/tourist_spots.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ spot_id: spotId, town_id: <?php echo $town_id; ?> })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Tourist spot deleted successfully');
                    loadTouristSpots();
                } else {
                    throw new Error(data.message || 'Failed to delete tourist spot');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error deleting tourist spot: ' + error.message);
            }
        }

        // Form handling
        const form = document.getElementById('touristSpotForm');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                
                try {
                    const url = '<?php echo $mode === "add" ? 
                        "../../../tripko-backend/api/tourism_officers/tourist_spots.php" : 
                        "../../../tripko-backend/api/tourism_officers/tourist_spots.php?id=" . ($spot_id ?? ''); ?>';
                    
                    const response = await fetch(url, {
                        method: '<?php echo $mode === "add" ? "POST" : "PUT"; ?>',
                        credentials: 'same-origin',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        alert('Tourist spot <?php echo $mode === "add" ? "created" : "updated"; ?> successfully');
                        window.location.href = 'tourist_spots.php';
                    } else {
                        throw new Error(data.message || 'Failed to <?php echo $mode; ?> tourist spot');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error <?php echo $mode === "add" ? "creating" : "updating"; ?> tourist spot: ' + error.message);
                }
            });
        }

        // Initialize page
        <?php if ($mode === 'list'): ?>
        document.addEventListener('DOMContentLoaded', loadTouristSpots);
        <?php endif; ?>

        <?php if ($mode === 'edit'): ?>
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch(`../../../tripko-backend/api/tourism_officers/tourist_spots.php?id=<?php echo $spot_id ?? ''; ?>`, { credentials: 'same-origin' });
                const data = await response.json();
                
                if (data.success && data.spot) {
                    const form = document.getElementById('touristSpotForm');
                    Object.entries(data.spot).forEach(([key, value]) => {
                        const input = form.elements[key];
                        if (input) input.value = value;
                    });

                    if (data.spot.images) {
                        const container = document.getElementById('imagePreviewContainer');
                        data.spot.images.forEach(imageUrl => {
                            const div = document.createElement('div');
                            div.className = 'relative';
                            div.innerHTML = `
                                <img src="${imageUrl}" alt="existing" class="image-preview rounded">
                                <button type="button" onclick="this.parentElement.remove()" 
                                        class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center">×</button>
                                <input type="hidden" name="existing_images[]" value="${imageUrl}">
                            `;
                            container.appendChild(div);
                        });
                    }
                }
            } catch (error) {
                console.error('Error loading tourist spot:', error);
                alert('Error loading tourist spot data: ' + error.message);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
