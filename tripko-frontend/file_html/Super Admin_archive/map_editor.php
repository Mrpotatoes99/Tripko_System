<?php
include('../../../tripko-backend/config/db.php');

// Remove ob_start and instead just make sure errors are visible
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Allow fetch to work without login check
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
  header('Content-Type: application/json');
  
  $result = $conn->query("
  SELECT me.*, mi.image_path AS image_url
  FROM map_editor me
  LEFT JOIN map_images mi ON me.map_id = mi.map_id
");

  $locations = [];
  while ($row = $result->fetch_assoc()) {
      $locations[] = $row;
  }
  echo json_encode($locations);
  exit;
}

// Start session after handling fetch
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

// Save Marker API (Insert, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  ob_start();

  // DELETE
  if (isset($_POST['delete_map_id'])) {
    $mapId = intval($_POST['delete_map_id']);
    // Get location_id for cleanup
    $getLoc = $conn->prepare("SELECT location_id FROM map_editor WHERE map_id = ?");
    $getLoc->bind_param("i", $mapId);
    $getLoc->execute();
    $getLoc->bind_result($locationId);
    $getLoc->fetch();
    $getLoc->close();
    // Delete image(s)
    $imgQ = $conn->prepare("SELECT image_path FROM map_images WHERE map_id = ?");
    $imgQ->bind_param("i", $mapId);
    $imgQ->execute();
    $imgQ->bind_result($imgPath);
    while ($imgQ->fetch()) {
      if ($imgPath && file_exists($imgPath)) @unlink($imgPath);
    }
    $imgQ->close();
    $conn->query("DELETE FROM map_images WHERE map_id = $mapId");
    $conn->query("DELETE FROM map_editor WHERE map_id = $mapId");
    if ($locationId) $conn->query("DELETE FROM location WHERE location_id = $locationId");
    $buffer = trim(ob_get_clean());
    echo json_encode(['status' => 'success']);
    exit;
  }

  // EDIT
  if (isset($_POST['edit_map_id'])) {
    $mapId = intval($_POST['edit_map_id']);
    $locationName = $_POST['location_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = ($_POST['category'] === 'Other' && !empty($_POST['custom_category'])) ? $_POST['custom_category'] : $_POST['category'];
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $cityMunId = intval($_POST['city_mun_id'] ?? 0);
    $barangayId = intval($_POST['barangay_id'] ?? 0);

    // Get location_id
    $getLoc = $conn->prepare("SELECT location_id FROM map_editor WHERE map_id = ?");
    $getLoc->bind_param("i", $mapId);
    $getLoc->execute();
    $getLoc->bind_result($locationId);
    $getLoc->fetch();
    $getLoc->close();

    // Update location
    $locStmt = $conn->prepare("UPDATE location SET town_id=?, barangay_id=? WHERE location_id=?");
    $locStmt->bind_param("iii", $cityMunId, $barangayId, $locationId);
    $locStmt->execute();
    $locStmt->close();

    // Update map_editor
    $stmt = $conn->prepare("UPDATE map_editor SET location_name=?, description=?, category=?, latitude=?, longitude=? WHERE map_id=?");
    $stmt->bind_param("ssssdi", $locationName, $description, $category, $latitude, $longitude, $mapId);
    $stmt->execute();
    $stmt->close();

    // Handle image upload (optional)
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
      $imageTmp = $_FILES['image_path']['tmp_name'];
      $imageName = basename($_FILES['image_path']['name']);
      $safeName = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $imageName);
      $uploadDir = 'uploads/';
      $uploadPath = $uploadDir . $safeName;
      if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
      if (move_uploaded_file($imageTmp, $uploadPath)) {
        // Remove old image(s)
        $imgQ = $conn->prepare("SELECT image_path FROM map_images WHERE map_id = ?");
        $imgQ->bind_param("i", $mapId);
        $imgQ->execute();
        $imgQ->bind_result($imgPath);
        while ($imgQ->fetch()) {
          if ($imgPath && file_exists($imgPath)) @unlink($imgPath);
        }
        $imgQ->close();
        $conn->query("DELETE FROM map_images WHERE map_id = $mapId");
        // Insert new image
        $imgStmt = $conn->prepare("INSERT INTO map_images (map_id, image_path) VALUES (?, ?)");
        if ($imgStmt) {
          $imgStmt->bind_param("is", $mapId, $uploadPath);
          $imgStmt->execute();
          $imgStmt->close();
        }
      }
    }
    $buffer = trim(ob_get_clean());
    if (!empty($buffer)) {
      echo json_encode(['status' => 'error', 'message' => 'Unexpected output', 'debug' => $buffer]);
    } else {
      echo json_encode(['status' => 'success']);
    }
    exit;
  }

  // ADD/INSERT (default)
  $locationName = $_POST['location_name'] ?? '';
  $description = $_POST['description'] ?? '';
  $category = ($_POST['category'] === 'Other' && !empty($_POST['custom_category'])) ? $_POST['custom_category'] : $_POST['category'];
  $latitude = floatval($_POST['latitude'] ?? 0);
  $longitude = floatval($_POST['longitude'] ?? 0);
  $provinceId = 1;
  $cityMunId = intval($_POST['city_mun_id'] ?? 0);
  $barangayId = intval($_POST['barangay_id'] ?? 0);

  // Insert into location table in database
  $locStmt = $conn->prepare("INSERT INTO location (province_id, town_id, barangay_id) VALUES (?, ?, ?)");
  if (!$locStmt || !$locStmt->bind_param("iii", $provinceId, $cityMunId, $barangayId) || !$locStmt->execute()) {
      echo json_encode(['status' => 'error', 'message' => 'Location insert failed']);
      exit;
  }
  $locationId = $locStmt->insert_id;
  $locStmt->close();
  // Insert into map_editor table
  $stmt = $conn->prepare("INSERT INTO map_editor (location_name, location_id, description, category, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
  if (!$stmt || !$stmt->bind_param("sissdd", $locationName, $locationId, $description, $category, $latitude, $longitude) || !$stmt->execute()) {
      echo json_encode(['status' => 'error', 'message' => 'Map insert failed']);
      exit;
  }
  $mapId = $stmt->insert_id;
  $stmt->close();

  // Handle image upload
  if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
      $imageTmp = $_FILES['image_path']['tmp_name'];
      $imageName = basename($_FILES['image_path']['name']);
      $safeName = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $imageName);
      $uploadDir = 'uploads/';
      $uploadPath = $uploadDir . $safeName;

      if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

      if (move_uploaded_file($imageTmp, $uploadPath)) {
          $imgStmt = $conn->prepare("INSERT INTO map_images (map_id, image_path) VALUES (?, ?)");
          if ($imgStmt) {
              $imgStmt->bind_param("is", $mapId, $uploadPath);
              $imgStmt->execute();
              $imgStmt->close();
          }
      }
  }

  // Check for unexpected output
  $buffer = trim(ob_get_clean());
  if (!empty($buffer)) {
      echo json_encode(['status' => 'error', 'message' => 'Unexpected output', 'debug' => $buffer]);
  } else {
      echo json_encode(['status' => 'success']);
  }
  exit;
}
  
