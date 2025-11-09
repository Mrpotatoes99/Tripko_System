<?php
require_once('../../../tripko-backend/config/check_session.php');
checkAdminSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TripKo Pangasinan - Festivals</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
  <link rel="stylesheet" href="../css/dashboard.css" />
  <link rel="stylesheet" href="../file_css/tourist_spot.css" />
  <style>
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

    canvas#transportChart {
      width: 100% !important;
      height: 100% !important;
      position: absolute !important;
      top: 0;
      left: 0;
    }
  </style>
</head>
<body class="bg-white text-gray-900">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main content -->
    <main class="flex-1 bg-[#F3F1E7] p-6">
      <header class="mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3 text-gray-900 font-normal text-base">
            <button aria-label="Menu" class="focus:outline-none">
              <i class="fas fa-bars text-lg"></i>
            </button>
            <span>Festivals</span>
          </div>
          <div class="flex items-center gap-4">
            <div>
              <input type="search" placeholder="Search" class="w-48 md:w-64 rounded-full border border-gray-400 bg-[#F3F1E7] py-1.5 px-4 text-gray-600 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#255D8A]" />
            </div>
            <button aria-label="Notifications" class="text-black text-xl focus:outline-none">
              <i class="fas fa-bell"></i>
            </button>
          </div>
        </div>
        <div class="flex justify-end mt-4">
          <div class="flex gap-3">
            <span class="text-sm text-gray-500">View-only mode</span>
            <button onclick="toggleView()" id="viewToggleBtn" class="bg-[#255D8A] text-white px-4 py-2 rounded-md hover:bg-[#1e4d70] transition-colors">
              <i class="fas fa-table"></i> Table View
            </button>
          </div>
        </div>
      </header>

  <!-- Festivals grid for JS rendering -->
  <div id="festivalsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
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

  /* Set specific widths for 6 columns */
  th:nth-child(1), td:nth-child(1) { width: 18%; }  /* Name */
  th:nth-child(2), td:nth-child(2) { width: 30%; }  /* Description */
  th:nth-child(3), td:nth-child(3) { width: 15%; }  /* Date */
  th:nth-child(4), td:nth-child(4) { width: 18%; }  /* Municipality */
  th:nth-child(5), td:nth-child(5) { width: 10%; text-align: justify; ; }  /* Status */
  th:nth-child(6), td:nth-child(6) { width: 20%; text-align: justify;  }   /* Actions */

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

  <!-- Add New Festival Modal -->
  <div id="addFestivalModal" class="fixed inset-0 hidden z-50">
    <div class="bg-black bg-opacity-50 absolute inset-0"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
      <div class="form-container bg-white relative z-10 p-8 rounded shadow-lg w-full max-w-2xl">
        <button type="button" class="absolute right-4 top-4 text-gray-500 hover:text-gray-700" onclick="closeModal()">
          <i class="fas fa-times text-xl"></i>
        </button>

        <h2 class="text-xl font-bold mb-4 text-[#255D8A]">Add Festival</h2>
       
        <!-- Replace the existing festival form with this updated version -->
