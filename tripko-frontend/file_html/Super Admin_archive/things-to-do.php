<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/tripko-system/tripko-backend/check_session.php');

// Check if user is logged in and redirect admin to dashboard
if (!isLoggedIn()) {
    header("Location: SignUp_LogIn_Form.php");
    exit();
} elseif (isAdmin()) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripKo Pangasinan - Home</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../file_css/userpage.css">
    <link rel="stylesheet" href="../file_css/navbar.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Cedarville+Cursive&display=swap');
     * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'incolosoLata';
        }

        body {
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background-color: rgba(37, 93, 138, 0.95);
            padding: 0%;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            backdrop-filter: blur(5px);
        }

        .nav-content {
            max-width: 1500px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: nowrap;
            padding: 0 2rem;
            margin-right: 15px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            white-space: nowrap;
        }

        .nav-links a:hover {
            color: #ffd700;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .menu-btn {
            display: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Dropdown styles */
        .nav-dropdown {
            position: relative;
            display: inline-block;
        }

        .nav-dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: rgba(37, 93, 138, 0.95);
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 4px;
            z-index: 1001;
        }

        .nav-dropdown-content.show {
            display: block;
        }

        .nav-dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }

        .nav-dropdown-content a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffd700;
        }

        /* Dropdown arrow */
        .nav-dropdown > a::after {
            content: '▼';
            font-size: 0.8em;
            margin-left: 5px;
            display: inline-block;
            transition: transform 0.3s;
        }

        .nav-dropdown.active > a::after {
            transform: rotate(180deg);
        }

        @media (max-width: 1024px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #255D8A;
                flex-direction: column;
                padding: 1rem;
                text-align: center;
                gap: 0.5rem;
            }

            .nav-links.active {
                display: flex;
            }

            .nav-links a {
                width: 100%;
                padding: 0.75rem;
            }

            .menu-btn {
                display: block;
            }
        }        
        
        .hero_content {
            margin-top: 9px;
            padding: 20px;
            text-align: center;
            color: #255D8A;
        }

        .title-row {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin-bottom: 20px;
            padding-top: 20px;
        }

        .hero_title {
            color: #255D8A;
            margin: 0;
            font-size: 2.0em;
            font-family: 'Cedarville Cursive', cursive;
            text-align: center;
        }

        .scroll-container {
            display: flex;
            overflow-x: auto;
            gap: 20px;
            padding: 20px;
            scroll-behavior: smooth;
        }    

        .scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .scroll-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .scroll-container::-webkit-scrollbar-thumb {
            background-color: #255D8A;
            border-radius: 20px;
        }

        .back-button {
            position: absolute;
            left: 40px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 10px 20px;
            background-color: #255D8A;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #1e4d70;
        }
        
        .container {
                margin: 0rem;
                padding: 2rem;
        }

        .title-section {
                text-align: center;
                margin-bottom: 0;
        }

        .subtitle {
                color: #255d8a;
                font-size: 1.1em;
                max-width: 600px;
                margin: 0 auto;
        }

        .activity-card {
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
                border: 2px solid #255D8A;
        }

        .activity-card:hover {
                transform: translateY(-5px);
        }

        .card-image {
                height: 200px;
                overflow: hidden;
        }

        .card-image img {
                width: 100%;
                height: 1000%;
                object-fit: cover;
                transition: transform 0.5s ease;
        }

        .activity-card:hover .card-image img {
                transform: scale(1.05);
        }

        .card-content {
                padding: 1.5rem;
        }

        .card-content h3 {
                color: #255D8A;
                font-size: 1.25rem;
                margin-bottom: 0.75rem;
        }

        .card-content p {
                color: #666;
                line-height: 1.6;
                margin-bottom: 1rem;
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
        }

        .learn-more {
                display: inline-block;
                background: #255D8A;
                color: white;
                padding: 0.5rem 1.25rem;
                border-radius: 4px;
                text-decoration: none;
                transition: background 0.3s ease;
            }

        .learn-more:hover {
                background: #1e4d70;
            }

        /* Title Styling (matches your admin) */
        .user-main-title {
        color: #255D8A;
        margin: 1.5rem;
        font-size: 2.0em;
        font-family: 'Cedarville Cursive', cursive;
        width: 100%;
        text-align: center;
        }

        .header-section {
        display: flex;
        align-items: center;
        padding: 1rem 2rem;
        background-color: white;
        width: 100%;
        margin-top: 60px;  /* Add margin-top to account for fixed navbar */
        position: relative;
      }
        
        .user-tours-container {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'inconsolata';
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        }

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 3rem;
            color: #255D8A;
            grid-column: 1 / -1;
        }
        .fa-spin {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Tours Grid (matches admin card style) */
        .user-tours-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
        padding: 2rem;
        }

        .hero_content {
            margin: 2rem;
            padding: 0;
        }

        /* Individual Tour Card */
        .tour-card {
        background: white;
        border: 2px solid #255D8A;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        }

        .tour-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .card-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .card-image img {
            width: 400px;;
            height: 350px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .tour-card:hover .card-image img {
            transform: scale(1.03);
        }

        .card-content {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .card-content h3 {
            color: #255D8A;
            font-size: 18px;;
            margin-bottom: 0.50rem;
            font-weight: medium;
        }

        .card-content p {
            color: #255d8a;
            margin-bottom: 0.75rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .fee-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #255d8a;
            font-size: 1rem;
            margin-bottom: 1rem;
            background: transparent;
       }

       .itinerary-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            color: #255d8a;
            font-size: 1.25rem;
            margin-bottom: 0.25em;
        }

        .itinerary-info h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            color: #255D8A;
            font-weight: 600;
        }


       .fee-info h3,
       .itinerary-info h3 {
        font-size: 1rem;
        color: #255D8A;
        font-weight: 600;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: left;
        gap: 0.5
    }

    .fee-info p {
        font-size: 1rem;
        color: #255d8a;
        font-weight: 600;
    }

    .tour-destination strong {
        font-size: 1rem;
        font-weight: 600;
        margin-right: 0.5rem;
    }

        .view-btn {
            margin-top: auto;
            background: #255D8A;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-weight: medium;
            font-size: 14px;
        }
        .view-btn:hover {
            background: #1e4d70;
        }

        /* Detailed View Styling */
        .itinerary-detail-view {
            max-width: 1000px;
            margin: 1rem auto;
            padding: 1rem;
            font-family: 'inconsolota';
            color: #255d8a;
        }
        .detail-header {
            margin-bottom: 1rem;
        }

        .detail-title {
            color: #255D8A;
            font-size: 1.35rem;
            margin-top: 0rem;
            font-weight: 'regular';
            text-align:left;
        }
        .detail-image {
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }
        .detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .detail-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .tour-destination, 
        .fee-info, 
        .itinerary-info {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding-left: 1rem;
            margin-bottom: 0.25rem;
        }

        .detail-content, 
        .detail-content p, 
        .detail-content medium,
        .detail-content .tour-destination,
        .detail-content .fee-info,
        .detail-content .itinerary-info,
        .detail-content .stops-list {
                font-size: 1rem;
                font-weight: 600;  /* medium weight */
                color: #255d8a;
        }

        .detail-content i {
            font-size: 1rem;
            color: #255d8a;
            margin-top: 0.2rem;
        }

        .detail-content .stops-list {
            margin-left: 2.5rem;
            line-height: 1.6;
        }

        .detail-content .stops-list li {
            margin-bottom: 0.75rem;
        }

        .detail-description {
            line-height: 1.6;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }
        .environmental-fee {
            color: #255d8a;
            font-weight: 'regular';
            margin-bottom: 1.5rem;
            display: block;
            font-size: 1.25rem;
        }
        .stops-title {
            color: #255D8A;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        .stops-list {
            padding-left: 1.5rem;
        }
        .stops-list li {
            margin-bottom: 0.75rem;
            color: #255d8a;
            line-height: 1.6;
        }

        /* Error State */
        .error-state {
            text-align: center;
            padding: 3rem;
            color: #e74c3c;
            grid-column: 1 / -1;
        }       
        
        .tour-destination {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            color: #255d8a;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .tour-destination, .fee-info .itinerary-info {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding-left: 2.5rem;
            color: #255d8a;
            font-size: 1rem;
            margin-bottom: 0.25rem;
            padding: 0.5rem ;
        }

        .tour-destination i, .fee-info i, .itinerary-info {
            font-size: 1rem;
            color: #255d8a;
            min-width: 24px;
            text-align: left;
        }


</style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile menu functionality
            const menuBtn = document.querySelector('.menu-btn');
            const navLinks = document.querySelector('.nav-links');
            
            menuBtn?.addEventListener('click', () => {
                navLinks?.classList.toggle('active');
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.nav-content')) {
                    navLinks?.classList.remove('active');
                }
            });

            // Background image rotation
            const backgrounds = document.querySelectorAll('.hero-background');
            const locationText = document.getElementById('currentLocation');
            let currentIndex = 0;

            // Initialize the first background
            backgrounds[0].classList.add('active');
            locationText.textContent = backgrounds[0].getAttribute('data-title');

            function rotateBackgrounds() {
                // Remove active class from current background
                backgrounds[currentIndex].classList.remove('active');
                
                // Move to next background
                currentIndex = (currentIndex + 1) % backgrounds.length;
                
                // Add active class to new background and update location
                backgrounds[currentIndex].classList.add('active');
                locationText.textContent = backgrounds[currentIndex].getAttribute('data-title');
            }

            // Start the rotation
            setInterval(rotateBackgrounds, 5000);

            // Category filtering and slider functionality
            const filterButtons = document.querySelectorAll('.filter-btn');
            const allCards = document.querySelectorAll('.card');
            const slider = document.querySelector('.cards-slider');
            const prevBtn = document.querySelector('.slider-nav.prev');
            const nextBtn = document.querySelector('.slider-nav.next');
            
            let currentSlide = 0;
            const cardsPerSlide = window.innerWidth >= 1200 ? 3 : window.innerWidth >= 768 ? 2 : 1;
            const totalSlides = Math.ceil(allCards.length / cardsPerSlide);

            // Filter functionality
            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');

                    const category = button.getAttribute('data-category');
                    
                    allCards.forEach(card => {
                        if (category === 'all' || card.getAttribute('data-category') === category) {
                            card.classList.remove('hidden');
                        } else {
                            card.classList.add('hidden');
                        }
                    });

                    // Reset slider position when filtering
                    currentSlide = 0;
                    updateSliderPosition();
                });
            });

            // Slider functionality
            function updateSliderPosition() {
                const cardWidth = allCards[0].offsetWidth;
                const gap = 32; // 2rem gap
                slider.style.transform = `translateX(-${currentSlide * (cardWidth + gap) * cardsPerSlide}px)`;
                
                // Update navigation buttons
                prevBtn.classList.toggle('hidden', currentSlide === 0);
                nextBtn.classList.toggle('hidden', currentSlide >= totalSlides - 1);
            }

            function showSliderNavigation() {
                if (allCards.length > cardsPerSlide) {
                    nextBtn.classList.remove('hidden');
                }
            }

            nextBtn.addEventListener('click', () => {
                if (currentSlide < totalSlides - 1) {
                    currentSlide++;
                    updateSliderPosition();
                }
            });

            prevBtn.addEventListener('click', () => {
                if (currentSlide > 0) {
                    currentSlide--;
                    updateSliderPosition();
                }
            });

            // Initialize slider
            showSliderNavigation();

            // Handle window resize
            window.addEventListener('resize', () => {
                currentSlide = 0;
                updateSliderPosition();
            });

            // Handle image errors
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    this.src = '../images/placeholder.jpg';
                    this.alt = 'Image not available';
                });
            });
        });

        // Spot management functions
        async function editSpot(spot) {
            window.location.href = `tourist_spot.php?edit=${spot.spot_id}`;
        }

        async function deleteSpot(spotId, spotName) {
            if (confirm(`Are you sure you want to delete the tourist spot "${spotName}"?`)) {
                try {
                    const response = await fetch(`../../../tripko-backend/api/tourist_spot/delete.php?spot_id=${spotId}`, {
                        method: 'DELETE'
                    });
                    const data = await response.json();
                    if (data.success) {
                        alert('Tourist spot deleted successfully!');
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to delete tourist spot');
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    alert('Error: ' + error.message);
                }
            }
        }
    </script>
</head>
<body>
<?php include_once 'navbar.php'; renderNavbar(); ?>

<section class="hero_content">
<!-- User Tours Display -->
 <div class="header-section">
    <a href="javascript:history.back()" class="back-button">
        <i class="bx bx-arrow-back"></i> Back
    </a>
    <h1 class="user-main-title">Wander and Experience More</h1>
</div>
  <div class="user-tours-grid" id="userToursGrid">
    <!-- Tours will load here automatically -->
    <div class="loading-state">
      <i class="fas fa-compass fa-spin"></i>
      <p>Loading available tours...</p>
    </div>
  </div>
</div>

<!-- Detailed Itinerary View -->
<div class="itinerary-detail-view" id="itineraryDetailView" style="display:none;">
  <!-- Content will be inserted here by JavaScript -->
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
  const toursGrid = document.getElementById('userToursGrid');
  const detailView = document.getElementById('itineraryDetailView');

  // Load tours from the same API endpoint used in itineraries.html
    fetch('../../../tripko-backend/api/itineraries/read.php')
    .then(response => {
      if (!response.ok) throw new Error('Network response failed');
      return response.json();
    })
    .then(data => {
      if (data.records && data.records.length > 0) {
        displayTours(data.records);
      } else {
        showMessage('No tours available yet', 'info');
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
      showMessage('Failed to load tours. Please try again later.', 'error');
    });

  // Display all tours in grid format
  function displayTours(tours) {
    toursGrid.innerHTML = '';
    
    tours.forEach(tour => {
        const card = document.createElement('div');
        card.className = 'tour-card';
        card.innerHTML = `
            <div class="card-image">
                <img src="${getImageUrl(tour.image_path)}" alt="${tour.name}">
            </div>
            <div class="card-content">
                <h3>${tour.name}</h3>
                <p class="tour-destination">
                    <i class='bx bxs-map-pin'></i> 
                    ${tour.destination_name || tour.destination}
                </p>
                <button class="view-btn" data-id="${tour.itinerary_id}">View Itinerary</button>
            </div>
        `;
        toursGrid.appendChild(card);
    });

    // Add click handlers to all view buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tourId = this.getAttribute('data-id');
            showTourDetails(tourId);
        });
    });
}

  // Show detailed view for a specific tour
  function showTourDetails(tourId) {
    // Fetch the specific tour details (using same endpoint as admin)
    fetch('../../../tripko-backend/api/itineraries/read.php')
      .then(response => response.json())
      .then(data => {
        const tour = data.records.find(t => t.itinerary_id == tourId);
        if (tour) {
          renderDetailView(tour);
        } else {
          showMessage('Tour details not found', 'error');
        }
      })
      .catch(error => {
        console.error('Error loading tour details:', error);
        showMessage('Failed to load tour details', 'error');
      });
  }
  // Render the detailed view
  function renderDetailView(tour) {
    detailView.innerHTML = `
        <div class="detail-header">
            <h2 class="detail-title">${tour.name}</h2>
        </div>
        <div class="detail-image">
            <img src="${getImageUrl(tour.image_path)}" alt="${tour.name}">
        </div>
        <div class="detail-content">
            <p class="tour-destination">
                <i class='bx bxs-map-pin'></i>
                <medium>Destination:</medium> ${tour.destination_name || tour.destination}
            </p>
            ${tour.environmental_fee ? `
                <div class="fee-info">
                    <h3><i class='bx bx-money'></i> Environmental Fee</h3>
                    <p>₱${tour.environmental_fee}</p>
                </div>
            ` : ''}
            <div class="itinerary-info">
                <h3><i class='bx bxs-map'></i> Itinerary Details</h3>
                <div class="stops-list">
                    ${formatItineraryStops(tour.description)}
                </div>
            </div>
        </div>
    `;
    
    toursGrid.style.display = 'none';
    detailView.style.display = 'block';
}

  // Helper function to extract numbered stops from description
  function extractStopsFromDescription(description) {
    return description.split('\n')
      .filter(line => line.match(/^\d+[\.\)]\s/))
      .map(line => line.replace(/^\d+[\.\)]\s/, '').trim());
  }

  // Helper function to get proper image URL
  function getImageUrl(imagePath) {
    if (!imagePath) return 'https://placehold.co/600x400?text=Tour+Image';
    return `/TripKo-System/uploads/${imagePath}`;
  }

  // Helper function to show messages
  function showMessage(message, type) {
    toursGrid.innerHTML = `
      <div class="${type === 'error' ? 'error-state' : 'loading-state'}">
        <i class="fas ${type === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
        <p>${message}</p>
      </div>
    `;
  }
});

// Add this helper function if not already present
function formatItineraryStops(description) {
    const stops = description.split('\n')
        .filter(line => line.trim())
        .map(line => line.trim());
        
    if (stops.length === 0) {
        return '<p>Detailed itinerary will be provided upon booking.</p>';
    }

    return `<ol>${stops.map(stop => `<li>${stop}</li>`).join('')}</ol>`;
}
</script>     

      </section>
</body>
</html>