?>

<!DOCTYPE html>
<html lang="en">
<head>  <meta charset="UTF-8">
  <title>Map Editor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="../../file_css/dashboard.css">  <style>
    .content-wrapper {
        flex: 1;
        background: #f4f6f9;
    }
    
    .content {
        padding: 20px;
    }
    /* Make map fill the available vertical space, matching sidebar height */
    .main-map-container {
        flex: 1 1 0%;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    #map {
        flex: 1 1 0%;
        width: 100%;
        min-height: 400px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        position: relative;
        margin-bottom: 20px;
    }
    #addBtn {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #255d8a;
        color: white;
        border: none;
        font-size: 32px;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    #addBtn:hover {
        transform: scale(1.1);
        background: #1e4d70;
    }    .form-container {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        max-width: 500px;
        width: 95%;
        max-height: 90vh;
        overflow-y: auto;
        z-index: 1001;
    }

    .form-container.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translate(-50%, -48%); }
        to { opacity: 1; transform: translate(-50%, -50%); }
    }

    .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        backdrop-filter: blur(3px);
    }

    .overlay.active {
        display: block;
        animation: fadeBackground 0.3s ease;
    }

    @keyframes fadeBackground {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    #formHeader {
        margin-bottom: 20px;
        text-align: center;
    }

    #formHeader h2 {
        color: #255d8a;
        font-size: 1.5rem;
        font-weight: 600;
    }

    #close {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
        transition: color 0.3s;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f1f1f1;
    }

    #close:hover {
        color: #ff4444;
        background: #ffe5e5;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    textarea.form-control {
        min-height: 100px;
    }

    .addBtn {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
    }

    .btn:hover {
        background: #218838;
    }
  </style>
