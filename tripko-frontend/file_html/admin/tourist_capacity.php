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
    <title>Tourist Spot Capacity Management - TripKo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="stylesheet" href="../file_css/navbar.css" />    
    <style>
        body, select, input, button {
            font-family: 'Kameron', serif;
            font-size: 17px;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            word-wrap: break-word;
            vertical-align: middle;
        }

        th {
            background-color: #f9fafb;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.875rem;
            color: #4a5568;
        }

        tbody tr:hover {
            background-color: #f8fafc;
            transition: background-color 0.3s ease;
        }

        /* Column widths */
        th:nth-child(1), td:nth-child(1) { width: 25%; } /* Tourist Spot */
        th:nth-child(2), td:nth-child(2) { width: 15%; } /* Town */
        th:nth-child(3), td:nth-child(3) { width: 15%; } /* Current/Max */
        th:nth-child(4), td:nth-child(4) { width: 20%; } /* Capacity */
        th:nth-child(5), td:nth-child(5) { width: 15%; } /* Last Updated */
        th:nth-child(6), td:nth-child(6) { width: 10%; } /* Actions */
    </style>
</head>
<body class="bg-white text-gray-900">    <div class="flex min-h-screen">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-1 bg-[#F3F1E7] p-6">
            <header class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3 text-gray-900">
                    <h1 class="text-2xl font-medium">Tourist Spot Capacity Management</h1>
                </div>
                <div class="flex items-center gap-4">               
                     <select id="townFilter" class="rounded-lg border border-gray-400 bg-white py-2 px-4 text-gray-600">
                        <option value="">All Towns</option>
                    </select>
                </div>
            </header>           
             <!-- Filter controls -->
            <div class="mb-6 flex gap-4">       <div class="flex gap-4">
                    <select id="capacityFilter" class="rounded-lg border border-gray-400 bg-white py-2 px-4 text-gray-600">
                        <option value="all">All Capacities</option>
                        <option value="high" class="text-red-500">High Capacity (90%+)</option>
                        <option value="medium" class="text-yellow-500">Medium Capacity (75-90%)</option>
                        <option value="low" class="text-green-500">Low Capacity (<75%)</option>
                    </select>
                    <div class="relative flex-1">
                        <input type="text" id="searchSpot" placeholder="Search tourist spots..." 
                            class="w-full rounded-lg border border-gray-400 bg-white py-2 pl-10 pr-4 text-gray-600">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <div id="filterStats" class="mt-2 flex gap-4 text-sm">
                    <span id="totalSpots" class="text-gray-600"></span>
                    <span id="filterInfo" class="text-gray-600"></span>
                </div>
            </div>

            <!-- Capacity Management Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">Tourist Spot</th>
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">Town</th>
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">Current/Max</th>
                            <th class="border border-gray-300 px-4 py-2 text-center font-bold">Capacity</th>
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">Last Updated</th>
                            <th class="border border-gray-300 px-4 py-2 text-center font-bold">Actions</th>
                        </tr>
                    </thead>                    <tbody id="capacityTableBody">
                        <!-- Table rows will be populated here -->
                    </tbody>
                </table>
                <div id="noResultsMessage" class="hidden text-center py-8">
                    <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                    <p class="text-xl text-gray-600">No tourist spots found</p>
                    <p class="text-gray-500">Try adjusting your search filters</p>
                </div>
            </div>
        </main>
    </div>    <!-- Capacity Update Modal -->
    <div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-xl font-medium">Update Capacity</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-4">
                <p id="modalSpotName" class="text-lg font-medium"></p>
                <p id="modalTownName" class="text-gray-600"></p>
            </div>            <div class="mb-4">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="modalCapacityBar" class="h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                <div class="mt-2 flex justify-between items-center text-sm">
                    <span id="modalStatus" class="px-2 py-1 rounded-full text-white text-xs"></span>
                    <span><span id="modalCapacityPercentage" class="font-medium">0%</span> Full</span>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700">Current Visitors</label>
                    <div class="flex items-center gap-2">
                        <input type="number" id="currentCapacity" 
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2" 
                            min="0" oninput="updateModalCapacityVisual()">
                        <button onclick="decrementCapacity()" class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button onclick="incrementCapacity()" class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700">Maximum Capacity</label>
                    <input type="number" id="maxCapacity" 
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2" 
                        min="1" oninput="updateModalCapacityVisual()">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                    Cancel
                </button>
                <button onclick="updateCapacity()" class="px-4 py-2 bg-[#255d8a] text-white rounded-lg hover:bg-[#1e4d70] flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </div>
        </div>
    </div>    <script>
        let spots = [];
        let selectedSpotId = null;        function filterSpots() {
            const searchText = document.getElementById('searchSpot').value.toLowerCase();
            const capacityFilter = document.getElementById('capacityFilter').value;
            const townFilter = document.getElementById('townFilter').value;

            return spots.filter(spot => {
                const percentage = calculateCapacityPercentage(spot.current_capacity, spot.max_capacity);
                const status = getStatusByPercentage(percentage);
                
                // Search text filter
                const matchesSearch = spot.name.toLowerCase().includes(searchText) ||
                                    spot.town_name.toLowerCase().includes(searchText) ||
                                    status.toLowerCase().includes(searchText);

                // Town filter
                const matchesTown = !townFilter || spot.town_id.toString() === townFilter;

                // Capacity filter
                const matchesCapacity = 
                    capacityFilter === 'all' ||
                    (capacityFilter === 'high' && percentage >= 90) ||
                    (capacityFilter === 'medium' && percentage >= 75 && percentage < 90) ||
                    (capacityFilter === 'low' && percentage < 75);

                return matchesSearch && matchesTown && matchesCapacity;
            });
        }

        function updateFilterUI() {
            const selectedFilter = document.getElementById('capacityFilter').value;
            const filterButton = document.getElementById('capacityFilter');
            
            // Reset classes
            filterButton.className = 'rounded-lg border py-2 px-4 text-gray-600';
            
            // Apply color based on selection
            switch(selectedFilter) {
                case 'high':
                    filterButton.className += ' border-red-500 text-red-500';
                    break;
                case 'medium':
                    filterButton.className += ' border-yellow-500 text-yellow-500';
                    break;
                case 'low':
                    filterButton.className += ' border-green-500 text-green-500';
                    break;
                default:
                    filterButton.className += ' border-gray-400 bg-white';
            }
        }        function updateStats(filteredSpots) {
            const totalSpotsEl = document.getElementById('totalSpots');
            const filterInfoEl = document.getElementById('filterInfo');
            
            const total = spots.length;
            const filtered = filteredSpots.length;
            
            // Count spots by status
            const statusCounts = filteredSpots.reduce((acc, spot) => {
                const status = spot.status || getStatusByPercentage(calculateCapacityPercentage(spot.current_capacity, spot.max_capacity));
                acc[status] = (acc[status] || 0) + 1;
                return acc;
            }, {});
            
            totalSpotsEl.innerHTML = `<i class="fas fa-map-marker-alt mr-2"></i>Total Spots: ${total}`;
            
            if (filtered !== total) {
                filterInfoEl.innerHTML = `<i class="fas fa-filter mr-2"></i>Showing ${filtered} spots (${
                    statusCounts.High ? `<span class="text-red-500">${statusCounts.High} High</span>` : ''
                }${statusCounts.Medium ? `${statusCounts.High ? ', ' : ''}<span class="text-yellow-500">${statusCounts.Medium} Medium</span>` : ''
                }${statusCounts.Low ? `${statusCounts.Medium || statusCounts.High ? ', ' : ''}<span class="text-green-500">${statusCounts.Low} Low</span>` : ''
                })`;
            } else {
                filterInfoEl.innerHTML = '';
            }
        }

        function renderSpots() {
            const filteredSpots = filterSpots();
            const tbody = document.getElementById('capacityTableBody');
            const noResultsMessage = document.getElementById('noResultsMessage');
            
            if (filteredSpots.length === 0) {
                tbody.innerHTML = '';
                noResultsMessage.classList.remove('hidden');
                updateStats(filteredSpots);
                return;
            }
            
            noResultsMessage.classList.add('hidden');
            tbody.innerHTML = '';
            
            filteredSpots.forEach(spot => {
                const percentage = spot.capacity_percentage || 0;
                const colorClass = getColorByPercentage(percentage);
                const statusClass = `bg-${colorClass}`;
                const rowHtml = `
                    <tr class="hover:bg-gray-50">
                        <td class="border border-gray-300 px-4 py-2">
                            <div class="font-medium">${spot.name}</div>
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-gray-600">
                            ${spot.town_name}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-gray-600">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-users text-${colorClass}"></i>
                                ${spot.current_capacity || 0} / ${spot.max_capacity || 100}
                            </div>
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <div class="flex items-center gap-2">
                                <div class="flex-grow bg-gray-200 rounded-full h-2.5">
                                    <div class="${statusClass} h-2.5 rounded-full transition-all duration-500" 
                                         style="width: ${percentage}%"></div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="${statusClass} text-white px-2 py-0.5 rounded-full text-xs">
                                        ${spot.status} (${percentage}%)
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-sm text-gray-500">
                            <div class="flex items-center gap-2">
                                <i class="far fa-clock"></i>
                                <div>
                                    ${spot.last_updated ? new Date(spot.last_updated).toLocaleString() : 'Never'}
                                    ${spot.updated_by ? `<div class="text-xs text-gray-400">by ${spot.updated_by}</div>` : ''}
                                </div>
                            </div>
                        </td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <button class="updateCapacityBtn px-3 py-1 bg-[#255D8A] text-white rounded hover:bg-[#1e4d70] text-sm" data-id="${spot.spot_id}">
                                        <i class="fas fa-edit mr-1"></i>Update
                                    </button>
                                </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', rowHtml);
            });
        }

        async function loadTowns() {
            try {
                const response = await fetch('../../../tripko-backend/api/towns/read.php');
                const data = await response.json();
                const townFilter = document.getElementById('townFilter');
                
                data.records.forEach(town => {
                    const option = document.createElement('option');
                    option.value = town.town_id;
                    option.textContent = town.name;
                    townFilter.appendChild(option);
                });

                townFilter.addEventListener('change', renderSpots);
            } catch (error) {
                console.error('Error loading towns:', error);
            }
        }        async function loadCapacities() {
            try {
                const townId = document.getElementById('townFilter').value;
                const url = `../../../tripko-backend/api/tourist_spot_capacity/read.php${townId ? '?town_id=' + townId : ''}`;
                const response = await fetch(url);

                // Read raw response for safer parsing and debugging
                const raw = await response.text();
                if (!raw || raw.trim() === '') {
                    console.error('Empty response from', url);
                    showError('No data returned from server (empty response)');
                    spots = [];
                    renderSpots();
                    return;
                }

                let data;
                try {
                    data = JSON.parse(raw);
                } catch (parseErr) {
                    console.error('Failed to parse JSON from', url, 'raw response:', raw, parseErr);
                    showError('Invalid server response. Check server logs.');
                    spots = [];
                    renderSpots();
                    return;
                }

                spots = (data.records || []).map(spot => ({
                    ...spot,
                    capacity_percentage: calculateCapacityPercentage(spot.current_capacity, spot.max_capacity),
                    status: getStatusByPercentage(calculateCapacityPercentage(spot.current_capacity, spot.max_capacity))
                }));
                renderSpots();
            } catch (error) {
                console.error('Error loading capacities:', error);
                showError('Failed to load tourist spot capacities');
                spots = [];
                renderSpots();
            }
        }

        // UI helper to surface errors without throwing exceptions
        function showError(message) {
            console.error(message);
            const noResultsMessage = document.getElementById('noResultsMessage');
            if (noResultsMessage) {
                noResultsMessage.classList.remove('hidden');
                const primary = noResultsMessage.querySelector('p');
                if (primary) primary.textContent = message;
            } else {
                alert(message);
            }
        }

        function calculateCapacityPercentage(current, max) {
            return Math.round((current / max) * 100) || 0;
        }

        function getStatusByPercentage(percentage) {
            if (percentage >= 90) return 'High';
            if (percentage >= 75) return 'Medium';
            return 'Low';
        }

        function getColorByPercentage(percentage) {
            if (percentage >= 90) return 'red-500';
            if (percentage >= 75) return 'yellow-500';
            return 'green-500';
        }        function updateModalCapacityVisual() {
            const current = parseInt(document.getElementById('currentCapacity').value) || 0;
            const max = parseInt(document.getElementById('maxCapacity').value) || 100;
            const percentage = Math.min(Math.round((current / max) * 100), 100);
            const colorClass = getColorByPercentage(percentage);
            const status = getStatusByPercentage(percentage);

            // Update progress bar
            const bar = document.getElementById('modalCapacityBar');
            bar.style.width = percentage + '%';
            bar.className = `h-2.5 rounded-full bg-${colorClass} transition-all duration-300`;

            // Update percentage text
            const percentageEl = document.getElementById('modalCapacityPercentage');
            percentageEl.textContent = percentage + '%';
            percentageEl.className = `font-medium text-${colorClass}`;

            // Update status badge
            const statusEl = document.getElementById('modalStatus');
            statusEl.textContent = status;
            statusEl.className = `px-2 py-1 rounded-full text-white text-xs bg-${colorClass}`;

            // Update capacity numbers color
            const currentInput = document.getElementById('currentCapacity');
            currentInput.className = `mt-1 block w-full rounded-lg border ${
                current > max ? 'border-red-500' : 'border-gray-300'
            } px-3 py-2 ${current > max ? 'text-red-500' : ''}`;

            // Show warning if current exceeds max
            if (current > max) {
                currentInput.title = 'Current capacity cannot exceed maximum capacity';
            } else {
                currentInput.title = '';
            }
        }

        function incrementCapacity() {
            const input = document.getElementById('currentCapacity');
            const max = parseInt(document.getElementById('maxCapacity').value) || 100;
            const current = parseInt(input.value) || 0;
            input.value = Math.min(current + 1, max);
            updateModalCapacityVisual();
        }

        function decrementCapacity() {
            const input = document.getElementById('currentCapacity');
            const current = parseInt(input.value) || 0;
            input.value = Math.max(current - 1, 0);
            updateModalCapacityVisual();
        }

        function openUpdateModal(spotId, name, town, current, max) {
            selectedSpotId = spotId;
            document.getElementById('modalSpotName').textContent = name;
            document.getElementById('modalTownName').textContent = town;
            document.getElementById('currentCapacity').value = current;
            document.getElementById('maxCapacity').value = max;
            document.getElementById('updateModal').classList.remove('hidden');
            updateModalCapacityVisual();
        }

        function closeModal() {
            document.getElementById('updateModal').classList.add('hidden');
            selectedSpotId = null;
        }

        async function updateCapacity() {
            if (!selectedSpotId) return;

            const current = parseInt(document.getElementById('currentCapacity').value);
            const max = parseInt(document.getElementById('maxCapacity').value);

            if (current > max) {
                alert('Current capacity cannot exceed maximum capacity');
                return;
            }

            try {
                const response = await fetch('../../../tripko-backend/api/tourist_spot_capacity/update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        spot_id: selectedSpotId,
                        current_capacity: current,
                        max_capacity: max
                    })
                });

                const data = await response.json();
                if (data.success) {
                    closeModal();
                    await loadCapacities();
                } else {
                    alert(data.message || 'Failed to update capacity');
                }
            } catch (error) {
                console.error('Error updating capacity:', error);
                alert('Failed to update capacity');
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            loadTowns();
            loadCapacities();            // Set up search and filter listeners
            document.getElementById('searchSpot').addEventListener('input', () => {
                renderSpots();
                updateFilterUI();
            });
            document.getElementById('capacityFilter').addEventListener('change', () => {
                renderSpots();
                updateFilterUI();
            });

            // Handle Enter key in inputs
            document.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        updateCapacity();
                    }
                });
            });

            // Close modal on background click
            document.getElementById('updateModal').addEventListener('click', (e) => {
                if (e.target === e.currentTarget) {
                    closeModal();
                }
            });
        });
    </script>
    <script>
        (function() {
            // Determine view-only from PHP session
            const CURRENT_USER_TYPE = <?php echo json_encode($_SESSION['user_type_id'] ?? null); ?>;
            const VIEW_ONLY = CURRENT_USER_TYPE === 1; // Super Admin = 1 -> view-only

            document.addEventListener('DOMContentLoaded', () => {
                const tbody = document.getElementById('capacityTableBody');

                function attachUpdateHandlers() {
                    if (!tbody) return;
                    tbody.querySelectorAll('.updateCapacityBtn').forEach(btn => {
                        // avoid attaching twice
                        if (btn.dataset.listenerAttached) return;
                        if (VIEW_ONLY) {
                            btn.addEventListener('click', (e) => {
                                e.preventDefault();
                                alert('View-only account: action disabled.');
                            });
                        } else {
                            btn.addEventListener('click', (e) => {
                                const id = btn.dataset.id;
                                const spot = spots.find(s => String(s.spot_id) === String(id));
                                if (spot) {
                                    openUpdateModal(spot.spot_id, spot.name, spot.town_name, spot.current_capacity || 0, spot.max_capacity || 100);
                                }
                            });
                        }
                        btn.dataset.listenerAttached = '1';
                    });
                }

                // Observe table body for new rows and attach handlers
                if (tbody) {
                    const obs = new MutationObserver(attachUpdateHandlers);
                    obs.observe(tbody, { childList: true, subtree: true });
                }

                // Attach to any existing buttons immediately
                attachUpdateHandlers();

                // If view-only, also disable the save action
                if (VIEW_ONLY) {
                    window.updateCapacity = function() { alert('View-only account: action disabled.'); };
                }
            });
        })();
    </script>
</body>
</html>