<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TripKo Pangasinan - Tourist Spots</title>  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
  <link rel="stylesheet" href="../../file_css/dashboard.css" />
  <link rel="stylesheet" href="../css/tourist_spot.css" /> 
  <style>
    /* Only keep the essential styles that need to be inline */
    body {
        font-family: 'poppins';
        font-size: 17px;
    }

    .nav-links a,
    .font-medium,
    button,
    select,
    input,
    p,
    h1, h2, h3, h4, h5, h6 {
        font-family: 'poppins';
    }

    #municipalityFilter option {
        background-color: white;
        color: #1a202c;
    }

    canvas#transportChart {
        width: 100% !important;
        height: 100% !important;
        position: absolute !important;
        top: 0;
        left: 0;
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
        const transportDropdown = document.getElementById('transportDropdown');
        const transportDropdownIcon = document.getElementById('transportDropdownIcon');

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#transportDropdown') && !e.target.closest('[onclick*="toggleTransportDropdown"]')) {
                transportDropdown?.classList.add('hidden');
                if (transportDropdownIcon) {
                    transportDropdownIcon.style.transform = 'rotate(0deg)';
                }
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
  </script>
</head>
<body class="bg-white text-gray-900">
  <div class="flex min-h-screen">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <!-- Main content -->
    <main class="flex-1 bg-[#F3F1E7] p-6">
      <div class="mb-6">
        <div class="flex items-center justify-between mb-4">          <div class="flex items-center gap-3 text-gray-900 font-normal text-base">
              <button aria-label="Menu" class="focus:outline-none">
                  <i class="fas fa-bars text-lg"></i>
              </button>
              <h2 class="font-medium text-xl">Tourist Spots</h2>
          </div>

          <div class="flex items-center gap-4">
            <div>
              <input type="search" placeholder="Search" class="w-48 md:w-64 rounded-full border border-gray-400 bg-[#F3F1E7] py-1.5 px-4 text-gray-600 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#255D8A]" />
            </div>            <div class="relative" id="adminDropdown">
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
        </div>        <div class="flex justify-end gap-3 items-center">          <button onclick="openModal()" class="bg-[#255D8A] text-white px-4 py-2 rounded-md hover:bg-[#1e4d70] transition-colors h-[40px] flex items-center min-w-[120px] justify-center">
            + Add new spot          </button>          <button onclick="toggleView()" id="viewToggleBtn" class="bg-[#255D8A] text-white px-4 py-2 rounded-md hover:bg-[#1e4d70] transition-colors h-[40px] flex items-center min-w-[140px] justify-center">
            <i class="fas fa-table mr-2"></i>Table View
          </button>          <div class="relative">
            <select id="categoryFilter" onchange="filterTouristSpots()" class="bg-[#255D8A] text-white px-4 py-2 text-[16px] rounded-md hover:bg-[#1e4d70] transition-colors cursor-pointer min-w-[100px] h-[40px]">
              <option value="" class="bg-white text-gray-900">Filter</option>
              <option value="Beach" class="bg-white text-gray-900">Beach</option>
              <option value="Islands" class="bg-white text-gray-900">Islands</option>
              <option value="Waterfalls" class="bg-white text-gray-900">Waterfalls</option>
              <option value="Caves" class="bg-white text-gray-900">Caves</option>
              <option value="Churches" class="bg-white text-gray-900">Churches and Cathedrals</option>
              <option value="Festivals" class="bg-white text-gray-900">Festivals</option>            </select>
          </div>
          <div class="relative">
            <select id="municipalityFilter" onchange="filterTouristSpots()" class="bg-[#255D8A] text-white px-4 py-2 text-[16px] rounded-md hover:bg-[#1e4d70] transition-colors cursor-pointer min-w-[150px] h-[40px]">
              <option value="" class="bg-white text-gray-900">All Municipalities</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Tourist spots grid -->
      <div id="gridView">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Tourist spot cards will be dynamically added here -->
        </div>
        <!-- Grid Pagination -->
        <div class="flex justify-center items-center mt-6 gap-2" id="gridPagination">
          <!-- Pagination controls will be added here -->
        </div>
      </div>

      <!-- Tourist spots table -->
      <div id="tableView" class="hidden">
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <table class="w-full border-collapse">
            <thead class="bg-gray-50">
              <tr>
                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Name</th>
                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Category</th>
                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Municipality</th>
                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Contact Info</th>
                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Description</th>
                <th class="border border-gray-300 px-4 py-2 text-center font-bold">Status</th>
                <th class="border border-gray-300 px-4 py-2 text-center font-bold">Actions</th>
              </tr>
            </thead>
            <tbody id="spotTableBody">
              <!-- Tourist spot rows will be dynamically added here -->
            </tbody>
          </table>
        </div>
        <!-- Table Pagination -->
        <div class="flex justify-center items-center mt-6 gap-2" id="tablePagination">
          <!-- Pagination controls will be added here -->
        </div>
      </div>
      <style>
      #tableView {
        margin: 2rem auto;
        max-width: 1000px;
        font-family: 'Poppins';
  }

  .hidden {
    display: none;
  }

  .bg-white {
    background-color: #fff;
  }

  .rounded-lg {
    border-radius: 12px;
  }

  .shadow {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .overflow-hidden {
    overflow: hidden;
  }

  table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
  }

  th,
  td {
    border: 1px solid #e2e8f0;
    padding: 16px;
    text-align: left;
    font-size: 14px;
    height: 60px;
    word-wrap: break-word;
    vertical-align: middle;
  }

  th {
    font-weight: 500;
    background-color: #BBDEFB;
    color: #0d47a1;
  }

  /* Set specific widths for 7 columns */
  th:nth-child(1){ width: 14%; }  /* Name */
  th:nth-child(2) { width: 10%; }  /* Category */
  th:nth-child(3) { width: 12%; }  /* Municipality */
  th:nth-child(4) { width: 15%; }  /* Contact Info */
  th:nth-child(5) { width: 20%; }  /* Description */
  th:nth-child(6) { width: 10%; text-align: justify; }  /* Actions */
  th:nth-child(7) { width: 21%; text-align: justify;}  /* Any extra action icon or button */

  tbody tr:nth-child(odd) {
    background-color: #ffffff;
  }

  tbody tr:nth-child(even) {
    background-color: #f9fafb;
  }

  tbody tr:hover {
    background-color: #f1f5f9;
    transition: background-color 0.3s ease;
  }

  .text-center {
    text-align: center;
  }

  .action-btn {
    background-color: #3b82f6;
    color: #fff;
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: background-color 0.2s ease;
  }

  .action-btn:hover {
    background-color: #2563eb;
  }
