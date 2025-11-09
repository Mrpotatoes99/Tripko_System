<?php
require_once('../../../tripko-backend/config/Database.php');
require_once('../../../tripko-backend/check_session.php');
checkTourismOfficerSession();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

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

// Check if we're in add/edit mode
$mode = isset($_GET['action']) ? $_GET['action'] : 'list';
// guarded festival id for edit mode
$festival_id = isset($_GET['id']) ? intval($_GET['id']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Festivals Management - <?php echo htmlspecialchars($town_name); ?></title>
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
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/festivals.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold active bg-[#1e4d70]">
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
    <main class="ml-64 mt-16 px-6 py-10 min-h-screen bg-[#F3F1E8]">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-10">
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-[#255D4F]">Festivals Management</h1>
                <?php if ($mode === 'list'): ?>
                <a href="?action=add" class="flex items-center px-5 py-2 bg-[#255D4F] text-white rounded-lg font-semibold hover:bg-[#1e4d70] transition duration-200 shadow">
                    <i class="fas fa-plus-circle mr-2"></i>Add New Festival
                </a>
                <?php endif; ?>
            </div>

            <?php if ($mode === 'list'): ?>
            <!-- Festivals List -->
            <div class="card bg-white rounded-2xl shadow-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="festivalsList">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading festivals...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php else: ?>
            <!-- Add/Edit Form -->
            <div class="card bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-bold mb-6"><?php echo $mode === 'add' ? 'Add New Festival' : 'Edit Festival'; ?></h2>
                <form id="festivalForm" class="space-y-6" enctype="multipart/form-data">
                    <?php if ($mode === 'edit'): ?>
                    <input type="hidden" name="festival_id" value="<?php echo htmlspecialchars($festival_id); ?>">
                    <?php endif; ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Festival Name</label>
                            <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Festival Date</label>
                            <input type="date" name="date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#255D4F] focus:ring focus:ring-[#255D4F] focus:ring-opacity-50">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Festival Image</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <div class="flex flex-wrap gap-4 mb-4" id="imagePreviewContainer"></div>
                                    <div class="flex text-sm text-gray-600">
                                        <label class="relative cursor-pointer bg-white rounded-md font-medium text-[#255D4F] hover:text-[#1e4d70] focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-[#255D4F]">
                                            <span>Upload an image</span>
                                            <input type="file" name="image" accept="image/*" class="sr-only" onchange="previewImages(this)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <a href="festivals.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#255D4F]">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 bg-[#255D4F] text-white rounded-md hover:bg-[#1e4d70] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#255D4F]">
                            <?php echo $mode === 'add' ? 'Create Festival' : 'Update Festival'; ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Utility function for handling fetch requests with proper error handling
    async function fetchWithErrorHandling(url, options = {}) {
            try {
                const defaultOptions = {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                };

                const response = await fetch(url, { ...defaultOptions, ...options });
                
                if (!response.ok) {
                    if (response.status === 401) {
                        // Session expired or unauthorized
                        window.location.href = '/tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php?error=session';
                        return null;
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new TypeError("Received non-JSON response from server");
                }

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Unknown error occurred');
                }
                return data;
            } catch (error) {
                console.error('Fetch error:', error);
                showError(error.message);
                return null;
            }
        }

        // Add image preview function for festival uploads
        function previewImages(input) {
            const container = document.getElementById('imagePreviewContainer');
            container.innerHTML = '';
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="preview" class="image-preview rounded">
                        <button type="button" onclick="this.parentElement.remove()" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center">×</button>
                    `;
                    container.appendChild(div);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded';
            errorDiv.style.zIndex = '9999';
            errorDiv.innerHTML = `
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">${message}</span>
                <button onclick="this.parentElement.remove()" class="absolute top-0 right-0 px-4 py-3">
                    <svg class="h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </button>
            `;
            document.body.appendChild(errorDiv);
            setTimeout(() => errorDiv.remove(), 5000);
        }

        // Image preview handler used by the add/edit festival form
        function previewImages(input) {
            const container = document.getElementById('imagePreviewContainer');
            if (!container) return; // safe guard when not on list page
            container.innerHTML = '';

            if (!input || !input.files) return;

            const maxFileSize = 10 * 1024 * 1024; // 10MB

            [...input.files].forEach(file => {
                if (!file.type.startsWith('image/')) {
                    alert(`File "${file.name}" is not an image`);
                    return;
                }

                if (file.size > maxFileSize) {
                    alert(`File "${file.name}" is too large. Maximum size is 10MB`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = e => {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="preview" class="image-preview rounded object-cover">
                        <button type="button" onclick="this.parentElement.remove()" 
                                class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center">×</button>
                    `;
                    container.appendChild(div);
                };
                reader.onerror = () => {
                    alert(`Error reading file "${file.name}"`);
                };
                reader.readAsDataURL(file);
            });
        }

        async function loadFestivals() {
            const tbody = document.getElementById('festivalsList');
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
                    </td>
                </tr>
            `;

            let data = null;
            try {
                data = await fetchWithErrorHandling('../../../tripko-backend/api/tourism_officers/festivals.php');
            } catch (error) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-red-600">
                            <div class="flex flex-col items-center py-4">
                                <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                                <p class="text-lg">Error loading festivals</p>
                                <p class="text-sm">${error.message || 'Unknown error occurred.'}</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            if (!data.festivals || data.festivals.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            <div class="flex flex-col items-center py-4">
                                <i class="fas fa-calendar-times text-4xl mb-2"></i>
                                <p class="text-lg">No festivals found</p>
                                <p class="text-sm">Click "Add New Festival" to create one</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = '';
            data.festivals.forEach(festival => {
                const imagePath = festival.image_path ? 
                    (festival.image_path.startsWith('http') ? festival.image_path : '../../../uploads/' + festival.image_path) : 
                    '../../../assets/images/placeholder.jpg';

                const festivalDate = new Date(festival.date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                tbody.innerHTML += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded object-cover" 
                                         src="${imagePath}" 
                                         alt="${festival.name}"
                                         onerror="this.src='../../../assets/images/placeholder.jpg'">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${festival.name}</div>
                                    <div class="text-sm text-gray-500">${festivalDate}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            ${festival.description ? festival.description.substring(0, 100) + '...' : 'No description available'}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                festival.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                            }">
                                ${festival.status}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <a href="?action=edit&id=${festival.festival_id}" class="text-[#255D4F] hover:text-[#1e4d70] mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                `;
            });
        }

        // Festival add form handler (POST with FormData)
        <?php if ($mode === 'add'): ?>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('festivalForm');
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const fetchUrl = '../../../tripko-backend/api/tourism_officers/festivals.php';
                    console.log('Festival add fetch:', fetchUrl, 'method: POST');
                    try {
                        const response = await fetch(fetchUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: formData
                        });
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            alert('Fetch error! Non-JSON response. Check browser Network tab for request details.');
                            throw new TypeError('Received non-JSON response from server');
                        }
                        const data = await response.json();
                        if (data.success) {
                            alert('Festival created successfully');
                            window.location.href = 'festivals.php';
                        } else {
                            throw new Error(data.message || 'Failed to create festival');
                        }
                    } catch (error) {
                        showError(error.message);
                    }
                });
            }
        });
        <?php endif; ?>
        // Initialize the page: only load list when in list mode
        <?php if ($mode === 'list'): ?>
        document.addEventListener('DOMContentLoaded', loadFestivals);
        <?php endif; ?>
    </script>
</body>
</html>