<form id="festivalForm" enctype="multipart/form-data">
  <!-- Festival Name and Municipality -->
  <div class="form-row grid grid-cols-2 gap-4 mb-4">
    <div class="form-group">
      <label for="festival-name" class="block text-[15px] font-medium text-gray-700 mb-1">
        Festival Name <span class="text-red-500">*</span>
      </label>
      <input type="text" id="festival-name" name="name" required 
             class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]" />
    </div>
    
    <div class="form-group">
      <label for="municipality" class="block text-[15px] font-medium text-gray-700 mb-2">
        Municipality <span class="text-red-500">*</span>
      </label>
      <select id="municipality" name="municipality" required 
              class="w-full border rounded-md px-3 py-2 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]">
        <option value="" disabled selected>Select municipality</option>
      </select>
    </div>
  </div>

  <!-- Description -->
  <div class="form-group mb-4">
    <label for="festival-description" class="block text-[15px] font-medium text-gray-700 mb-1">
      Description <span class="text-red-500">*</span>
    </label>
    <textarea id="festival-description" name="description" rows="4" required 
              class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]"></textarea>
  </div>

  <!-- Date -->    <div class="form-group mb-4">
      <label for="festival-date" class="block text-[15px] font-medium text-gray-700 mb-1">
        Date <span class="text-red-500">*</span>
      </label>
      <input type="date" id="festival-date" name="date" required 
             class="w-full border rounded-md px-2 py-1.5 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#255D8A]" />
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
    <button type="button" 
            class="px-3 py-1.5 rounded-md bg-gray-200 hover:bg-gray-300 transition-colors text-[15px]" 
            onclick="closeModal()">Cancel</button>
    <button type="submit" 
            class="px-3 py-1.5 rounded-md bg-[#255D8A] text-white hover:bg-[#1e4d70] transition-colors text-[15px]">
      Save
    </button>
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
        <h3 class="text-xl font-bold mb-4">Change Status</h3>
        <p class="mb-4">Current status: <span id="currentStatusText" class="font-semibold"></span></p>
        <div class="space-y-3">
          <button onclick="updateFestivalStatus('active')" 
                  class="w-full py-2 px-4 rounded bg-green-600 text-white hover:bg-green-700 transition-colors">
            Set Active
          </button>
          <button onclick="updateFestivalStatus('inactive')" 
                  class="w-full py-2 px-4 rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
            Set Inactive
          </button>
          <button onclick="closeStatusModal()" 
                  class="w-full py-2 px-4 rounded bg-gray-300 hover:bg-gray-400 transition-colors">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Pagination constants
    const itemsPerGridPage = 6;  // 6 cards per page in grid view
    const itemsPerTablePage = 10; // 10 rows per page in table view
    let currentGridPage = 1;
    let currentTablePage = 1;
    let currentView = 'grid';

    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const mobileMenu = document.getElementById('mobileMenu');
    
    mobileMenuButton?.addEventListener('click', () => {
      mobileMenu?.classList.toggle('hidden');
    });

    // File upload handling
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.getElementById('fileInput');
    const fileNamesDisplay = document.querySelector('.file-names');
    const chooseFilesBtn = uploadArea?.querySelector('button');
    
    if (chooseFilesBtn && fileInput) {
      chooseFilesBtn.addEventListener('click', (e) => {
        e.preventDefault();
        fileInput.click();
      });
    }

    fileInput?.addEventListener('change', function() {
      const files = Array.from(this.files || []);
      if (files.length > 0) {
        // Display file names
        fileNamesDisplay.textContent = files.map(f => f.name).join(', ');
      } else {
        fileNamesDisplay.textContent = 'No file chosen';
      }
    });

    // Helper function for image URLs
    function getImageUrl(imagePath) {
      if (!imagePath || imagePath === 'placeholder.jpg') {
        return '../images/placeholder.jpg';
      }
      return `/TripKo-System/uploads/${imagePath}`;
    }

    // All the rest of our functions
    function toggleView() {
      const gridView = document.getElementById('gridView');
      const tableView = document.getElementById('tableView');
      const viewToggleBtn = document.getElementById('viewToggleBtn');

      if (gridView.classList.contains('hidden')) {
        gridView.classList.remove('hidden');
        tableView.classList.add('hidden');
        viewToggleBtn.innerHTML = '<i class="fas fa-table"></i> Table View';
      } else {
        gridView.classList.add('hidden');
        tableView.classList.remove('hidden');
        viewToggleBtn.innerHTML = '<i class="fas fa-th"></i> Grid View';
      }
    }

    async function loadFestivals() {
    const gridContainer = document.querySelector('#gridView .grid');
    const tableBody = document.getElementById('festivalTableBody');

    try {
        console.log('Fetching festivals...');
        
        // Show loading state
        gridContainer.innerHTML = '<div class="col-span-full flex justify-center items-center py-8"><div class="loader"></div></div>';
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Loading...</td></tr>';
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);
        
  const res = await fetch('../../../tripko-backend/api/festival/read.php', {
            signal: controller.signal,
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        clearTimeout(timeoutId);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        console.log('Festivals data:', data);

        // Check if data.records exists and is an array
        const festivals = data.records || [];
        if (!Array.isArray(festivals)) {
            throw new Error('Invalid response format from server');
        }

        if (festivals.length === 0) {
            console.log('No festivals found');
            const noDataMessage = '<div class="text-center py-8 text-gray-500"><i class="fas fa-inbox text-4xl mb-3 block"></i><p>No festivals found</p></div>';
            grid.innerHTML = noDataMessage;
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-500">No festivals found</td></tr>';
            return;
        }

        // Update grid and table views with the festival data
        updateViews(festivals);
        
    } catch (err) {
        console.error('Failed to load festivals:', err);
        grid.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500">Error loading festivals</div>';
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-500">Error loading festivals</td></tr>';
    }
}

// Add this helper function to update views
function updateViews(festivals) {
    const gridContainer = document.querySelector('#gridView .grid');
    const tableBody = document.getElementById('festivalTableBody');

    // Calculate pagination
    const totalGridPages = Math.ceil(festivals.length / itemsPerGridPage);
    const totalTablePages = Math.ceil(festivals.length / itemsPerTablePage);

    // Ensure current pages are within bounds
    currentGridPage = Math.min(Math.max(1, currentGridPage), totalGridPages);
    currentTablePage = Math.min(Math.max(1, currentTablePage), totalTablePages);

    // Get current page items
    const gridStartIndex = (currentGridPage - 1) * itemsPerGridPage;
    const tableStartIndex = (currentTablePage - 1) * itemsPerTablePage;
    const gridItems = festivals.slice(gridStartIndex, gridStartIndex + itemsPerGridPage);
    const tableItems = festivals.slice(tableStartIndex, tableStartIndex + itemsPerTablePage);

    // Render pagination controls
    renderPagination('gridPagination', currentGridPage, totalGridPages);
    renderPagination('tablePagination', currentTablePage, totalTablePages);
    
    // Update grid view
    gridContainer.innerHTML = gridItems.map(f => `
        <div class="bg-white rounded-lg shadow-md border border-gray-200 flex flex-col h-full group hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="relative w-full h-56 bg-gray-100 flex items-center justify-center overflow-hidden">
                <img src="${getImageUrl(f.image_path)}" 
                     alt="${f.name}" 
                     class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
                     onerror="this.onerror=null; this.src='../images/placeholder.jpg';" />
            </div>
            <div class="flex-1 flex flex-col p-5">
                <div class="flex items-center justify-between mb-3">
                    ${f.status === 'inactive' ? 
                        '<span class="bg-red-500 text-white font-medium text-[14px] px-2 py-1 rounded">Inactive</span>' 
                        : ''}
                </div>
                <h3 class="text-[17px] font-medium mb-2 text-[#255D8A] line-clamp-2">${f.name}</h3>
                <p class="text-[15px] text-gray-700 mb-3 line-clamp-3">${f.description}</p>
                <p class="text-[15px] text-gray-500 mt-auto flex items-center">
                    <i class="fas fa-calendar-alt mr-2"></i>${f.date}
                </p>
            </div>
        </div>
    `).join('');

    // Update table view
    tableBody.innerHTML = tableItems.map(f => `
        <tr class="hover:bg-gray-50">
            <td class="border border-gray-300 px-4 py-2">${f.name}</td>
            <td class="border border-gray-300 px-4 py-2">
                <div class="max-w-xs overflow-hidden text-ellipsis">${f.description}</div>
            </td>
            <td class="border border-gray-300 px-4 py-2">${f.date}</td>
            <td class="border border-gray-300 px-4 py-2">${f.town_name || 'Unknown'}</td>
            <td class="border border-gray-300 px-4 py-2 text-center">
                <span class="px-2 py-1 rounded-full text-xs font-medium ${
                    f.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }">
                    ${f.status || 'Unknown'}
                </span>
            </td>
            <td class="border border-gray-300 px-4 py-2 text-center">
                <div class="flex justify-center gap-2">
                    <button onclick="editFestival(${f.festival_id})" 
                            class="bg-[#255d8a] text-white px-3 py-1 rounded text-sm hover:bg-[#1e4d70]">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </button>
                    <button onclick="openStatusModal(${f.festival_id}, '${f.status || 'active'}')"
                            class="${f.status === 'inactive' ? 'bg-red-600' : 'bg-green-600'} text-white px-3 py-1 rounded text-sm hover:bg-opacity-90">
                        Status
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

    let currentFestivalId = null;

    function openStatusModal(festivalId, currentStatus) {
      currentFestivalId = festivalId;
      const modal = document.getElementById('statusModal');
      const statusText = document.getElementById('currentStatusText');
      statusText.textContent = currentStatus;
      statusText.className = 'font-medium ' + 
        (currentStatus === 'active' ? 'text-green-600' : 'text-red-600');
      modal.classList.remove('hidden');
    }

    function closeStatusModal() {
      document.getElementById('statusModal').classList.add('hidden');
      currentFestivalId = null;
    }

    async function updateFestivalStatus(newStatus) {
      if (!currentFestivalId) return;

      try {
  const res = await fetch('../../../tripko-backend/api/festival/toggle_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
            festival_id: currentFestivalId,
            status: newStatus 
          })
        });
        
        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }
        const data = await res.json();
        if (data.success) {
          alert(`Festival status updated to ${newStatus}`);
          closeStatusModal();
          loadFestivals();
        } else {
          throw new Error(data.message || 'Failed to update status');
        }
      } catch (err) {
        console.error('Error updating status:', err);
        alert('Error: ' + err.message);
      }
    }

    // Update the existing toggleStatus function to use the modal
    function toggleStatus(id, currentStatus) {
      openStatusModal(id, currentStatus);
    }

    function openModal() {
      document.getElementById('addFestivalModal').classList.remove('hidden');
    }

    function closeModal() {
      document.getElementById('addFestivalModal').classList.add('hidden');
      document.getElementById('festivalForm').reset();
      // Reset the file input display
      const fileNamesDisplay = document.querySelector('.file-names');
      if (fileNamesDisplay) {
        fileNamesDisplay.textContent = 'No file chosen';
      }
      // Clean up any preview images
      const preview = document.querySelector('.upload-area .image-preview');
      if (preview) {
        preview.remove();
      }
    }

    async function editFestival(id) {
      try {
        console.log('Fetching festival:', id);
  const response = await fetch(`../../../tripko-backend/api/festival/read_single.php?id=${id}`, {
          method: 'GET',
          credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        });
        
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);
        
        if (!response.ok) {
          throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        
        if (data.success && data.festival) {
          const festival = data.festival;
          
          // Populate form fields
          const form = document.getElementById('festivalForm');
          form.name.value = festival.name || '';
          form.description.value = festival.description || '';
          form.date.value = festival.date || '';
          form.municipality.value = festival.town_id || '';
          
          // Add festival ID to form for update
          let idInput = form.querySelector('input[name="festival_id"]');
          if (!idInput) {
            idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'festival_id';
            form.appendChild(idInput);
          }
          idInput.value = id;
          
          openModal();
        } else {
          throw new Error(data.message || 'Failed to load festival details');
        }
      } catch (error) {
        console.error('Error loading festival:', error);
        alert(`Error: ${error.message}`);
      }
    }

    

    // Update form submission to handle both create and edit
    document.getElementById('festivalForm')?.addEventListener('submit', async e => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const isEdit = formData.has('festival_id');
      
      try {
        const url = isEdit ? 
          '../../../tripko-backend/api/festival/update.php' : 
          '../../../tripko-backend/api/festival/create.php';

        console.log('Form data being sent:', Object.fromEntries(formData));
        const res = await fetch(url, {
          method: 'POST',
          body: formData
        });
        
        const result = await res.json();
        console.log('Server response:', result);
        
        if (result.success) {
          alert(isEdit ? 'Festival updated successfully!' : 'Festival added successfully!');
          closeModal();
          loadFestivals();
        } else {
          throw new Error(result.message || `Failed to ${isEdit ? 'update' : 'add'} festival`);
        }
      } catch (err) {
        console.error(isEdit ? 'Update error:' : 'Error submitting form:', err);
        alert('Error: ' + err.message);
      }
    });

    // Initialize everything when the DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
      // Set up file input handling
      const fileInput = document.getElementById('fileInput');
      const fileNamesDisplay = document.querySelector('.file-names');
      const uploadArea = document.querySelector('.upload-area');

      fileInput?.addEventListener('change', function() {
        const files = Array.from(this.files || []);
        if (files.length > 0) {
          fileNamesDisplay.textContent = files.map(f => f.name).join(', ');
        } else {
          fileNamesDisplay.textContent = 'No file chosen';
        }
      });

      // Load municipalities for the select dropdown
      async function loadMunicipalities() {
        try {
          const response = await fetch('../../../tripko-backend/api/towns/read.php');
          const data = await response.json();
          const municipalitySelect = document.getElementById('municipality');
          
          municipalitySelect.innerHTML = '<option value="" selected disabled>Select municipality</option>';
          
          if (data.success && data.records && Array.isArray(data.records)) {
            data.records.forEach(town => {
              const option = document.createElement('option');
              option.value = town.town_id;
              option.textContent = town.name;
              municipalitySelect.appendChild(option);
            });
          } else {
            throw new Error('Invalid data format received from server');
          }
        } catch (error) {
          console.error('Failed to load municipalities:', error);
          document.getElementById('municipality').innerHTML = 
            '<option value="" disabled selected>Error loading municipalities</option>';
        }
      }
      
      // Initialize page
      loadMunicipalities();

      // Load initial data
      loadFestivals();
    });

    // Load municipalities when the page loads
    document.addEventListener('DOMContentLoaded', async () => {
      try {
        await loadMunicipalities();
        await loadFestivals();
      } catch (error) {
        console.error('Error during initialization:', error);
      }
    });

    // Load municipalities for the select dropdown
    async function loadMunicipalities() {
      const select = document.getElementById('municipality');
      if (!select) {
        console.error('Municipality select element not found');
        return;
      }

      try {
        console.log('Fetching municipalities...');
  const response = await fetch('../../../tripko-backend/api/towns/read.php', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Cache-Control': 'no-cache'
          }
        });

        console.log('Response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        let text;
        try {
          text = await response.text();
          console.log('Raw response:', text);
        } catch (e) {
          throw new Error(`Failed to read response: ${e.message}`);
        }

        if (!text.trim()) {
          throw new Error('Empty response received from server');
        }

        let data;
        try {
          data = JSON.parse(text);
          console.log('Parsed data:', data);
        } catch (e) {
          console.error('JSON parse error:', e);
          throw new Error(`Invalid JSON response: ${e.message}`);
        }

        select.innerHTML = '<option value="" disabled selected>Select municipality</option>';
        
        if (!data.success) {
          throw new Error(data.message || 'Server returned unsuccessful response');
        }

        if (!data.records || !Array.isArray(data.records)) {
          throw new Error('Invalid data format: missing or invalid records array');
        }

        console.log('Found', data.records.length, 'municipalities');
        
        // Sort municipalities by name
        const sortedRecords = [...data.records].sort((a, b) => 
          a.name.localeCompare(b.name, undefined, {sensitivity: 'base'})
        );

        sortedRecords.forEach(town => {
          if (!town.town_id || !town.name) {
            console.warn('Invalid town data:', town);
            return;
          }
          const option = document.createElement('option');
          option.value = town.town_id;
          option.textContent = town.name;
          select.appendChild(option);
        });

        if (select.children.length <= 1) {
          throw new Error('No valid municipalities loaded');
        }
      } catch (error) {
        console.error('Failed to load municipalities:', error);
        
        const errorMessage = error.message.includes('Failed to fetch') ? 
          'Network error - please check your connection' :
          'Error loading municipalities';
          
        select.innerHTML = `<option value="" disabled selected>${errorMessage}</option>`;
      }
    }

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
    icon.style.transform = dropdown.classList.contains('hidden') ? 'rotate(180deg)' : 'rotate(0deg)';
}

/* Pagination functions */
function renderPagination(containerId, currentPage, totalPages) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let paginationHTML = '';
    
    // Previous button
    paginationHTML += `
        <button onclick="handlePageChange(${currentPage - 1}, '${containerId}')" 
                class="px-3 py-1 rounded-md bg-gray-200 hover:bg-gray-300 transition-colors text-sm ${currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : ''}"
                ${currentPage <= 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>`;

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            paginationHTML += `
                <button onclick="handlePageChange(${i}, '${containerId}')" 
                        class="px-3 py-1 rounded-md transition-colors text-sm ${
                            i === currentPage 
                                ? 'bg-[#255D8A] text-white' 
                                : 'bg-gray-200 hover:bg-gray-300'
                        }">
                    ${i}
                </button>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            paginationHTML += `<span class="px-2">...</span>`;
        }
    }

    // Next button
    paginationHTML += `
        <button onclick="handlePageChange(${currentPage + 1}, '${containerId}')" 
                class="px-3 py-1 rounded-md bg-gray-200 hover:bg-gray-300 transition-colors text-sm ${currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : ''}"
                ${currentPage >= totalPages ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>`;

    container.innerHTML = paginationHTML;
}