</head>
<body>  
    <div class="wrapper" style="display: flex; min-height: 100vh; width: 100%;">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="content-wrapper" style="flex: 1 1 0%; display: flex; flex-direction: column; background: #f4f6f9;">
       
        <section class="content pt-3 px-3 main-map-container"> 
            <div style="position:relative; flex:1 1 0%; display:flex; flex-direction:column; min-height:0;">
                <div id="map"></div>
                <button id="addBtn" title="Add New Location" style="position:absolute;top:20px;right:20px;z-index:1001;width:60px;height:60px;border-radius:50%;background:#255d8a;color:white;border:none;font-size:32px;line-height:1;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.2);display:flex;align-items:center;justify-content:center;transition:all 0.3s ease;">+</button>
                <!-- Edit and Delete Buttons -->
                <div id="editDeleteBtnGroup" style="position:absolute;top:90px;right:20px;z-index:1001;display:flex;flex-direction:column;gap:12px;">
                  <button id="editBtn" title="Edit Location" style="width:48px;height:48px;border-radius:50%;background:#ffc107;color:#fff;border:none;font-size:22px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.15);cursor:pointer;transition:all 0.2s;">
                    <i class='bx bx-edit'></i>
                  </button>
                  <button id="deleteBtn" title="Delete Location" style="width:48px;height:48px;border-radius:50%;background:#dc3545;color:#fff;border:none;font-size:22px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.15);cursor:pointer;transition:all 0.2s;">
                    <i class='bx bx-trash'></i>
                  </button>
                </div>
            </div>
            <div class="overlay"></div>
            <div class="form-container">
            <h2 class="text-2xl font-bold mb-4">Add Location</h2>
            <div id="formContainer">
  <div id="formHeader"><h2>Add New Location</h2></div>
  <form id="locationForm" enctype="multipart/form-data">
    <span id="close" title="Close">&times;</span>

        <input type="text" name="location_name" placeholder="Location Name" required>
        <div class="form-group">
  <label>Province</label>
  <select class="form-control" disabled>
    <option value="1" selected>Pangasinan</option>
  </select>
</div>

<!-- City / Municipality -->
<div class="form-group">
  <label>City / Municipality</label>
  <select id="cityMun" name="city_mun_id" class="form-control" required>
    <option value="">Select City / Municipality</option>
  </select>
</div>

<!-- Barangay -->
<div class="form-group">
  <label>Barangay</label>
  <select id="barangay" name="barangay_id" class="form-control" required>
    <option value="">Select Barangay</option>
  </select>
</div>



