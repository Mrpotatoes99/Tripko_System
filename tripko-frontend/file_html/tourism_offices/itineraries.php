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

// Safe town values (in case query returned no row)
$town_name = isset($town_data['town_name']) ? $town_data['town_name'] : 'Unknown';
$town_id = isset($town_data['town_id']) ? $town_data['town_id'] : 0;

// Check if we're in add/edit mode
$mode = isset($_GET['action']) ? $_GET['action'] : 'list';
// itinerary id used in edit mode (guarded)
$itinerary_id = isset($_GET['id']) ? intval($_GET['id']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itineraries Management - <?php echo htmlspecialchars($town_name); ?></title>
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
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
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
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/itineraries.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold active bg-[#1e4d70]">
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
    <main class="ml-64 mt-16 px-6 py-10 min-h-screen bg-[#F3F1E8]">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-10">
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-[#255D4F]">Itineraries Management</h1>
                <?php if ($mode === 'list'): ?>
                <a href="?action=add" class="flex items-center px-5 py-2 bg-[#255D4F] text-white rounded-lg font-semibold hover:bg-[#1e4d70] transition duration-200 shadow">
                    <i class="fas fa-plus-circle mr-2"></i>Create New Itinerary
                </a>
                <?php endif; ?>
            </div>

            <?php if ($mode === 'list'): ?>
            <!-- Itineraries List -->
            <div class="card bg-white rounded-2xl shadow-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price Range</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="itinerariesList">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading itineraries...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php else: ?>
            <!-- Add/Edit Form -->
            <div class="card bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-bold mb-6"><?php echo $mode === 'add' ? 'Create New Itinerary' : 'Edit Itinerary'; ?></h2>
                <form id="itineraryForm" class="space-y-6">
                    <input type="hidden" name="town_id" value="<?php echo $town_id; ?>">
                    <?php if ($mode === 'edit'): ?>
                    <input type="hidden" name="itinerary_id" value="<?php echo htmlspecialchars($itinerary_id); ?>">
                    <?php endif; ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Itinerary Title</label>
                            <input type="text" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Duration (Days)</label>
                            <input type="number" name="duration_days" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                                <option value="">Select a category</option>
                                <option value="adventure">Adventure</option>
                                <option value="cultural">Cultural</option>
                                <option value="nature">Nature</option>
                                <option value="food">Food & Culinary</option>
                                <option value="relaxation">Relaxation</option>
                                <option value="family">Family-friendly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Minimum Price (₱)</label>
                            <input type="number" name="min_price" min="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Maximum Price (₱)</label>
                            <input type="number" name="max_price" min="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50"></textarea>
                        </div>
                        <!-- Itinerary Days -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-4">Itinerary Schedule</label>
                            <div id="daysContainer" class="space-y-6">
                                <!-- Days will be added here -->
                            </div>
                            <button type="button" onclick="addDay()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-[#255D4F] bg-[#255D4F] bg-opacity-10 hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#255D4F]">
                                <i class="fas fa-plus-circle mr-2"></i>Add Day
                            </button>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Inclusions</label>
                            <textarea name="inclusions" rows="3" placeholder="List what's included in the package" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Important Notes</label>
                            <textarea name="notes" rows="3" placeholder="Any important information for travelers" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Featured Image</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <div id="imagePreviewContainer" class="flex flex-wrap gap-4 mb-4"></div>
                                    <div class="flex text-sm text-gray-600">
                                        <label class="relative cursor-pointer bg-white rounded-md font-medium text-[#255D4F] hover:text-[#1e4d70] focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-[#255D4F]">
                                            <span>Upload image</span>
                                            <input type="file" name="featured_image" accept="image/*" class="sr-only" onchange="previewImage(this)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG up to 10MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <a href="itineraries.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#255D4F]">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 bg-[#255D4F] text-white rounded-md hover:bg-[#1e4d70] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#255D4F]">
                            <?php echo $mode === 'add' ? 'Create Itinerary' : 'Update Itinerary'; ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        let dayCount = 0;

        // Function to add a new day to the itinerary
        function addDay() {
            dayCount++;
            const daysContainer = document.getElementById('daysContainer');
            const dayDiv = document.createElement('div');
            dayDiv.className = 'day-container p-4 border border-gray-200 rounded-md';
            dayDiv.innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Day ${dayCount}</h3>
                    <button type="button" onclick="this.closest('.day-container').remove()" 
                            class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Activities</label>
                        <div class="activities-container space-y-2">
                            <div class="activity-item flex items-center space-x-2">
                                <input type="text" name="days[${dayCount}][activities][]" 
                                       placeholder="Activity description"
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                                <input type="time" name="days[${dayCount}][times][]"
                                       class="w-32 rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                                <button type="button" onclick="addActivity(this)" class="text-[#255D4F] hover:text-[#1e4d70]">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes for this day</label>
                        <textarea name="days[${dayCount}][notes]" rows="2" 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50"
                                  placeholder="Additional information for this day's activities"></textarea>
                    </div>
                </div>
            `;
            daysContainer.appendChild(dayDiv);
        }

        // Function to add a new activity to a day
        function addActivity(button) {
            const activitiesContainer = button.closest('.activities-container');
            const dayNumber = activitiesContainer.querySelector('input[name^="days["]').name.match(/\d+/)[0];
            const activityDiv = document.createElement('div');
            activityDiv.className = 'activity-item flex items-center space-x-2 mt-2';
            activityDiv.innerHTML = `
                <input type="text" name="days[${dayNumber}][activities][]" 
                       placeholder="Activity description"
                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                <input type="time" name="days[${dayNumber}][times][]"
                       class="w-32 rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                <button type="button" onclick="this.closest('.activity-item').remove()" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-minus-circle"></i>
                </button>
            `;
            activitiesContainer.appendChild(activityDiv);
        }

        // Function to preview the featured image
        function previewImage(input) {
            const container = document.getElementById('imagePreviewContainer');
            container.innerHTML = '';

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="preview" class="image-preview rounded">
                        <button type="button" onclick="clearImage()" 
                                class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center">
                            ×
                        </button>
                    `;
                    container.appendChild(div);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Function to clear the featured image
        function clearImage() {
            document.querySelector('input[name="featured_image"]').value = '';
            document.getElementById('imagePreviewContainer').innerHTML = '';
        }

        // Function to load itineraries
        async function loadItineraries() {
            try {
                const response = await fetch(`../../../tripko-backend/api/tourism_officers/itineraries.php?town_id=<?php echo $town_id; ?>`, { credentials: 'same-origin' });
                const data = await response.json();
                
                const tbody = document.getElementById('itinerariesList');
                tbody.innerHTML = '';
                
                if (data.success && data.itineraries && data.itineraries.length > 0) {
                    data.itineraries.forEach(itinerary => {
                        const minPrice = (typeof itinerary.min_price === 'number') ? itinerary.min_price : 0;
                        const maxPrice = (typeof itinerary.max_price === 'number') ? itinerary.max_price : minPrice;
                        const duration = itinerary.duration_days ? itinerary.duration_days : (itinerary.duration || 1);
                        tbody.innerHTML += `
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover" 
                                                 src="${itinerary.featured_image || 'default-itinerary.jpg'}" 
                                                 alt="${itinerary.title}">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">${itinerary.title}</div>
                                            <div class="text-sm text-gray-500">${itinerary.category}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    ${duration} day${duration > 1 ? 's' : ''}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    ₱${minPrice.toLocaleString()}${maxPrice !== minPrice ? ' - ₱' + maxPrice.toLocaleString() : ''}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                        itinerary.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }">
                                        ${itinerary.status}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium">
                                    <a href="?action=edit&id=${itinerary.id}" class="text-[#255D4F] hover:text-[#1e4d70] mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button onclick="deleteItinerary(${itinerary.id})" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No itineraries found
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error loading itineraries:', error);
                document.getElementById('itinerariesList').innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-red-500">
                            Error loading itineraries. Please try again.
                        </td>
                    </tr>
                `;
            }
        }

        // Function to delete an itinerary
        async function deleteItinerary(itineraryId) {
            if (confirm('Are you sure you want to delete this itinerary? This action cannot be undone.')) {
                try {
                    const response = await fetch('../../../tripko-backend/api/tourism_officers/itineraries.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            itinerary_id: itineraryId,
                            town_id: <?php echo $town_id; ?>
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        alert('Itinerary deleted successfully');
                        loadItineraries();
                    } else {
                        throw new Error(data.message || 'Failed to delete itinerary');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting itinerary: ' + error.message);
                }
            }
        }

        // Form submission handler
        const form = document.getElementById('itineraryForm');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                
                try {
                    const url = '<?php echo $mode === "add" ? 
                        "../../../tripko-backend/api/tourism_officers/itineraries.php" : 
                        "../../../tripko-backend/api/tourism_officers/itineraries.php?id=" . ($itinerary_id ?? ''); ?>';
                    
                    const response = await fetch(url, {
                        method: '<?php echo $mode === "add" ? "POST" : "PUT"; ?>',
                        credentials: 'same-origin',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        alert('Itinerary <?php echo $mode === "add" ? "created" : "updated"; ?> successfully');
                        window.location.href = 'itineraries.php';
                    } else {
                        throw new Error(data.message || 'Failed to <?php echo $mode; ?> itinerary');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error <?php echo $mode === "add" ? "creating" : "updating"; ?> itinerary: ' + error.message);
                }
            });
        }

        // Add initial day when creating a new itinerary
        <?php if ($mode === 'add'): ?>
        document.addEventListener('DOMContentLoaded', () => {
            addDay();
        });
        <?php endif; ?>

        // Load itineraries when page loads (if in list mode)
        <?php if ($mode === 'list'): ?>
        document.addEventListener('DOMContentLoaded', loadItineraries);
        <?php endif; ?>

        <?php if ($mode === 'edit'): ?>
        // Load itinerary data for editing
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch(`../../../tripko-backend/api/tourism_officers/itineraries.php?id=<?php echo $itinerary_id ?? ''; ?>`, { credentials: 'same-origin' });
                const data = await response.json();
                
                if (data.success && data.itinerary) {
                    const form = document.getElementById('itineraryForm');
                    Object.entries(data.itinerary).forEach(([key, value]) => {
                        const input = form.elements[key];
                        if (input && key !== 'days') {
                            input.value = value;
                        }
                    });

                    // Load days and activities
                    if (data.itinerary.days) {
                        data.itinerary.days.forEach(day => {
                            addDay();
                            // Populate activities for this day
                            const dayContainer = document.querySelector('.day-container:last-child');
                            const activitiesContainer = dayContainer.querySelector('.activities-container');
                            activitiesContainer.innerHTML = ''; // Clear default activity

                            day.activities.forEach((activity, index) => {
                                const activityDiv = document.createElement('div');
                                activityDiv.className = 'activity-item flex items-center space-x-2 mt-2';
                                activityDiv.innerHTML = `
                                    <input type="text" name="days[${dayCount}][activities][]" 
                                           value="${activity}" 
                                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                                    <input type="time" name="days[${dayCount}][times][]"
                                           value="${day.times[index]}"
                                           class="w-32 rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                                    <button type="button" onclick="${index === 0 ? 'addActivity(this)' : 'this.closest(\'.activity-item\').remove()'}" 
                                            class="${index === 0 ? 'text-[#255D4F] hover:text-[#1e4d70]' : 'text-red-600 hover:text-red-800'}">
                                        <i class="fas fa-${index === 0 ? 'plus' : 'minus'}-circle"></i>
                                    </button>
                                `;
                                activitiesContainer.appendChild(activityDiv);
                            });

                            // Set day notes
                            const notesTextarea = dayContainer.querySelector('textarea[name^="days"][name$="[notes]"]');
                            if (notesTextarea && day.notes) {
                                notesTextarea.value = day.notes;
                            }
                        });
                    }

                    // Load featured image
                    if (data.itinerary.featured_image) {
                        const container = document.getElementById('imagePreviewContainer');
                        container.innerHTML = `
                            <div class="relative">
                                <img src="${data.itinerary.featured_image}" alt="existing" class="image-preview rounded">
                                <button type="button" onclick="clearImage()" 
                                        class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center">
                                    ×
                                </button>
                                <input type="hidden" name="existing_image" value="${data.itinerary.featured_image}">
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Error loading itinerary:', error);
                alert('Error loading itinerary data: ' + error.message);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
