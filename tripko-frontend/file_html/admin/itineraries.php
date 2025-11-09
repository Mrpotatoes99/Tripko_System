<?php
session_start();
require_once('../../../tripko-backend/config/check_session.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TripKo Pangasinan - Itineraries</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
  <link rel="stylesheet" href="../../file_css/dashboard.css" />
  <link rel="stylesheet" href="../file_css/tourist_spot.css" />
  <style>
    
    body {
        font-family: 'poppins';
        font-size: 17px;
    }

    .nav        // Table view
        tableBody.innerHTML += `          <tr class="hover:bg-gray-100 transition-colors">
            <td class="border border-gray-300 px-4 py-2">${itinerary.name}</td>
            <td class="border border-gray-300 px-4 py-2 line-clamp-2">${itinerary.description}</td>
            <td class="border border-gray-300 px-4 py-2">${itinerary.town_name || 'N/A'}</td>
            <td class="border border-gray-300 px-4 py-2">${environmental_fee}</td>   a,
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
        // Transport dropdown
        const transportDropdown = document.getElementById('transportDropdown');
        const transportDropdownIcon = document.getElementById('transportDropdownIcon');

        // Close dropdown
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#transportDropdown') && !e.target.closest('[onclick*="toggleTransportDropdown"]')) {
                transportDropdown?.classList.add('hidden');
                if (transportDropdownIcon) {
                    transportDropdownIcon.style.transform = 'rotate(0deg)';
                }
            }
        });
    });
  </script>
</head>
<body class="bg-white text-gray-900">
  <div class="flex min-h-screen">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main content -->
    <main class="flex-1 bg-[#F3F1E7] p-6">
      <header class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3 text-gray-900 font-normal text-base">
          <button aria-label="Menu" class="focus:outline-none">
            <i class="fas fa-bars text-lg"></i>
          </button>
          <span>Itineraries</span>
        </div>
        <div class="flex items-center gap-4">
          <div>
            <input type="search" placeholder="Search" class="w-48 md:w-64 rounded-full border border-gray-400 bg-[#F3F1E7] py-1.5 px-4 text-gray-600 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#255D8A]" />
          </div>
          <button aria-label="Notifications" class="text-black text-xl focus:outline-none">
            <i class="fas fa-bell"></i>
          </button>
        </div>
      </header>

      <div class="flex justify-between items-center mb-6">
        <h2 class="font-semibold text-xl">Itineraries</h2>
        <div class="flex gap-3">
          <span class="text-sm text-gray-500">View-only mode</span>
          <button onclick="toggleView()" id="viewToggleBtn" class="bg-[#255D8A] text-white px-4 py-2 rounded-md hover:bg-[#1e4d70] transition-colors">
            <i class="fas fa-table"></i> Table View
          </button>
          <div class="relative">
            <select id="municipalityFilter" onchange="filterItineraries()" class="bg-[#255D8A] text-white px-4 py-2 rounded-md hover:bg-[#1e4d70] transition-colors cursor-pointer">
              <option value="" class="bg-white">All Municipalities</option>
            </select>
          </div>
        </div>
      </div>      <!-- Itineraries grid -->
      <div id="gridView" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Itinerary cards will be dynamically added here -->
      </div>      <!-- Grid Pagination -->
      <div class="flex justify-center items-center mt-6 mb-6 gap-2" id="gridPagination">
        <!-- Grid pagination controls will be added here -->
      </div>      <!-- Itineraries table -->
      <div id="tableView" class="hidden">
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <table class="w-full border-collapse">
            <thead class="bg-gray-50">
              <tr>
                <th class="border border-gray-300 px-4 py-2 text-left">Title</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Description</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Municipality</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Environmental Fee</th>
                <th class="border border-gray-300 px-4 py-2 text-center">Status</th>
                <th class="border border-gray-300 px-4 py-2 text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="itineraryTableBody">
              <!-- Itinerary rows will be dynamically added here -->            </tbody>
          </table>
        </div>
        <!-- Table Pagination -->
        <div class="flex justify-center items-center mt-6 mb-6 gap-2" id="tablePagination">
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

  thead {
    /* no background */
  }

  th,
  td {
    border: 1px solid #e2e8f0;
    padding: 12px;
    text-align: left;
    font-size: 14px;
    height: 60px;
    word-wrap: break-word;
    vertical-align: middle;
  }

  th {
   font-weight: 500; /* medium */
  }

  /* Colored header cells */
  th:nth-child(1) {
    background-color: #BBDEFB; 
    color: #0d47a1;
  }

  th:nth-child(2) {
    background-color: #BBDEFB; 
    color: #0d47a1;
  }

  th:nth-child(3) {
    background-color: #BBDEFB; 
    color: #0d47a1;
  }

  th:nth-child(4) {
    background-color: #BBDEFB; 
    color: #0d47a1;
  }

  th:nth-child(5) {
    background-color: #BBDEFB; 
    color: #0d47a1;
    text-align:justify;
  }

  th:nth-child(6) {
    background-color: #BBDEFB; 
    color: #0d47a1;
    text-align:justify;
  }

  /* Alternating row colors */
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
  </div>  <!-- Add New Itinerary Modal -->  <div id="addItineraryModal" class="fixed inset-0 hidden z-50">
    <div class="bg-black bg-opacity-50 backdrop-blur-sm absolute inset-0"></div>
    <div class="fixed inset-0 flex items-center justify-center">
       <div class="form-container bg-white relative z-10 p-6 rounded-lg shadow-lg w-full max-w-4xl mx-4">
        <button type="button" class="absolute right-4 top-4 text-gray-500 hover:text-gray-700" onclick="closeModal()">
          <i class="fas fa-times text-xl"></i>
        </button>

        <h2 class="text-xl font-bold mb-4 text-[#255D8A]">Add New Itinerary</h2>
       
        <form id="itineraryForm" enctype="multipart/form-data">          <!-- Municipality and Itinerary Name -->
          <div class="form-row grid grid-cols-2 gap-4 mb-4">            <div class="form-group">
              <label for="town_id" class="block text-[15px] font-medium text-gray-700 mb-1">
                Municipality <span class="text-red-500">*</span>
              </label>
              <select id="town_id" name="town_id" required 
                      class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]">
                <option value="" selected disabled>Select municipality</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="name" class="block text-[15px] font-medium text-gray-700 mb-1">
                Itinerary Name <span class="text-red-500">*</span>
              </label>
              <input type="text" id="name" name="name" required 
                     class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]">
            </div>
          </div>

          <!-- Description -->
          <div class="form-group mb-4">
            <label for="description" class="block text-[15px] font-medium text-gray-700 mb-1">
              Description <span class="text-red-500">*</span>
            </label>
            <textarea id="description" name="description" rows="4" required 
                      class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]"></textarea>
          </div>

          <!-- Environmental Fee -->
          <div class="form-row grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label for="environmental_fee" class="block text-[15px] font-medium text-gray-700 mb-1">
                Environmental Fee <span class="text-gray-500">(Optional)</span>
              </label>
              <input type="text" id="environmental_fee" name="environmental_fee"
                     class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]"
                     placeholder="Enter amount">
            </div>
          </div>          <!-- Image Upload -->
          <div class="form-group mb-4">
            <div class="upload-area bg-white border border-gray-300 rounded-lg p-3">
              <div class="flex flex-col gap-2">
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
    <script>
    // Pagination variables
    let currentPage = 1;    // Items per page for different views
    const GRID_ITEMS_PER_PAGE = 6; // 6 cards in grid view (2x3 grid)
    const TABLE_ITEMS_PER_PAGE = 10; // 10 items per page in table view
    
    // Function to render pagination controls
    function renderPagination(containerId, currentPage, totalPages, onPageChange) {
      const container = document.getElementById(containerId);
      if (!container) return;
      
      let html = '';
      
      // Previous button
      html += `
        <button 
          onclick="handlePageChange('${containerId}', ${currentPage - 1})"
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
              onclick="handlePageChange('${containerId}', ${i})"
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
          onclick="handlePageChange('${containerId}', ${currentPage + 1})"
          class="px-3 py-1 rounded-md text-sm ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-[#255D8A] text-white hover:bg-[#1e4d70]'}"
          ${currentPage === totalPages ? 'disabled' : ''}>
          <i class="fas fa-chevron-right"></i>
        </button>
      `;

      container.innerHTML = html;
    }

    // Handle page changes
    function handlePageChange(containerId, newPage) {
      currentPage = newPage;
      loadItineraries();
    }

    // Modal open/close
function openModal() {
  document.getElementById('addItineraryModal').classList.remove('hidden');
}
function closeModal() {
  document.getElementById('addItineraryModal').classList.add('hidden');
}
function toggleTransportDropdown(event) {
  event.preventDefault();
  const dropdown = document.getElementById('transportDropdown');
  const icon = document.getElementById('transportDropdownIcon');
  dropdown.classList.toggle('hidden');
  icon.style.transform = dropdown.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Toggle between grid and table view
function toggleView() {
  const gridView = document.getElementById('gridView');
  const tableView = document.getElementById('tableView');
  const viewToggleBtn = document.getElementById('viewToggleBtn');
  const gridPagination = document.getElementById('gridPagination');

  if (gridView.classList.contains('hidden')) {
    // Switch to grid view
    gridView.classList.remove('hidden');
    tableView.classList.add('hidden');
    gridPagination.classList.remove('hidden');
    viewToggleBtn.innerHTML = '<i class="fas fa-table"></i> Table View';
  } else {
    // Switch to table view
    gridView.classList.add('hidden');
    tableView.classList.remove('hidden');
    gridPagination.classList.add('hidden');
    viewToggleBtn.innerHTML = '<i class="fas fa-th"></i> Grid View';
  }
  
  // Reset to first page and reload items when switching views
  currentPage = 1;
  loadItineraries();
}

// Form submit handler
document.addEventListener('DOMContentLoaded', () => {
  loadItineraries();
  loadDestinations();
  const form = document.querySelector('#addItineraryModal form');
  
  // Check if form exists before continuing
  if (!form) {
    console.error('Form not found');
    return;
  }
  
  const fileInput = form.querySelector('#fileInput');
  const fileNamesDisplay = form.querySelector('.file-names');
  const chooseFilesBtn = form.querySelector('button[type="button"]:not([onclick])');
  
  if (chooseFilesBtn && fileInput) {
    chooseFilesBtn.addEventListener('click', (e) => {
      e.preventDefault();
      fileInput.click();
    });
  }
  
  fileInput?.addEventListener('change', function() {
    const files = Array.from(this.files || []);
    fileNamesDisplay.textContent = files.length > 0 ? files.map(f => f.name).join(', ') : 'No file chosen';
  });
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(form);
    const files = fileInput.files;
    for(let i = 0; i < files.length; i++) {
      formData.append('images[]', files[i]);
    }

    const isEdit = formData.has('itinerary_id');
    const url = isEdit ? 
  '../../../tripko-backend/api/itineraries/update.php' : 
  '../../../tripko-backend/api/itineraries/create.php';

    try {
      const response = await fetch(url, {
        method: 'POST',
        body: formData
      });
      const data = await response.json();
      if(data.success) {
        alert(isEdit ? 'Itinerary updated!' : 'Itinerary added!');
        closeModal();
        form.reset();
        
        // Clear file input and preview display
        fileInput.value = '';
        fileNamesDisplay.textContent = 'No file chosen';
        loadItineraries();
      } else {
        alert(`Failed to ${isEdit ? 'update' : 'add'} itinerary: ${data.message || 'Unknown error'}`);
      }
    } catch (error) {
      alert('Error: ' + error.message);
    }
  });
});

// Load and display itineraries
async function loadItineraries() {
  try {
    const gridContainer = document.querySelector('#gridView');
    const tableBody = document.getElementById('itineraryTableBody');
    
    // Show loading state
    gridContainer.innerHTML = `
      <div class="col-span-full text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-[#255D8A] mb-4"></div>
        <p class="text-gray-600">Loading itineraries...</p>
      </div>
    `;
    
  const response = await fetch('../../../tripko-backend/api/itineraries/read.php');
    const data = await response.json();
    
    gridContainer.innerHTML = '';
    tableBody.innerHTML = '';

    if (data && data.records && Array.isArray(data.records)) {      // Filter by municipality if selected
      const selectedMunicipality = document.getElementById('municipalityFilter')?.value;      let filteredItineraries = data.records;
      
      if (selectedMunicipality && selectedMunicipality !== '') {
        filteredItineraries = data.records.filter(itinerary => {
          // Handle null or undefined town_id
          if (!itinerary.town_id) return false;
          return String(itinerary.town_id) === String(selectedMunicipality);
        });
      }

      // Determine current view and items per page
      const isTableView = document.getElementById('gridView').classList.contains('hidden');
      const itemsPerPage = isTableView ? TABLE_ITEMS_PER_PAGE : GRID_ITEMS_PER_PAGE;
      
      // Calculate pagination
      const totalPages = Math.ceil(filteredItineraries.length / itemsPerPage);
      const startIndex = (currentPage - 1) * itemsPerPage;
      const endIndex = startIndex + itemsPerPage;
      const currentItems = filteredItineraries.slice(startIndex, endIndex);

      // Reset to first page if current page is out of bounds
      if (currentPage > totalPages) {
        currentPage = 1;
      }      // Render pagination for both views
      renderPagination('gridPagination', currentPage, totalPages);
      renderPagination('tablePagination', currentPage, totalPages);

      if (filteredItineraries.length === 0) {
        const noDataMessage = selectedMunicipality ? 
          'No itineraries found for the selected municipality' : 
          'No itineraries found';

        gridContainer.innerHTML = `
          <div class="col-span-full text-center py-8 text-gray-500">
            <i class="fas fa-filter text-4xl mb-3 block"></i>
            <p class="text-[17px] font-medium">${noDataMessage}</p>
          </div>
        `;
        tableBody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center py-8 text-gray-500">
              <i class="fas fa-filter text-4xl mb-3 block"></i>
              <p>${noDataMessage}</p>
            </td>
          </tr>
        `;
        return;
      }
      
      // Display filtered itineraries
      currentItems.forEach(itinerary => {
        // Format environmental fee once for both views
        const environmental_fee = itinerary.environmental_fee 
          ? `₱${parseFloat(itinerary.environmental_fee).toFixed(2)}` 
          : 'No fee';

        // Grid view
        gridContainer.innerHTML += `          <div class="rounded-lg overflow-hidden border border-gray-200 shadow-md bg-white flex flex-col h-full transition-transform hover:scale-105 hover:shadow-lg">
            <div class="relative w-full h-48 bg-gray-100 flex items-center justify-center">
              <img src="${getImageUrl(itinerary.image_path)}"
                   alt="${itinerary.name || 'Itinerary'}"
                   class="w-full h-full object-cover transition-all duration-300" />
            </div>
            <div class="flex-1 flex flex-col p-4">
              <div class="mb-2">
              <span class="text-sm font-medium text-gray-600">
                  <i class="fas fa-map-marker-alt mr-1"></i>${itinerary.town_name || 'N/A'}
                </span>
              </div>
              <h3 class="text-lg font-bold mb-1 text-[#255D8A]">${itinerary.name}</h3>
              <p class="text-sm text-gray-700 mb-2 line-clamp-3">${itinerary.description}</p>
              <p class="text-xs text-gray-500 mt-auto flex items-center">
                <i class="fas fa-leaf mr-1"></i>${environmental_fee}
              </p>
            </div>
          </div>
        `;

        // Table view
        tableBody.innerHTML += `          <tr class="hover:bg-gray-100 transition-colors">
            <td class="border border-gray-300 px-4 py-2">${itinerary.name}</td>
            <td class="border border-gray-300 px-4 py-2 line-clamp-2">${itinerary.description}</td>
            <td class="border border-gray-300 px-4 py-2">${itinerary.town_name || 'N/A'}</td>
            <td class="border border-gray-300 px-4 py-2">${environmental_fee}</td>  
            <td class="border border-gray-300 px-4 py-2 text-center">
              <span class="inline-block px-2 py-1 text-xs rounded-full 
                          ${itinerary.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                ${itinerary.status || 'Active'}
              </span>
            </td>
            <td class="border border-gray-300 px-4 py-2 text-center">
              <div class="flex justify-center gap-2">
                <button onclick="editItinerary(${itinerary.itinerary_id})" 
                        class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                  Edit
                </button>
                <button onclick="toggleItineraryStatus(${itinerary.itinerary_id}, '${itinerary.status || 'active'}')" 
                        class="${itinerary.status === 'inactive' ? 'bg-green-600' : 'bg-red-600'} text-white px-3 py-1 rounded text-sm hover:${itinerary.status === 'inactive' ? 'bg-green-700' : 'bg-red-700'}">
                  ${itinerary.status === 'inactive' ? 'Activate' : 'Deactivate'}
                </button>
              </div>
            </td>
          </tr>
        `;
      });
    } else {
      gridContainer.innerHTML = `
        <div class="col-span-full text-center py-8 text-gray-500">
          <i class="fas fa-inbox text-4xl mb-3 block"></i>
          <p>No itineraries found</p>
        </div>
      `;
      tableBody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center py-4 text-gray-500">
            <i class="fas fa-inbox text-4xl mb-3 block"></i>
            No itineraries found
          </td>
        </tr>
      `;
    }
  } catch (error) {
    console.error('Error loading itineraries:', error);
    document.querySelector('#gridView').innerHTML = `
      <div class="col-span-full text-center py-8 text-red-500">
        <i class="fas fa-exclamation-circle text-4xl mb-3 block"></i>
        <p>Failed to load itineraries. Please try again later.</p>
        <p class="text-sm mt-2">Error details: ${error.message}</p>
      </div>
    `;
    document.getElementById('itineraryTableBody').innerHTML = `
      <tr>
        <td colspan="6" class="text-center py-4 text-red-500">
          <i class="fas fa-exclamation-circle text-4xl mb-3 block"></i>
          Failed to load itineraries. Please try again later.
        </td>
      </tr>
    `;
  }
}

// Helper for image URL
function getImageUrl(imagePath) {
  if (!imagePath || imagePath === 'placeholder.jpg') {
    return 'https://placehold.co/400x300?text=No+Image';
  }
  return `/TripKo-System/uploads/${imagePath}`;
}

// Load destinations for the select dropdown
async function loadDestinations() {
  try {
  const response = await fetch('../../../tripko-backend/api/towns/read.php');
    const data = await response.json();
    const select = document.getElementById('town_id');
    select.innerHTML = '<option value="" selected disabled>Select municipality</option>';
    if (data && data.records && Array.isArray(data.records)) {
      data.records.forEach(town => {
        const option = document.createElement('option');
        option.value = town.town_id;
        option.textContent = town.name;
        select.appendChild(option);
      });
    }
  } catch (error) {
    const select = document.getElementById('town_id');
    if (select) {
      select.innerHTML = '<option value="" disabled>Error loading destinations</option>';
    }
  }
}

// Edit itinerary function
async function editItinerary(id) {
  try {
  const response = await fetch(`../../../tripko-backend/api/itineraries/read_single.php?id=${id}`);
    const data = await response.json();
    if (data.success && data.itinerary) {
      const itinerary = data.itinerary;
      // Populate form fields      document.getElementById('town_id').value = itinerary.town_id;
      document.getElementById('name').value = itinerary.name;
      document.getElementById('description').value = itinerary.description;
      document.getElementById('environmental_fee').value = itinerary.environmental_fee || '';
      
      // Add itinerary ID to form for update
      let idInput = document.getElementById('itineraryId');
      if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.id = 'itineraryId';
        idInput.name = 'itinerary_id';
        document.getElementById('itineraryForm').appendChild(idInput);
      }
      idInput.value = id;
      
      openModal();
    } else {
      throw new Error(data.message || 'Failed to load itinerary details');
    }
  } catch (error) {
    console.error('Error loading itinerary:', error);
    alert('Failed to load itinerary details: ' + error.message);
  }
}

// Toggle itinerary status
async function toggleItineraryStatus(itineraryId, currentStatus) {
  try {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
  const response = await fetch('../../../tripko-backend/api/itineraries/update_status.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        itinerary_id: itineraryId,
        status: newStatus
      })
    });

    const data = await response.json();
    if (data.success) {
      loadItineraries(); // Reload the list to show updated status
    } else {
      throw new Error(data.message || 'Failed to update status');
    }
  } catch (error) {
    console.error('Error updating status:', error);
    alert('Failed to update itinerary status: ' + error.message);
  }
}

// Form submission handler
document.getElementById('itineraryForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  const isEdit = formData.get('itinerary_id') ? true : false;
  
  try {
    const endpoint = isEdit ? 
  '../../../tripko-backend/api/itineraries/update.php' : 
  '../../../tripko-backend/api/itineraries/create.php';

    const response = await fetch(endpoint, {
      method: 'POST',
      body: formData
    });

    const data = await response.json();
    if (data.success) {
      alert(isEdit ? 'Itinerary updated successfully!' : 'Itinerary created successfully!');
      closeModal();
      loadItineraries();
      this.reset();
    } else {
      throw new Error(data.message || `Failed to ${isEdit ? 'update' : 'create'} itinerary`);
    }
  } catch (error) {
    console.error('Save error:', error);
    alert('Error: ' + error.message);
  }
});

// Load municipalities for the filter dropdown
async function loadMunicipalityFilter() {
  const municipalityFilter = document.getElementById('municipalityFilter');
  if (!municipalityFilter) return;

  try {
    // Show loading state
    municipalityFilter.innerHTML = '<option value="">Loading municipalities...</option>';
    municipalityFilter.disabled = true;

  const response = await fetch('../../../tripko-backend/api/towns/read.php');
    const data = await response.json();
    
    municipalityFilter.innerHTML = '<option value="">All Municipalities</option>';
    
    if (data && data.records && Array.isArray(data.records)) {
      // Sort municipalities alphabetically
      const sortedTowns = [...data.records].sort((a, b) => 
        (a.name || '').localeCompare(b.name || '')
      );

      sortedTowns.forEach(town => {
        if (town.town_id && town.name) {
          const option = document.createElement('option');
          option.value = town.town_id;
          option.textContent = town.name;
          municipalityFilter.appendChild(option);
        }
      });
    } else {
      throw new Error('Invalid data format received from server');
    }
  } catch (error) {
    console.error('Failed to load municipalities:', error);
    municipalityFilter.innerHTML = '<option value="">Error loading municipalities</option>';
  } finally {
    municipalityFilter.disabled = false;
  }
}

// Filter function with loading state
function filterItineraries() {
  // Reset pagination when filter changes
  currentPage = 1;
  loadItineraries();
}

// Initialize page with components
document.addEventListener('DOMContentLoaded', async () => {
  try {
    // First load municipalities
    await loadMunicipalityFilter();
    
    // Then load destinations for add/edit form
    await loadDestinations();
    
    // Finally load itineraries
    await loadItineraries();

  } catch (error) {
    console.error('Error during initialization:', error);
  }
  
  // Setup form file input handlers
  const form = document.querySelector('#itineraryForm');
  const fileInput = form?.querySelector('#fileInput');
  const fileNamesDisplay = form?.querySelector('.file-names');
  const chooseFilesBtn = form?.querySelector('button[type="button"]:not([onclick])');
  
  if (chooseFilesBtn && fileInput) {
    chooseFilesBtn.addEventListener('click', (e) => {
      e.preventDefault();
      fileInput.click();
    });
  }
  
  fileInput?.addEventListener('change', function() {
    const files = Array.from(this.files || []);
    if (fileNamesDisplay) {
      fileNamesDisplay.textContent = files.length > 0 ? files.map(f => f.name).join(', ') : 'No file chosen';
    }
  });
});
  </script>
  <script>
    // UI-only enforcement: hide write controls and stub write functions for view-only Super Admin
    (function() {
      const VIEW_ONLY = true;
      if (!VIEW_ONLY) return;

      document.addEventListener('DOMContentLoaded', () => {
        // Replace Create button
        const createBtn = Array.from(document.querySelectorAll('button')).find(b => b.getAttribute('onclick') && b.getAttribute('onclick').includes('openModal'));
        if (createBtn) {
          const span = document.createElement('span');
          span.className = 'text-sm text-gray-500';
          span.textContent = 'View-only mode';
          createBtn.parentNode.replaceChild(span, createBtn);
        }

        // Observe table body and replace action buttons
        const tableBody = document.getElementById('itineraryTableBody');
        if (tableBody) {
          const obs = new MutationObserver(() => {
            tableBody.querySelectorAll('td').forEach(td => {
              if (td.innerHTML.includes('Edit') || td.innerHTML.includes('Activate') || td.innerHTML.includes('Deactivate')) {
                td.innerHTML = '<div class="flex justify-center gap-2"><span class="text-sm text-gray-600">—</span></div>';
              }
            });
          });
          obs.observe(tableBody, { childList: true, subtree: true });
        }

        // Stub write functions
        window.openModal = () => alert('View-only account: action disabled.');
        window.closeModal = () => {};
        window.editItinerary = () => alert('View-only account: action disabled.');
        window.toggleItineraryStatus = () => alert('View-only account: action disabled.');
        document.getElementById('itineraryForm')?.addEventListener('submit', (e) => { e.preventDefault(); alert('View-only account: action disabled.'); });
      });
    })();
  </script>
</body>
</html>