<div class="form-group">
  <label>Select Category</label>
  <select class="form-control" id="categorySelect" name="category" onchange="toggleCustomCategory()" required>
    <option value="">Categories</option>
    <option value="Beach">Beach</option>
    <option value="Caves">Caves</option>
    <option value="Islands">Islands</option>
    <option value="Churches">Churches</option>
    <option value="Festival">Festival</option>
    <option value="Waterfalls">Waterfalls</option>
    <option value="Caves">Caves</option>
    <option value="Bus station">Bus Station</option>
  </select>
  <input type="text" id="customCategoryInput" name="custom_category" class="form-control mt-2" placeholder="Enter custom category" style="display:none;">
</div>
        <textarea name="description" placeholder=" Description" required></textarea>
        <div class="form-group">
  <label>Latitude</label>
  <input type="text" id="lat" name="latitude" class="form-control" required>
</div>

<div class="form-group">
  <label>Longitude</label>
  <input type="text" id="lng" name="longitude" class="form-control" required>
</div>
        <input type="file" name="image_path">
        <div class="form-group full-width text-center">
  <button type="submit" class="btn btn-success small-submit">Submit Details</button>
</div>
      </form>                    
    </div>
                </div>
            </section>
        </div>
    </div>

    <div id="toastMessage"style="display:none;position:fixed; top:20px; right:20px; padding:10px 20px; background-color:#2ecc71; color:white; border-radius:5px; box-shadow:0 2px 8px rgba(0,0,0,0.2); z-index:9999;">
  <span id="toastText"></span>
</div>



<!-- Custom Popup Modal -->
<style>
  #customPopup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(190, 28, 28, 0.2);
    z-index: 2000;
    max-width: 400px;
    width: 95%;
    text-align: center;
  }
  #customPopup.active {
    display: block;
    animation: fadeIn 0.3s ease;
  }
  #customPopup .close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 24px;
    background: #f1f1f1;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    color: #666;
    transition: color 0.3s, background 0.3s;
  }
  #customPopup .close-btn:hover {
    color: #ff4444;
    background: #ffe5e5;
  }
  #customPopup .popup-image {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
  }
</style>
<div id="customPopup">
  <button onclick="closePopup()" class="close-btn">×</button>
  <img id="popupImage" src="" alt="Location" class="popup-image">
  <h3 id="popupTitle" class="popup-title"></h3>
  <p id="popupCategory" class="popup-category"></p>
  <p id="popupDescription" class="popup-description"></p>
</div>


<?php
// For location address load towns and barangay data from DB
$cityQuery = $conn->query("SELECT town_id as city_mun_id, name as city_mun_name FROM towns WHERE status = 'active'");
$barangayQuery = $conn->query("SELECT barangay_id, barangay_name, town_id as city_mun_id FROM barangay");

$cities = [];
$barangays = [];

while ($row = $cityQuery->fetch_assoc()) {
  $cities[] = $row;
}
while ($row = $barangayQuery->fetch_assoc()) {
  $barangays[] = $row;
}
?>

<script>
  const cities = <?= json_encode($cities) ?>;
  const barangays = <?= json_encode($barangays) ?>;

  // Popup modal logic
  function showPopup(data) {
    document.getElementById('popupImage').src = data.image_url || '';
    document.getElementById('popupTitle').textContent = data.location_name || '';
    // Show bus icon if category is Bus station (case-insensitive, ignore HTML)
    const cat = (data.category || '').toLowerCase().replace(/<[^>]*>/g, '').trim();
    if (cat === 'bus station') {
      document.getElementById('popupCategory').innerHTML = "<i class='bx bx-bus' style='font-size:1.5em;vertical-align:middle;'></i> Bus Station";
    } else {
      document.getElementById('popupCategory').textContent = data.category || '';
    }
    document.getElementById('popupDescription').textContent = data.description || '';
    document.getElementById('customPopup').classList.add('active');
  }
  function closePopup() {
    document.getElementById('customPopup').classList.remove('active');
  }

  document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('addBtn');
    const formContainer = document.querySelector('.form-container');
    const overlay = document.querySelector('.overlay');
    const closeBtn = document.getElementById('close');

    // Show form
    addBtn.addEventListener('click', () => {
        formContainer.classList.add('active');
        overlay.classList.add('active');
    });

    // Hide form
    function hideForm() {
        formContainer.classList.remove('active');
        overlay.classList.remove('active');
    }

    closeBtn.addEventListener('click', hideForm);
    overlay.addEventListener('click', hideForm);
  });
