<?php
require_once('../../../tripko-backend/config/check_session.php');
checkAdminSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TripKo Pangasinan - Municipality Management</title>  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Merriweather&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../file_css/dashboard.css" />
  <style>
    body {
        font-family: 'Kameron', serif;
        font-size: 17px;
    }

    .form-container {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        width: 95%;
        max-width: 1200px;
        margin: auto;
        position: relative;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: border-color 0.15s ease-in-out;
    }

    .form-control:focus {
        border-color: #255D8A;
        outline: none;
        box-shadow: 0 0 0 2px rgba(37, 93, 138, 0.1);
    }

    .required {
        color: #e11d48;
    }    .upload-area {
        border: 2px dashed #d1d5db;
        padding: 2rem;
        text-align: center;
        border-radius: 0.5rem;
        transition: all 0.15s ease-in-out;
        background-color: white;
    }

    .upload-area:hover #uploadText {
        color: #255D8A;
    }

    #uploadText {
        cursor: pointer;
        padding: 1rem;
        transition: all 0.15s ease-in-out;
    }

    #uploadText:hover {
        background-color: rgba(37, 93, 138, 0.05);
        border-radius: 0.5rem;
    }    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 40;
        pointer-events: auto;
    }
      .modal-container {
        position: relative;
        z-index: 9999;
    }

    .form-container {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        width: 95%;
        max-width: 800px;
        margin: auto;
    }    .upload-container {
        position: relative;
        isolation: isolate;
        pointer-events: none;
    }
    
    .upload-area, 
    .upload-area * {
        pointer-events: auto;
    }

    .modal-content {
        pointer-events: none;
    }

    .modal-content > * {
        pointer-events: auto;
    }
  </style>