// Function to handle page changes
function handlePageChange(newPage, containerId) {
    if (containerId === 'gridPagination') {
        currentGridPage = newPage;
    } else if (containerId === 'tablePagination') {
        currentTablePage = newPage;
    }
    loadFestivals();
}
  </script>
    <script>
      (function() {
        const VIEW_ONLY = true;
        if (!VIEW_ONLY) return;

        document.addEventListener('DOMContentLoaded', () => {
          // Replace any Edit/Status buttons in festival table rows
          const tableBody = document.getElementById('festivalTableBody');
          if (tableBody) {
            const obs = new MutationObserver(() => {
              tableBody.querySelectorAll('td').forEach(td => {
                if (td.innerHTML.includes('Edit') || td.innerHTML.includes('Status')) {
                  td.innerHTML = '<div class="flex justify-center gap-2"><span class="text-sm text-gray-600">—</span></div>';
                }
              });
            });
            obs.observe(tableBody, { childList: true, subtree: true });
          }

          // Replace the Add button if present
          const addBtn = Array.from(document.querySelectorAll('button')).find(b => b.getAttribute('onclick') && b.getAttribute('onclick').includes('openModal'));
          if (addBtn) {
            const span = document.createElement('span');
            span.className = 'text-sm text-gray-500';
            span.textContent = 'View-only mode';
            addBtn.parentNode.replaceChild(span, addBtn);
          }

          // Stub write functions
          window.openModal = () => alert('View-only account: action disabled.');
          window.closeModal = () => {};
          window.editFestival = () => alert('View-only account: action disabled.');
          window.openStatusModal = () => alert('View-only account: action disabled.');
          window.updateFestivalStatus = () => alert('View-only account: action disabled.');
          document.getElementById('festivalForm')?.addEventListener('submit', (e) => { e.preventDefault(); alert('View-only account: action disabled.'); });
        });
      })();
    </script>
</body>
<script src="../file_js/festivals.js"></script>
</html>