</script>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// --- Modal Picker for Edit/Delete ---
document.addEventListener('DOMContentLoaded', function () {
  const editBtn = document.getElementById('editBtn');
  const deleteBtn = document.getElementById('deleteBtn');
  const formContainer = document.querySelector('.form-container');
  const overlay = document.querySelector('.overlay');
  const locationForm = document.getElementById('locationForm');

  function showToast(msg) {
    const toast = document.getElementById('toastMessage');
    const toastText = document.getElementById('toastText');
    toastText.textContent = msg;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 2500);
  }

  function showModalPicker(action, callback) {
    fetch('map_editor.php?action=fetch')
      .then(res => res.json())
      .then(data => {
        if (!data.length) return showToast('No locations found.');
        let modal = document.getElementById('modalPicker');
        if (!modal) {
          modal = document.createElement('div');
          modal.id = 'modalPicker';
          modal.style.position = 'fixed';
          modal.style.top = '0';
          modal.style.left = '0';
          modal.style.width = '100vw';
          modal.style.height = '100vh';
          modal.style.background = 'rgba(0,0,0,0.4)';
          modal.style.zIndex = '3000';
          modal.style.display = 'flex';
          modal.style.alignItems = 'center';
          modal.style.justifyContent = 'center';
          document.body.appendChild(modal);
        }
        modal.innerHTML = `<div style=\"background:#fff;padding:24px 18px;border-radius:10px;max-width:400px;width:95%;max-height:80vh;overflow:auto;box-shadow:0 4px 24px rgba(0,0,0,0.18);\">
          <h3 style='margin-bottom:16px;font-size:1.2em;'>Select Location to ${action}</h3>
          <ul style='list-style:none;padding:0;margin:0;'>
            ${data.map(loc => `<li style='margin-bottom:10px;'><button data-mapid='${loc.map_id}' style='width:100%;text-align:left;padding:8px 12px;border-radius:6px;border:1px solid #eee;background:#f8f8f8;cursor:pointer;'>
              <b>${loc.location_name}</b><br><span style='font-size:0.95em;color:#666;'>${loc.category}</span>
            </button></li>`).join('')}
          </ul>
          <button id='closeModalPicker' style='margin-top:12px;padding:6px 18px;border-radius:6px;background:#eee;border:none;cursor:pointer;'>Cancel</button>
        </div>`;
        modal.style.display = 'flex';
        modal.querySelectorAll('button[data-mapid]').forEach(btn => {
          btn.onclick = () => {
            const mapId = btn.getAttribute('data-mapid');
            const loc = data.find(l => l.map_id == mapId);
            modal.style.display = 'none';
            callback(loc);
          };
        });
        document.getElementById('closeModalPicker').onclick = () => { modal.style.display = 'none'; };
      });
  }

  // Edit button: choose location to edit
  editBtn.addEventListener('click', function () {
    showModalPicker('edit', function(loc) {
      formContainer.classList.add('active');
      overlay.classList.add('active');
      document.getElementById('formHeader').innerHTML = '<h2>Edit Location</h2>';
      locationForm['location_name'].value = loc.location_name;
      document.getElementById('lat').value = loc.latitude;
      document.getElementById('lng').value = loc.longitude;
      locationForm['description'].value = loc.description;
      document.getElementById('cityMun').value = loc.city_mun_id || '';
      const event = new Event('change');
      document.getElementById('cityMun').dispatchEvent(event);
      setTimeout(() => {
        document.getElementById('barangay').value = loc.barangay_id || '';
      }, 100);
      const catSelect = document.getElementById('categorySelect');
      let found = false;
      for (let i = 0; i < catSelect.options.length; i++) {
        if (catSelect.options[i].value === loc.category) {
          catSelect.selectedIndex = i;
          found = true;
          break;
        }
      }
      if (!found) {
        catSelect.value = 'Other';
        document.getElementById('customCategoryInput').value = loc.category;
        document.getElementById('customCategoryInput').style.display = 'block';
      } else {
        var customCatInput = document.getElementById('customCategoryInput');
        if (customCatInput) customCatInput.style.display = 'none';
      }
      locationForm['image_path'].required = false;
      let mapIdInput = document.getElementById('edit_map_id');
      if (!mapIdInput) {
        mapIdInput = document.createElement('input');
        mapIdInput.type = 'hidden';
        mapIdInput.id = 'edit_map_id';
        mapIdInput.name = 'edit_map_id';
        locationForm.appendChild(mapIdInput);
      }
      mapIdInput.value = loc.map_id;
      locationForm.setAttribute('data-edit-mode', 'true');
    });
  });

  // Delete button: choose location to delete
  deleteBtn.addEventListener('click', function () {
    showModalPicker('delete', function(loc) {
      // Custom confirm dialog
      let confirmDiv = document.createElement('div');
      confirmDiv.style.position = 'fixed';
      confirmDiv.style.top = '0';
      confirmDiv.style.left = '0';
      confirmDiv.style.width = '100vw';
      confirmDiv.style.height = '100vh';
      confirmDiv.style.background = 'rgba(0,0,0,0.4)';
      confirmDiv.style.zIndex = '4000';
      confirmDiv.style.display = 'flex';
      confirmDiv.style.alignItems = 'center';
      confirmDiv.style.justifyContent = 'center';
      confirmDiv.innerHTML = `<div style=\"background:#fff;padding:24px 18px;border-radius:10px;max-width:340px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,0.18);text-align:center;\">
        <div style='font-size:1.25em;margin-bottom:24px;'>Are you sure you want to delete <b>${loc.location_name}</b>?</div>
        <button id='confirmDelete' style='background:#dc3545;color:#fff;padding:12px 32px;font-size:1.1em;border:none;border-radius:7px;margin-right:18px;cursor:pointer;'>Delete</button>
        <button id='cancelDelete' style='background:#eee;padding:12px 32px;font-size:1.1em;border:none;border-radius:7px;cursor:pointer;'>Cancel</button>
      </div>`;
      document.body.appendChild(confirmDiv);
      document.getElementById('confirmDelete').onclick = () => {
        confirmDiv.remove();
        fetch('map_editor.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'delete_map_id=' + encodeURIComponent(loc.map_id)
        })
          .then(res => res.json())
          .then(response => {
            if (response.status === 'success') {
              showToast('Location deleted successfully!');
              setTimeout(() => location.reload(), 1000);
            } else {
              showToast('Delete failed: ' + response.message);
            }
          })
          .catch(() => showToast('Error deleting location.'));
      };
      document.getElementById('cancelDelete').onclick = () => {
        confirmDiv.remove();
      };
    });
  });

  // On form submit, check if edit mode
  locationForm.addEventListener('submit', function (e) {
    if (locationForm.getAttribute('data-edit-mode') === 'true') {
      e.preventDefault();
      const formData = new FormData(locationForm);
      formData.append('edit_map_id', document.getElementById('edit_map_id').value);
      fetch('map_editor.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(response => {
          if (response.status === 'success') {
            showToast('Location updated successfully!');
            setTimeout(() => location.reload(), 1000);
          } else {
            showToast('Update failed: ' + response.message);
          }
        })
        .catch(() => showToast('Error updating location.'));
      // Reset edit mode
      locationForm.removeAttribute('data-edit-mode');
      document.getElementById('formHeader').innerHTML = '<h2>Add New Location</h2>';
      document.getElementById('edit_map_id').remove();
    }
  }, true);
});
</script>
<script src="../file_js/script.js"></script>
</body>
</html>