</head>
<body>
  <div class="flex h-screen bg-gray-100">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <!-- Main content -->
    <main class="flex-1 bg-[#F3F1E7] p-6">
      <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
          <button id="viewToggleBtn" onclick="toggleView()" class="bg-[#255D8A] text-white px-4 py-2 rounded-md hover:bg-[#1e4d70] transition-colors">
            <i class="fas fa-table mr-2"></i>Table View
          </button>
          <button onclick="openModal()" class="bg-[#255D8A] text-white px-4 py-2 rounded-md hover:bg-[#1e4d70] transition-colors h-[40px] flex items-center min-w-[120px] justify-center">
            <i class="fas fa-plus mr-2"></i>Add Municipality
          </button>
        </div>
      </div>

      <!-- Grid View -->
      <div id="gridView" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        </div>
        <!-- Grid Pagination -->
        <div class="flex justify-center items-center mt-6 gap-2" id="gridPagination">
        </div>
      </div>

      <!-- Table View -->
      <div id="tableView">
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <table class="w-full">            <thead>
              <tr>
                <th class="px-4 py-3 bg-[#BBDEFB] text-left text-[#0d47a1] text-sm font-medium">Municipality Name</th>
                <th class="px-4 py-3 bg-[#BBDEFB] text-center text-[#0d47a1] text-sm font-medium">Details</th>
                <th class="px-4 py-3 bg-[#BBDEFB] text-center text-[#0d47a1] text-sm font-medium">Actions</th>
              </tr>
            </thead>
            <tbody id="townTableBody">
              <!-- Table content will be loaded dynamically -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Add/Edit Municipality Modal -->      <div id="municipalityModal" class="fixed inset-0 hidden" style="z-index: 9999;">
        <div class="modal-overlay" onclick="closeModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">          <div class="form-container bg-white relative modal-content">
            <button type="button" class="absolute right-4 top-4 text-gray-500 hover:text-gray-700" onclick="closeModal()">
              <i class="fas fa-times text-xl"></i>
            </button>
            
            <h2 class="text-2xl font-bold mb-6" id="modalTitle">Add New Municipality</h2>
            
            <form id="municipalityForm" onsubmit="handleSubmit(event)">
              <input type="hidden" name="town_id" id="townId">
                <div class="form-group mb-6">
                <label>Municipality Name <span class="required">*</span></label>
                <input type="text" name="name" required class="form-control">
              </div>              <div class="form-group mb-6">
                <label>Municipality Image</label>
                <div class="upload-container">
                  <input type="file" name="image" accept="image/*" class="hidden" id="imageInput" onchange="handleImageSelect(event)">
                  <div class="upload-area">
                    <div id="uploadText" class="cursor-pointer" onclick="triggerFileInput(event)">
                      <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                      <p class="text-gray-500">Click here to upload an image</p>
                    </div>
                    <div id="imagePreview" class="hidden mt-4">
                      <!-- Preview will be added here -->
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex justify-end gap-3">
                <button type="submit" id="submitBtn" class="px-4 py-2 bg-[#255D8A] text-white rounded hover:bg-[#1e4d70]">Save Municipality</button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
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
            <h3 class="text-xl font-bold mb-4">Change Municipality Status</h3>
            <p class="mb-4">Current status: <span id="currentStatusText" class="font-semibold"></span></p>
            <div class="space-y-3">
              <button onclick="updateMunicipalityStatus('active')" 
                      class="w-full py-2 px-4 rounded bg-green-600 text-white hover:bg-green-700 transition-colors">
                Set Active
              </button>
              <button onclick="updateMunicipalityStatus('inactive')" 
                      class="w-full py-2 px-4 rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
                Set Inactive
              </button>
              <button onclick="closeStatusModal()" 
                      class="w-full py-2 px-4 rounded bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    let currentTownId = null;

    document.addEventListener('DOMContentLoaded', () => {
      loadTableView();
      
      // Close dropdown when clicking outside
      document.addEventListener('click', (e) => {
        if (!e.target.closest('#transportDropdown') && !e.target.closest('[onclick*="toggleTransportDropdown"]')) {
          const dropdown = document.getElementById('transportDropdown');
          const icon = document.getElementById('transportDropdownIcon');
          if (dropdown) {
            dropdown.classList.add('hidden');
            if (icon) {
              icon.style.transform = 'rotate(0deg)';
            }
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

    async function loadTableView() {
      try {
  const response = await fetch('../../../tripko-backend/api/towns/read.php');
        const data = await response.json();
        const tableBody = document.getElementById('townTableBody');
        tableBody.innerHTML = '';
        
        if (data && data.records && Array.isArray(data.records)) {
          data.records.forEach(town => {
            const statusClass = town.status === 'inactive' ? 'bg-red-50' : '';
            const statusColor = town.status === 'inactive' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
            
            tableBody.innerHTML += `              <tr class="hover:bg-gray-50 transition-colors">
                <td class="border border-gray-300 px-4 py-3">
                  <div class="text-sm font-medium text-gray-900">${town.name || ''}</div>
                </td>
                <td class="border border-gray-300 px-4 py-3 text-center text-gray-600">
                  ${town.name || 'Municipality'}
                </td>
                            class="${town.status === 'inactive' ? 'bg-red-600' : 'bg-green-600'} text-white px-3 py-1.5 rounded text-sm hover:opacity-90 transition-opacity">
                      <i class="fas ${town.status === 'inactive' ? 'fa-toggle-off' : 'fa-toggle-on'} mr-1"></i>Status
                    </button>
                  </div>
                </td>
              </tr>
            `;
          });
        } else {
          tableBody.innerHTML = `
            <tr>
              <td colspan="4" class="text-center py-8 text-gray-500">
                <i class="fas fa-info-circle text-xl mb-2"></i>
                <p>No municipalities found</p>
              </td>
            </tr>
          `;
        }
      } catch (error) {
        console.error('Error loading table data:', error);
        tableBody.innerHTML = `
          <tr>
            <td colspan="4" class="text-center py-8 text-red-500">
              <i class="fas fa-exclamation-circle text-xl mb-2"></i>
              <p>Error loading municipalities</p>
            </td>
          </tr>
        `;
      }
    }

    function openStatusModal(townId, currentStatus) {
      currentTownId = townId;
      const modal = document.getElementById('statusModal');
      const statusText = document.getElementById('currentStatusText');
      statusText.textContent = currentStatus;
      statusText.className = 'font-medium ' + 
        (currentStatus === 'inactive' ? 'text-red-600' : 'text-green-600');
      modal.classList.remove('hidden');
    }

    function closeStatusModal() {
      document.getElementById('statusModal').classList.add('hidden');
      currentTownId = null;
    }

    async function updateMunicipalityStatus(newStatus) {
      if (!currentTownId) return;

      try {
        // Show loading state
        const buttons = document.querySelectorAll('#statusModal button');
        buttons.forEach(btn => {
          btn.disabled = true;
          if (btn.textContent.trim().toLowerCase().includes(newStatus)) {
            btn.innerHTML = `<i class="fas fa-circle-notch fa-spin mr-2"></i>Updating...`;
          }
        });

  const response = await fetch('../../../tripko-backend/api/towns/toggle_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
            town_id: currentTownId,
            status: newStatus 
          })
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
          closeStatusModal();
          await loadTableView();
          showNotification(`Municipality status updated to ${newStatus}`, 'success');
        } else {
          throw new Error(data.message || 'Failed to update status');
        }
      } catch (error) {
        console.error('Error updating status:', error);
        showNotification(error.message, 'error');
      } finally {
        // Reset button states
        const buttons = document.querySelectorAll('#statusModal button');
        buttons.forEach(btn => {
          btn.disabled = false;
          if (btn.innerHTML.includes('fa-spin')) {
            const statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            btn.innerHTML = `Set ${statusText}`;
          }
        });
      }
    }

    function openModal(municipalityData = null) {
      const modal = document.getElementById('municipalityModal');
      const form = document.getElementById('municipalityForm');
      const modalTitle = document.getElementById('modalTitle');
      const imagePreview = document.getElementById('imagePreview');
      const uploadText = document.getElementById('uploadText');
      
      // Reset form and preview
      form.reset();
      imagePreview.innerHTML = '';
      imagePreview.classList.add('hidden');
      uploadText.classList.remove('hidden');      if (municipalityData) {
        // Editing existing municipality
        console.log('Editing municipality:', municipalityData);
        modalTitle.textContent = 'Edit Municipality';
        document.getElementById('townId').value = municipalityData.town_id;
        form.name.value = municipalityData.name || '';
        if (municipalityData.image_path) {
          showImagePreview('../../uploads/' + municipalityData.image_path);
        }
      } else {
        // Adding new municipality
        console.log('Adding new municipality');
        modalTitle.textContent = 'Add New Municipality';
        document.getElementById('townId').value = '';
      }
      
      modal.classList.remove('hidden');
    }

    function closeModal() {
      const modal = document.getElementById('municipalityModal');
      const form = document.getElementById('municipalityForm');
      const imagePreview = document.getElementById('imagePreview');
      const uploadText = document.getElementById('uploadText');
      
      // Reset form and clear data
      form.reset();
      document.getElementById('townId').value = '';
      imagePreview.innerHTML = '';
      imagePreview.classList.add('hidden');
      uploadText.classList.remove('hidden');
      
      // Reset any error states
      form.querySelectorAll('.form-control').forEach(input => {
        input.classList.remove('border-red-500');
      });
      
      // Hide modal
      modal.classList.add('hidden');
      
      // Enable submit button and reset text
      const submitBtn = document.getElementById('submitBtn');
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Save Municipality';
    }

    function editMunicipality(municipality) {
      if (!municipality || !municipality.town_id) {
        showNotification('Invalid municipality data', 'error');
        return;
      }

      try {
        openModal(municipality);
      } catch (error) {
        console.error('Error setting up edit form:', error);
        showNotification('Error loading municipality data', 'error');
      }    }    function showImagePreview(url) {
      const preview = document.getElementById('imagePreview');
      const uploadText = document.getElementById('uploadText');
      
      preview.innerHTML = `
        <div class="relative">
          <img src="${url}" 
               alt="Preview" 
               class="max-h-48 w-auto mx-auto rounded-lg shadow-sm cursor-pointer transition-all duration-300 hover:shadow-md"
               onclick="triggerFileInput(event)"
               onerror="handleImageError(this)">
        </div>
      `;
      
      uploadText.classList.add('hidden');
      preview.classList.remove('hidden');
    }    function handleImageError(img) {
      // First try with the default placeholder
      img.src = '../file_images/placeholder.jpg';
      // If that fails too, replace with a div showing an icon
      img.onerror = () => {
        const container = img.parentElement;
        container.innerHTML = `
          <div class="flex flex-col items-center justify-center h-full bg-gray-100">
            <i class="fas fa-image text-gray-400 text-4xl mb-2"></i>
            <p class="text-gray-500 text-sm">Image not available</p>
          </div>
        `;
      };
    }function handleImageSelect(event) {
      event.preventDefault();
      event.stopPropagation();

      const file = event.target.files[0];
      if (!file) return;

      // Validate file type
      const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
      if (!validTypes.includes(file.type)) {
        showNotification('Please select a valid image file (JPEG, PNG, or GIF)', 'error');
        event.target.value = '';
        return;
      }

      // Validate file size (5MB max)
      if (file.size > 5 * 1024 * 1024) {
        showNotification('Image size should be less than 5MB', 'error');
        event.target.value = '';
        return;
      }

      // Create preview
      const reader = new FileReader();
      reader.onload = function(e) {
        if (e.target && e.target.result) {
          showImagePreview(e.target.result);
        }
      };
      reader.readAsDataURL(file);
    }    function triggerFileInput(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      
      const fileInput = document.getElementById('imageInput');
      if (fileInput) {
        fileInput.click();
      }
    }
    
    function showNotification(message, type = 'success') {
      // Remove any existing notifications
      const existingNotifications = document.querySelectorAll('.notification');
      existingNotifications.forEach(notification => notification.remove());

      // Create new notification
      const notification = document.createElement('div');
      notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out z-50 flex items-center space-x-3 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
      } text-white`;

      notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
      `;

      // Add to document
      document.body.appendChild(notification);

      // Animate in
      setTimeout(() => {
        notification.style.transform = 'translateY(10px)';
      }, 100);

      // Remove after delay
      setTimeout(() => {
        notification.style.transform = 'translateY(-100%)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }    // Handle form submission
    async function handleSubmit(event) {
      event.preventDefault();
      const form = document.getElementById('municipalityForm');
      const formData = new FormData(form);
      const townId = document.getElementById('townId').value;
      const submitBtn = document.getElementById('submitBtn');

      try {
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i>Saving...';

        // Validate form data
        const name = formData.get('name').trim();
        if (!name) {
          throw new Error('Municipality name is required');
        }

        const isEditing = townId && townId.trim() !== '';
        formData.append('town_id', townId); // Ensure town_id is included for updates
  const url = `../../../tripko-backend/api/towns/${isEditing ? 'update' : 'create'}.php`;

        // Validate image if one is selected
        const imageFile = formData.get('image');
        if (imageFile && imageFile.size > 0) {
          const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
          if (!validTypes.includes(imageFile.type)) {
            throw new Error('Please select a valid image file (JPEG, PNG, or GIF)');
          }
          if (imageFile.size > 5 * 1024 * 1024) {
            throw new Error('Image size should be less than 5MB');
          }
        }        // Send request
        const response = await fetch(url, {
          method: 'POST',
          body: formData
        });

        let data;
        const responseText = await response.text();
        try {
          data = JSON.parse(responseText);
        } catch (e) {
          console.error('Server response:', responseText);
          throw new Error('Invalid server response');
        }

        if (!response.ok) {
          throw new Error(data.message || `Server error: ${response.status}`);
        }

        if (data.success) {
          closeModal();
          await loadTableView();
          showNotification(
            isEditing ? 'Municipality updated successfully' : 'Municipality added successfully', 
            'success'
          );
        } else {
          throw new Error(data.message || 'Error saving municipality');
        }
      } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
      } finally {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Municipality';
      }
    }

    let currentView = 'table';
    let currentPage = 1;
    const itemsPerPage = 9;

    // Toggle between grid and table views
    function toggleView() {
      const gridView = document.getElementById('gridView');
      const tableView = document.getElementById('tableView');
      const toggleBtn = document.getElementById('viewToggleBtn');
      const paginationContainer = document.getElementById('gridPagination');

      if (currentView === 'table') {
        gridView.classList.remove('hidden');
        tableView.classList.add('hidden');
        toggleBtn.innerHTML = '<i class="fas fa-table mr-2"></i>Table View';
        currentView = 'grid';
        LoadGridView(); // Load grid data
      } else {
        gridView.classList.add('hidden');
        tableView.classList.remove('hidden');
        toggleBtn.innerHTML = '<i class="fas fa-grip-horizontal mr-2"></i>Grid View';
        currentView = 'table';
        LoadTableView(); // Load table data
      }
      
      // Reset pagination when switching views
      currentPage = 1;
      paginationContainer.innerHTML = '';
    }    // Load Grid View    
    function LoadGridView() {
      const gridContainer = document.querySelector('#gridView .grid');
      const paginationContainer = document.getElementById('gridPagination');
      
  fetch('../../../tripko-backend/api/towns/read.php')
        .then(response => response.json())
        .then(data => {
          console.log('API Response:', data); // Debug log
          
          if (data && data.records && Array.isArray(data.records)) {
            // Clear existing content
            gridContainer.innerHTML = '';
            
            // Filter out municipalities without images and debug log the filter
            const municipalitiesWithImages = data.records.filter(town => {
              console.log('Town:', town.name, 'Image path:', town.image_path); // Debug log
              return town.image_path && town.image_path.trim() !== '';
            });
            
            console.log('Municipalities with images:', municipalitiesWithImages); // Debug log
            
            if (municipalitiesWithImages.length === 0) {
              gridContainer.innerHTML = `
                <div class="col-span-3 text-center py-8 text-gray-500">
                  <i class="fas fa-images text-xl mb-2"></i>
                  <p>No municipalities with images found</p>
                  <p class="text-sm mt-2">Switch to table view to see all municipalities</p>
                  <p class="text-xs text-gray-400 mt-2">Total municipalities: ${data.records.length}</p>
                </div>
              `;
              return;
            }
            
            municipalitiesWithImages.forEach(town => {
              const gridItem = document.createElement('div');
              gridItem.className = 'bg-white rounded-lg shadow overflow-hidden';
              
              // Check if image path starts with http/https
              const imagePath = town.image_path.startsWith('http') ? 
                town.image_path : 
                `../../uploads/${town.image_path}`;
                gridItem.innerHTML = `
                <div class="relative h-48 overflow-hidden">
                  <img src="${imagePath}" 
                       alt="${town.name || 'Town Image'}" 
                       class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                       onerror="handleImageError(this)">
                </div>
                <div class="p-4">
                  <h3 class="text-lg font-semibold mb-2">${town.name || 'Unnamed Municipality'}</h3>
                  <p class="text-gray-600 text-sm">Click to view details</p>
                </div>
              `;
              
              gridContainer.appendChild(gridItem);
            });

            // Update pagination controls if needed
            const totalPages = Math.ceil(municipalitiesWithImages.length / itemsPerPage);
            updatePagination(totalPages, paginationContainer);
          } else {
            gridContainer.innerHTML = `
              <div class="col-span-3 text-center py-8 text-gray-500">
                <i class="fas fa-info-circle text-xl mb-2"></i>
                <p>No municipalities found</p>
              </div>
            `;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          gridContainer.innerHTML = `
            <div class="col-span-3 text-center py-8 text-red-500">
              <i class="fas fa-exclamation-circle text-xl mb-2"></i>
              <p>Error loading municipalities</p>
            </div>
          `;
        });
    }

    // Update pagination controls
    function updatePagination(totalPages, container) {
      container.innerHTML = '';
      
      // Previous button
      const prevButton = document.createElement('button');
      prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
      prevButton.className = `px-3 py-1 rounded ${currentPage === 1 ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-500 text-white hover:bg-blue-600'}`;
      prevButton.disabled = currentPage === 1;
      prevButton.onclick = () => {
        if (currentPage > 1) {
          currentPage--;
          currentView === 'grid' ? LoadGridView() : LoadTableView();
        }
      };
      container.appendChild(prevButton);

      // Page numbers
      for (let i = 1; i <= totalPages; i++) {
        const pageButton = document.createElement('button');
        pageButton.textContent = i;
        pageButton.className = `px-3 py-1 rounded ${currentPage === i ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'}`;
        pageButton.onclick = () => {
          currentPage = i;
          currentView === 'grid' ? LoadGridView() : LoadTableView();
        };
        container.appendChild(pageButton);
      }

      // Next button
      const nextButton = document.createElement('button');
      nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
      nextButton.className = `px-3 py-1 rounded ${currentPage === totalPages ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-500 text-white hover:bg-blue-600'}`;
      nextButton.disabled = currentPage === totalPages;
      nextButton.onclick = () => {
        if (currentPage < totalPages) {
          currentPage++;
          currentView === 'grid' ? LoadGridView() : LoadTableView();
        }
      };
      container.appendChild(nextButton);
    }

    // Modified LoadTableView to work with pagination
    function LoadTableView() {
      const tableBody = document.getElementById('townTableBody');
      const paginationContainer = document.getElementById('gridPagination');

  fetch('../../../tripko-backend/api/towns/read.php')
        .then(response => response.json())
        .then(data => {
          if (data && data.records && Array.isArray(data.records)) {
            const totalItems = data.records.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedData = data.records.slice(start, end);

            tableBody.innerHTML = '';
            
            paginatedData.forEach(town => {
              const row = document.createElement('tr');
              row.className = `hover:bg-gray-50 transition-colors ${town.status === 'inactive' ? 'bg-red-50' : ''}`;              const editButton = document.createElement('button');
              editButton.className = 'bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:opacity-90 transition-opacity';
              editButton.innerHTML = '<i class="fas fa-edit mr-1"></i>Edit';
              editButton.addEventListener('click', () => editMunicipality(town));

              const statusButton = document.createElement('button');
              statusButton.className = `${town.status === 'inactive' ? 'bg-red-600' : 'bg-green-600'} text-white px-3 py-1.5 rounded text-sm hover:opacity-90 transition-opacity`;
              statusButton.innerHTML = `<i class="fas ${town.status === 'inactive' ? 'fa-toggle-off' : 'fa-toggle-on'} mr-1"></i>Status`;
              statusButton.addEventListener('click', () => openStatusModal(town.town_id, town.status));

              const actionsDiv = document.createElement('div');
              actionsDiv.className = 'flex justify-center gap-2';
              actionsDiv.appendChild(editButton);
              actionsDiv.appendChild(statusButton);

              row.innerHTML = `
                <td class="border border-gray-300 px-4 py-3">
                  <div class="text-sm font-medium text-gray-900">${town.name || 'Unnamed Municipality'}</div>
                </td>
                <td class="border border-gray-300 px-4 py-3 text-center">
                  <div class="text-sm text-gray-600">${town.name || 'Municipality'}</div>
                </td>
              `;

              const actionsCell = document.createElement('td');
              actionsCell.className = 'border border-gray-300 px-4 py-3 text-center';
              actionsCell.appendChild(actionsDiv);
              row.appendChild(actionsCell);
              tableBody.appendChild(row);
            });

            // Update pagination
            updatePagination(totalPages, paginationContainer);
          } else {
            tableBody.innerHTML = `
              <tr>
                <td colspan="4" class="text-center py-8 text-gray-500">
                  <i class="fas fa-info-circle text-xl mb-2"></i>
                  <p>No municipalities found</p>
                </td>
              </tr>
            `;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          tableBody.innerHTML = `
            <tr>
              <td colspan="4" class="text-center py-8 text-red-500">
                <i class="fas fa-exclamation-circle text-xl mb-2"></i>
                <p>Error loading municipalities</p>
              </td>
            </tr>
          `;
        });
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', () => {
      LoadTableView();
    });
  </script>
</body>
</html>