</style>
    </main>
  </div>

  <!-- Add New Spot Modal -->
  <div id="addSpotModal" class="fixed inset-0 hidden z-50">
    <div class="bg-black bg-opacity-50 absolute inset-0"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
      <div class="form-container bg-white relative z-10 p-8 rounded shadow-lg w-full max-w-2xl">
        <button type="button" class="absolute right-4 top-4 text-gray-500 hover:text-gray-700" onclick="closeModal()">
          <i class="fas fa-times text-xl"></i>
        </button>

        <h2 class="text-xl font-bold mb-4 text-[#255D8A]">Add Tourist Spot</h2>
       
        <form id="spotForm" enctype="multipart/form-data">
          <!-- Tourist Spot Name and Category -->
          <div class="form-row grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label for="spot-name" class="block text-[15px] font-medium text-gray-700 mb-1">
                Tourist Spot Name <span class="text-red-500">*</span>
              </label>
              <input type="text" id="spot-name" name="name" required 
                     class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]" />
            </div>
            
            <div class="form-group">
              <label for="category" class="block text-[15px] font-medium text-gray-700 mb-2">
                Category <span class="text-red-500">*</span>
              </label>
              <select id="category" name="category" required 
                      class="w-full border rounded-md px-3 py-2 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]">
                <option value="" disabled selected>Select category</option>
                <option value="Beach">Beach</option>
                <option value="Islands">Islands</option>
                <option value="Waterfalls">Waterfalls</option>
                <option value="Caves">Caves</option>
                <option value="Churches">Churches and Cathedrals</option>
                <option value="Festivals">Festivals</option>
              </select>
            </div>
          </div>

          <!-- Description -->
          <div class="form-group mb-4">
            <label for="spot-description" class="block text-[15px] font-medium text-gray-700 mb-1">
              Description <span class="text-red-500">*</span>
            </label>
            <textarea id="spot-description" name="description" rows="4" required 
                      class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]"></textarea>
          </div>

          <!-- Municipality and Contact Info -->
          <div class="form-row grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label for="townSelect" class="block text-[15px] font-medium text-gray-700 mb-1">
                Municipality <span class="text-red-500">*</span>
              </label>
              <select id="townSelect" name="town_id" required 
                      class="w-full border rounded-md px-3 py-2 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]">
                <option value="" disabled selected>Select municipality</option>
              </select>
            </div>

            <div class="form-group">
              <label for="contact-info" class="block text-[15px] font-medium text-gray-700 mb-1">
                Contact Info <span class="text-gray-500">(Optional)</span>
              </label>
              <input type="text" id="contact-info" name="contact_info" 
                     class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]" />
            </div>
          </div>

          <!-- Image Upload -->
          <div class="form-group mb-4">
            <div class="upload-area bg-white border border-gray-300 rounded-lg p-3">
              <div class="flex items-center gap-2">
                <button type="button" class="bg-[#255D8A] text-white px-3 py-1.5 rounded hover:bg-[#1e4d70] transition-colors">
                  <i class="fas fa-upload mr-2"></i>Choose Files
                </button>
                <span class="text-sm text-gray-500 file-names">No file chosen</span>
              </div>
              <input type="file" name="images[]" accept="image/png, image/jpeg" multiple class="hidden" id="fileInput" />
            </div>
          </div>

          <!-- Form Buttons -->
          <div class="flex justify-end space-x-2 pt-3 border-t">
            <button type="button" onclick="closeModal()" 
                    class="px-3 py-1.5 rounded-md bg-gray-200 hover:bg-gray-300 transition-colors text-[15px]">Cancel</button>
            <button type="submit" 
                    class="px-3 py-1.5 rounded-md bg-[#255D8A] text-white hover:bg-[#1e4d70] transition-colors text-[15px]">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Status Change Modal -->
  <div id="statusModal" class="fixed inset-0 hidden z-50">
    <div class="bg-black bg-opacity-50 absolute inset-0"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
      <div class="bg-white relative z-10 p-6 rounded-lg shadow-lg w-full max-w-md">
        <h3 class="text-lg font-medium mb-4">Update Tourist Spot Status</h3>
        <p class="mb-4">Current status: <span id="currentStatusText" class="font-medium"></span></p>
        <div class="flex gap-4">
          <button onclick="updateSpotStatus('active')" 
                  class="flex-1 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
            Set Active
          </button>
          <button onclick="updateSpotStatus('inactive')" 
                  class="flex-1 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
            Set Inactive
          </button>
        </div>
        <button onclick="closeStatusModal()" 
                class="mt-4 w-full border border-gray-300 px-4 py-2 rounded hover:bg-gray-50">
          Cancel
        </button>
      </div>
    </div>
  </div>

  <script>
    // Modal functionality and form handling
    const modal = document.getElementById('addSpotModal');
    const form = document.querySelector('form');
    const fileInput = document.querySelector('input[type="file"]');
    const uploadArea = document.querySelector('.upload-area');    function openModal() {
      modal.classList.remove('hidden');
      
      // Reset modal title to add mode
      document.querySelector('.form-container h2').textContent = 'Add Tourist Spot';
      
      // Remove any existing spot_id input to ensure we're in create mode
      const existingSpotId = form.querySelector('input[name="spot_id"]');
      if (existingSpotId) {
        existingSpotId.remove();
      }
      
      // Reset form and clear preview
      form.reset();
      const preview = uploadArea.querySelector('.image-preview');
      if (preview) preview.remove();
    }

    function closeModal() {
      modal.classList.add('hidden');
    }

    // Form submission handler - handles both create and edit
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(form);
      const files = fileInput.files;
      
      // Only append files if new ones were selected
      if (files.length > 0) {
        for(let i = 0; i < files.length; i++) {
          formData.append('images[]', files[i]);
        }
      }

      // Determine if this is an edit or create based on presence of spot_id
      const isEdit = formData.has('spot_id');
      const url = isEdit ? 
  '../../../tripko-backend/api/tourist_spot/update.php' : 
  '../../../tripko-backend/api/tourist_spot/create.php';

      try {
        const response = await fetch(url, {
          method: 'POST',
          body: formData
        });

        const data = await response.json();
        if(data.success) {
          alert(isEdit ? 'Tourist spot updated successfully!' : 'Tourist spot added successfully!');
          closeModal();
          loadTouristSpots();
        } else {
          throw new Error(data.message || `Failed to ${isEdit ? 'update' : 'save'} tourist spot`);
        }
      } catch (error) {
        console.error(isEdit ? 'Update error:' : 'Save error:', error);
        alert('Error: ' + error.message);
      }
    });

    // Transport dropdown toggle
    function toggleTransportDropdown(event) {
      event.preventDefault();
      const dropdown = document.getElementById('transportDropdown');
      const icon = document.getElementById('transportDropdownIcon');
      dropdown.classList.toggle('hidden');
      icon.style.transform = dropdown.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    // File upload handling
    uploadArea.addEventListener('click', () => fileInput.click());    fileInput.addEventListener('change', (e) => {
      const files = Array.from(e.target.files);
      const fileNamesSpan = uploadArea.querySelector('.file-names');
      
      if (files.length > 0) {
        const fileNames = files.map(file => file.name).join(', ');
        fileNamesSpan.textContent = files.length === 1 
          ? fileNames 
          : `${files.length} files selected: ${fileNames}`;
      } else {
        fileNamesSpan.textContent = 'No file chosen';
      }
    });

    // Helper functions
    function getImageUrl(imagePath) {
      if (!imagePath || imagePath === 'placeholder.jpg') {
        return 'https://placehold.co/400x300?text=No+Image';
      }
      return `/TripKo-System/uploads/${imagePath}`;
    }

    // Global state for pagination
    let currentGridPage = 1;
    let currentTablePage = 1;
    
    // Render pagination controls
    function renderPagination(containerId, currentPage, totalPages, onPageChange) {
      const container = document.getElementById(containerId);
      if (!container) return;

      let html = '';
      
      // Previous button
      html += `
        <button 
          onclick="handlePageChange(${currentPage - 1}, '${containerId}')"
          class="px-3 py-1 rounded-md text-sm ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-[#255D8A] text-white hover:bg-[#1e4d70]'}"
          ${currentPage === 1 ? 'disabled' : ''}>
          <i class="fas fa-chevron-left"></i>
        </button>
      `;

      // Page numbers
      for (let i = 1; i <= totalPages; i++) {
        if (
          i === 1 || // First page
          i === totalPages || // Last page
          (i >= currentPage - 1 && i <= currentPage + 1) // Pages around current
        ) {
          html += `
            <button 
              onclick="handlePageChange(${i}, '${containerId}')"
              class="px-3 py-1 rounded-md text-sm ${i === currentPage ? 'bg-[#255D8A] text-white' : 'bg-gray-100 hover:bg-gray-200'}">
              ${i}
            </button>
          `;
        } else if (
          i === currentPage - 2 ||
          i === currentPage + 2
        ) {
          html += `<span class="px-2">...</span>`;
        }
      }

      // Next button
      html += `
        <button 
          onclick="handlePageChange(${currentPage + 1}, '${containerId}')"
          class="px-3 py-1 rounded-md text-sm ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-[#255D8A] text-white hover:bg-[#1e4d70]'}"
          ${currentPage === totalPages ? 'disabled' : ''}>
          <i class="fas fa-chevron-right"></i>
        </button>
      `;

      container.innerHTML = html;
    }

    // Handle page changes
    function handlePageChange(newPage, containerId) {
      if (containerId === 'gridPagination') {
        currentGridPage = newPage;
        loadTouristSpots();
      } else {
        currentTablePage = newPage;
        loadTableView();
      }
    }

    // Load and display tourist spots with pagination
    async function loadTouristSpots() {
      const container = document.querySelector('#gridView .grid');
      try {
        container.innerHTML = `
          <div class="col-span-full flex justify-center items-center py-8">
            <div class="text-center">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-[#255D8A] mb-4"></div>
              <p class="text-gray-600">Loading tourist spots...</p>
            </div>
          </div>
        `;

        const selectedCategory = document.getElementById('categoryFilter').value;
        const selectedMunicipality = document.getElementById('municipalityFilter').value;

  let url = `../../../tripko-backend/api/tourist_spot/read.php?page=${currentGridPage}&view=grid`;
        if (selectedCategory) url += `&category=${encodeURIComponent(selectedCategory)}`;
        if (selectedMunicipality) url += `&municipality=${encodeURIComponent(selectedMunicipality)}`;

        const response = await fetch(url);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status} - ${await response.text()}`);
        }

        const data = await response.json();
        
        if (!data || !data.records || !Array.isArray(data.records)) {
          throw new Error('Invalid data format received from server');
        }

        container.innerHTML = '';
        
        if (data.records.length === 0) {
          container.innerHTML = `
            <div class="col-span-full text-center py-8 text-gray-500">
              <i class="fas fa-filter text-4xl mb-3 block"></i>
              <p class="text-[17px] font-medium" style="font-family: 'poppins'">No tourist spots found</p>
            </div>
          `;
        } else {
          data.records.forEach(spot => {
            const statusClass = spot.status === 'inactive' ? 'bg-red-100 border-red-300' : '';
            container.innerHTML += `
              <div class="rounded-lg overflow-hidden border border-gray-200 shadow-md bg-white flex flex-col h-full transition-transform hover:scale-105 hover:shadow-lg ${statusClass}">
                <div class="relative w-full h-48 bg-gray-100">
                  <img src="${getImageUrl(spot.image_path)}" 
                       alt="${spot.name || 'Tourist Spot'}"                           class="w-full h-full object-cover transition-all duration-300" 
                           onerror="this.src='../images/placeholder.jpg'" />
                  ${spot.status === 'inactive' ? '<div class="absolute top-2 left-2 bg-red-500 text-white font-medium text-[17px] px-2 py-1 rounded" style="font-family: \'poppins\'">Inactive</div>' : ''}
                </div>
                <div class="flex-1 flex flex-col p-4" style="font-family: 'poppins'">
                  <div class="text-[16px] font-regular">
                    <h3 class="text-[#255D8A] mb-1 font-bold">${spot.name}</h3>
                    <p class="text-gray-700 mb-2 line-clamp-3">${spot.description}</p>
                  </div>
                  <div class="mt-auto text-[14px] font-regular">
                    
                    </p>
                    <p class="text-gray-500 mt-1 flex items-center">
                      <i class="fas fa-phone-alt mr-1"></i>${spot.contact_info || 'No contact info'}
                    </p>
                  </div>
                </div>
              </div>
            `;
          });
        }

        // Render pagination
        renderPagination('gridPagination', data.pagination.page, data.pagination.pages);

      } catch (error) {
        console.error('Fetch Error:', error);
        container.innerHTML = `
          <div class="col-span-full text-center py-8 text-red-500">
            <i class="fas fa-exclamation-circle text-4xl mb-3 block"></i>
            <p>Failed to load tourist spots. Please try again later.</p>
            <p class="text-sm mt-2">Error details: ${error.message}</p>
          </div>
        `;
      }
    }

    // Load municipalities for the select dropdown
    async function loadMunicipalities() {
      try {
        console.log('Fetching municipalities...');
  const response = await fetch('../../../tripko-backend/api/towns/read.php', {
          headers: {
            'Accept': 'application/json',
            'Cache-Control': 'no-cache'
          },
          credentials: 'same-origin'
        });
        
        console.log('Response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let data;
        try {
          data = JSON.parse(text);
          console.log('Parsed data:', data);
        } catch (e) {
          console.error('JSON parse error:', e);
          throw new Error('Invalid JSON response from server');
        }
        
        const townSelect = document.querySelector('select[name="town_id"]');
        const municipalityFilter = document.getElementById('municipalityFilter');
        
        if (!townSelect || !municipalityFilter) {
          console.error('Could not find select elements');
          throw new Error('Required elements not found');
        }
        
        townSelect.innerHTML = '<option value="" selected disabled>Select municipality</option>';
        municipalityFilter.innerHTML = '<option value="">All Municipalities</option>';
        
        if (data.success && data.records && Array.isArray(data.records)) {
          console.log('Found', data.records.length, 'municipalities');
          data.records.sort((a, b) => a.name.localeCompare(b.name)).forEach(town => {
            const option = document.createElement('option');
            option.value = town.town_id;
            option.textContent = town.name;
            townSelect.appendChild(option);
            
            const filterOption = option.cloneNode(true);
            municipalityFilter.appendChild(filterOption);
          });
        } else {
          console.error('Invalid data format:', data);
          throw new Error('Invalid data format received from server');
        }
      } catch (error) {
        console.error('Failed to load municipalities:', error);
        // Show error in both dropdowns
        const elements = [
          document.querySelector('select[name="town_id"]'),
          document.getElementById('municipalityFilter')
        ];
        
        elements.forEach(el => {
          if (el) {
            el.innerHTML = '<option value="" disabled selected>Error loading municipalities</option>';
          }
        });
        
        // Show error message to user
        alert('Failed to load municipalities. Please refresh the page and try again.');
      }
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', () => {
      loadTouristSpots();
      loadMunicipalities();
    });

    // Edit spot functionality
    async function editSpot(spot) {
      openModal();
      
      // Set form values
      form.name.value = spot.name || '';
      form.category.value = spot.category || '';
      form.description.value = spot.description || '';
      form.town_id.value = spot.town_id || '';
      form.contact_info.value = spot.contact_info || '';
      
      // Add spot_id to form for update
      let spotIdInput = form.querySelector('input[name="spot_id"]');
      if (!spotIdInput) {
        spotIdInput = document.createElement('input');
        spotIdInput.type = 'hidden';
        spotIdInput.name = 'spot_id';
        form.appendChild(spotIdInput);
      }
      spotIdInput.value = spot.spot_id;      // Show existing image filename if available
      if (spot.image_path) {
        const fileNamesSpan = uploadArea.querySelector('.file-names');
        fileNamesSpan.textContent = `Current file: ${spot.image_path}`;
      }
    }

    // Delete spot functionality
    async function deleteSpot(spotId, spotName) {
      if (confirm(`Are you sure you want to delete the tourist spot "${spotName}"?`)) {
        try {
          const response = await fetch(`../../../tripko-backend/api/tourist_spot/delete.php?spot_id=${spotId}`, {
            method: 'DELETE'
          });
          const data = await response.json();
          if (data.success) {
            alert('Tourist spot deleted successfully!');
            loadTouristSpots();
          } else {
            throw new Error(data.message || 'Failed to delete tourist spot');
          }
        } catch (error) {
          console.error('Delete error:', error);
          alert('Error: ' + error.message);
        }
      }
    }

    let currentSpotId = null;

    function openStatusModal(spotId, currentStatus) {
      currentSpotId = spotId;
      const modal = document.getElementById('statusModal');
      const statusText = document.getElementById('currentStatusText');
      statusText.textContent = currentStatus || 'active';
      statusText.className = 'font-medium ' + 
        (currentStatus === 'inactive' ? 'text-red-600' : 'text-green-600');
      modal.classList.remove('hidden');
    }

    function closeStatusModal() {
      document.getElementById('statusModal').classList.add('hidden');
      currentSpotId = null;
    }

    async function updateSpotStatus(newStatus) {
      if (!currentSpotId) return;

      try {
  const response = await fetch('../../../tripko-backend/api/tourist_spot/update_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            spot_id: currentSpotId,
            status: newStatus
          })
        });
        
        const data = await response.json();
        if (data.success) {
          alert(`Tourist spot status updated to ${newStatus}`);
          closeStatusModal();
          // Refresh both views
          loadTouristSpots();
          if (!document.getElementById('tableView').classList.contains('hidden')) {
            loadTableView();
          }
        } else {
          throw new Error(data.message || 'Failed to update status');
        }
      } catch (error) {
        console.error('Error updating status:', error);
        alert('Failed to update tourist spot status: ' + error.message);
      }
    }

    // Update the existing toggleSpotStatus function to use the modal
    function toggleSpotStatus(spotId, currentStatus) {
      openStatusModal(spotId, currentStatus);
    }

    // Toggle view function
    function toggleView() {
      const gridView = document.getElementById('gridView');
      const tableView = document.getElementById('tableView');
      const viewToggleBtn = document.getElementById('viewToggleBtn');
      
      const isGridView = !gridView.classList.contains('hidden');
      gridView.classList.toggle('hidden', isGridView);
      tableView.classList.toggle('hidden', !isGridView);
      viewToggleBtn.innerHTML = isGridView ? '<i class="fas fa-th mr-2"></i>Grid View' : '<i class="fas fa-table mr-2"></i>Table View';
      
      if (!isGridView) {
        loadTableView(); // Load table data
      } else {
        loadTouristSpots(); // Load grid data
      }
    }

    
    // Load table view data with pagination
    async function loadTableView() {
      try {
        const tableBody = document.getElementById('spotTableBody');
        tableBody.innerHTML = `
          <tr>
            <td colspan="7" class="text-center py-8">
              <div class="flex justify-center items-center">
                <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-[#255D8A]"></div>
              </div>
            </td>
          </tr>
        `;

        const selectedCategory = document.getElementById('categoryFilter').value;
        const selectedMunicipality = document.getElementById('municipalityFilter').value;

  let url = `../../../tripko-backend/api/tourist_spot/read.php?page=${currentTablePage}&view=table`;
        if (selectedCategory) url += `&category=${encodeURIComponent(selectedCategory)}`;
        if (selectedMunicipality) url += `&municipality=${encodeURIComponent(selectedMunicipality)}`;

        const response = await fetch(url);
        const data = await response.json();
        
        tableBody.innerHTML = '';
        
        if (data && data.records && Array.isArray(data.records)) {
          if (data.records.length === 0) {
            tableBody.innerHTML = `
              <tr>
                <td colspan="7" class="text-center py-8">
                  <div class="flex flex-col items-center justify-center text-gray-500">
                    <i class="fas fa-filter text-4xl mb-3"></i>
                    <p class="text-[17px] font-medium">No tourist spots found</p>
                  </div>
                </td>
              </tr>
            `;
          } else {
            data.records.forEach(spot => {
              const statusClass = spot.status === 'inactive' ? 'bg-red-50' : '';
              const statusBadgeClass = spot.status === 'inactive' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
              const statusButtonClass = spot.status === 'inactive' ? 'bg-red-600' : 'bg-green-600';

              tableBody.innerHTML += `
                <tr class="hover:bg-gray-100 transition-colors ${statusClass}">
                  <td class="border border-gray-300 px-4 py-2">${spot.name}</td>
                  <td class="border border-gray-300 px-4 py-2">${spot.category || 'N/A'}</td>
                  <td class="border border-gray-300 px-4 py-2">${spot.town_name || 'N/A'}</td>
                  <td class="border border-gray-300 px-4 py-2">${spot.contact_info || 'N/A'}</td>
                  <td class="border border-gray-300 px-4 py-2 line-clamp-2">${spot.description}</td>
                  <td class="border border-gray-300 px-4 py-2 text-center">
                    <span class="inline-block px-2 py-1 text-xs rounded-full ${statusBadgeClass}">
                      ${spot.status || 'active'}
                    </span>
                  </td>
                  <td class="border border-gray-300 px-4 py-2 text-center">
                    <div class="flex justify-center gap-2">
                      <button onclick='editSpot(${JSON.stringify(spot).replace(/'/g, "&#39;")})'
                              class="bg-[#255d8a] text-white px-3 py-1 rounded text-sm hover:bg-[#1e4d70]">
                        Edit
                      </button>
                      <button onclick="deleteSpot(${spot.spot_id}, '${spot.name}')"
                              class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                        Delete
                      </button>
                      <button onclick="openStatusModal(${spot.spot_id}, '${spot.status || 'active'}')"
                              class="${statusButtonClass} text-white px-3 py-1 rounded text-sm hover:bg-opacity-90">
                        Status
                      </button>
                    </div>
                  </td>
                </tr>
              `;
            });
          }

          // Render pagination
          renderPagination('tablePagination', data.pagination.page, data.pagination.pages);

        } else {
          tableBody.innerHTML = `
            <tr>
              <td colspan="7" class="text-center py-8">
                <div class="flex flex-col items-center justify-center text-red-500">
                  <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                  <p>No tourist spots found</p>
                </div>
              </td>
            </tr>
          `;
        }
      } catch (error) {
        console.error('Fetch Error:', error);
        tableBody.innerHTML = `
          <tr>
            <td colspan="7" class="text-center py-8">
              <div class="flex flex-col items-center justify-center text-red-500">
                <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                <p>Failed to load tourist spots</p>
                <p class="text-sm mt-2">Please try again later</p>
              </div>
            </td>
          </tr>
        `;
      }
    }

    // Filter tourist spots by category and municipality
    function filterTouristSpots() {
      const selectedMunicipality = document.getElementById('municipalityFilter').value;
      const selectedCategory = document.getElementById('categoryFilter').value;
      
      // Reset pagination when filters change
      currentGridPage = 1;
      currentTablePage = 1;
      
      // Store filters in session storage
      sessionStorage.setItem('selectedCategory', selectedCategory);
      sessionStorage.setItem('selectedMunicipality', selectedMunicipality);
      
      // Reload spots with new filters
      loadTouristSpots();
      
      // If we're in table view, reload that too
      if (!document.getElementById('tableView').classList.contains('hidden')) {
        loadTableView();
      }
    }
    
    // Enforce view-only behavior in the UI for Super Admin accounts.
    // This hides add/edit/delete controls and replaces write functions with no-op alerts.
    (function enforceViewOnlyUI() {
      const VIEW_ONLY_MODE = true; // keep true to disable create/edit/delete/status in UI
      if (!VIEW_ONLY_MODE) return;

      // Hide the Add new spot button if present (replace with text)
      document.addEventListener('DOMContentLoaded', () => {
        try {
          const btns = Array.from(document.querySelectorAll('button'));
          const addBtn = btns.find(b => b.getAttribute('onclick') && b.getAttribute('onclick').includes('openModal'));
          if (addBtn) {
            const span = document.createElement('span');
            span.className = 'text-sm text-gray-500';
            span.textContent = 'View-only mode';
            addBtn.parentNode.replaceChild(span, addBtn);
          }

          // Replace runtime-generated action buttons in table rows after table is loaded
          const observeTable = new MutationObserver(() => {
            document.querySelectorAll('#spotTableBody td').forEach(td => {
              if (td.innerHTML.includes('Edit') && td.innerHTML.includes('Delete')) {
                td.innerHTML = '<div class="flex justify-center gap-2"><span class="text-sm text-gray-600">—</span></div>';
              }
            });
          });
          const table = document.getElementById('spotTableBody');
          if (table) observeTable.observe(table, { childList: true, subtree: true });
        } catch (e) {
          console.error('enforceViewOnlyUI error', e);
        }
      });

      // Replace write-related functions with friendly no-ops
      window.openModal = function() { alert('View-only account: action disabled.'); };
      window.closeModal = function() { /* no-op */ };
      window.editSpot = function() { alert('View-only account: action disabled.'); };
      window.deleteSpot = function() { alert('View-only account: action disabled.'); };
      window.openStatusModal = function() { alert('View-only account: action disabled.'); };
      window.closeStatusModal = function() { /* no-op */ };
      window.updateSpotStatus = function() { alert('View-only account: action disabled.'); };

      // Prevent the form from submitting
      const origAddListener = document.addEventListener;
      document.addEventListener('DOMContentLoaded', () => {
        const spotForm = document.getElementById('spotForm');
        if (spotForm) {
          spotForm.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('View-only account: action disabled.');
          });
        }
      });
    })();
  </script>
</body>
</html>