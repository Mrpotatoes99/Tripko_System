<?php
session_start();
require_once '../../../tripko-backend/check_session.php';

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
    <style>
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

        .nav-dropdown:hover .nav-dropdown-content {
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
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding-top: 60px;
            z-index: 1; /* Ensure it's below navbar */
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: all 1.5s ease-in-out;
            transform: scale(1.1);
        }

        .hero-background.active {
            opacity: 1;
            transform: scale(1);
        }

        .hero-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.3));
        }

        .hero-content {
            max-width: 900px;
            width: 90%;
            padding: 2rem;
            position: relative;
            z-index: 3;
            border-radius: 20px;
        }

        .hero-content h1 {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .cta-button {
            display: inline-block;
            background-color: #255D8A;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            border: #255d8a; /* Add golden stroke/border */
        }

        .cta-button:hover {
            background-color: #1e4d70;
            transform: translateY(-2px);
            border-color: #255D8A; /* Change border color on hover */
        }

        .search-container {
            margin: 2.5rem auto;
            max-width: 700px;
            position: relative;
            background: white;
            border-radius: 50px;
            box-shadow: #255D8A;
            border: #255D8A;
        }

        .search-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #255D8A;
            font-size: 1.2rem;
        }

        .search-container input {
            width: 100%;
            padding: 15px 20px 15px 45px;
            border: none;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            box-shadow: #255D8A;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 15px;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: none;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
        }

        .search-result-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-result-item:hover {
            background-color: #f5f5f5;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #255D8A;
            font-size: 1.2rem;
        }
    
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.1rem;
                padding: 0 1rem;
            }

            .search-container {
                width: 90%;
            }
        }

        /* Featured Section */
        .featured-section {
            padding: 4rem 1rem;
            background-color: #f9f9f9;
        }

        .section-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #255D8A;
        }

        .category-filters {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            padding: 0 1rem;
        }

        .filter-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            border: 2px solid #255D8A;
            color: #255D8A;
            font-weight: 500;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-btn:hover, .filter-btn.active {
            background: #255D8A;
            color: white;
        }

        .filter-btn.active {
            color: #255D8A;
            background: transparent;
            border: 2px solid #255D8A;
        }

        .cards-container {
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            overflow: hidden;
            padding: 1rem;
        }

        .cards-slider {
            display: flex;
            transition: transform 0.5s ease;
            gap: 2rem;
        }

        .card {
            flex: 0 0 calc(33.333% - 1.33rem);
            min-width: calc(33.333% - 1.33rem);
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            opacity: 1;
        }

        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: #255D8A;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .slider-nav:hover {
            background: #1e4d70;
            transform: translateY(-50%) scale(1.1);
        }

        .slider-nav.prev {
            left: 0;
        }

        .slider-nav.next {
            right: 0;
        }

        .slider-nav.hidden {
            display: none;
        }

        @media (max-width: 1200px) {
            .card {
                flex: 0 0 calc(50% - 1rem);
                min-width: calc(50% - 1rem);
            }
        }

        @media (max-width: 768px) {
            .card {
                flex: 0 0 calc(100%);
                min-width: calc(100%);
            }
        }        .card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            object-position: center;
        }

        .card-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .category-tag {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #255D8A;
            margin-bottom: 0.75rem;
        }

        .card p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            flex: 1;
        }

        .contact-info {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            margin-bottom: 1rem;
        }

        .cta-button {
            display: inline-block;
            background-color: #255D8A;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
        }

        .cta-button:hover {
            background-color: #1e4d70;
            transform: translateY(-2px);
        }

        .main-cta-button {
            display: inline-block;
            background-color: #255D8A;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }

        .main-cta-button:hover {
            background-color: #255D8A;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
            }
        }

        /* Placeholder loading animation */
        .placeholder-animation {
            height: 20px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            margin: 10px 0;
            border-radius: 4px;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        .location-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: #ffd700;
            font-size: 1.2rem;
            font-weight: 500;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .location-indicator i {
            font-size: 1.4rem;
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
            setInterval(rotateBackgrounds, 5000);            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');
            
            // Handle search input on Enter key press
            searchInput?.addEventListener('keypress', async function(e) {
                if (e.key !== 'Enter') return;
                
                const query = this.value.trim().toLowerCase();
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }                try {
                    // Only perform search if there's a valid query
                    const response = await fetch('../../../tripko-backend/api/tourist_spot/search.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ search: query })
                    });

                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }

                    const data = await response.json();
                    
                    // Format and display search results
                    if (data.records && data.records.length > 0) {                        // Check for results that match the search query
                        const matchingResults = data.records.filter(spot =>
                            spot.name.toLowerCase().includes(query)
                        );
                        
                        if (matchingResults.length > 0) {
                            // Display matching results
                            const resultsHtml = matchingResults.map(spot => `
                                <div class="search-result-item" data-spot-id="${spot.spot_id}" data-town-id="${spot.town_id}">
                                    <div class="spot-image">
                                        <img src="${spot.image_path ? '../uploads/' + spot.image_path : '../uploads/placeholder.jpg'}"
                                        alt="${spot.name}"
                                        onerror="this.src='../uploads/placeholder.jpg'"
                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                </div>
                                    <div>
                                        <div style="color: #255d8a; font-weight: 600;">${spot.name}</div>
                                        <small style="color: #255d8a;">${spot.town_name}</small>
                                    </div>
                                </div>
                            `).join('');
                            
                            searchResults.innerHTML = resultsHtml;
                            
                            // Add click handlers for results
                            document.querySelectorAll('.search-result-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    window.location.href = `user side/municipality.php?id=${this.dataset.townId}&spot=${this.dataset.spotId}`;
                                });
                            });
                        } else {
                            // Show "No results found" only when there are no matches
                            searchResults.innerHTML = '<div class="search-result-item" style="color: #255d8a;">No results found</div>';
                        }
                        searchResults.style.display = 'block';
                    } else {
                        // Clear results if no query is entered
                        searchResults.innerHTML = '';
                        searchResults.style.display = 'none';                    }} catch (error) {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="search-result-item" style="color: #ff0000;">No result found. Please try again.</div>';
                    searchResults.style.display = 'block';
                }});

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchResults?.contains(e.target) && e.target !== searchInput) {
                    searchResults.style.display = 'none';
                }
            });

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
                    updateSliderPosition();                });
            });
            
            // Slider functionality
            function updateSliderPosition() {
                if (!allCards || allCards.length === 0) return;
                
                const cardWidth = allCards[0].offsetWidth;
                if (!cardWidth) return;
                
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
    </script>
</head>
<body>
    <?php include_once 'navbar.php'; renderNavbar(); ?>

    <section class="hero">
        <?php
        // Array of featured destination images
        $backgroundImages = [
            ['url' => '../images/hundred-islands.jpg', 'title' => 'Hundred Islands'],
            ['url' => '../images/patar-white-beach.jpg', 'title' => 'Patar White Beach'],
            ['url' => '../images/cathedral-joseph.jpg', 'title' => 'Cathedral of Saint Joseph'],
            ['url' => '../images/bolinao-falls.jpg', 'title' => 'Bolinao Falls'],
            ['url' => '../images/enchanted-cave.jpg', 'title' => 'Enchanted Cave'],
            ['url' => '../images/abagatanen-beach.jpg', 'title' => 'Abagatanen Beach'],
            ['url' => '../images/agno-beach.jpg', 'title' => 'Agno Beach']
        ];

        foreach ($backgroundImages as $index => $image) {
            echo '<div class="hero-background ' . ($index === 0 ? 'active' : '') . '" 
                      style="background-image: url(\'' . $image['url'] . '\')" 
                      data-title="' . $image['title'] . '"></div>';
        }
        ?>
        
        <div class="hero-content">
            <h1>Discover Pangasinan</h1>
            <p>Experience the breathtaking natural wonders, rich cultural heritage, and unforgettable adventures in the heart of Luzon.</p>
            <div class="location-indicator">
                <i class='bx bxs-map'></i>
                <span id="currentLocation">Hundred Islands</span>
            </div>
            <div class="search-container">
                <i class='bx bx-search search-icon'></i>
                <input type="text" id="searchInput" placeholder="Search tourist spots...">
                <div id="searchResults" class="search-results"></div>
            </div>
        </div>
    </section>

    <section class="featured-section">
        <h2 class="section-title">Popular Destinations</h2>
        
        <div class="category-filters">
            <a href="user side/places-to-go.php" class="filter-btn">Beaches</a>
            <a href="user side/islands-to-go.php" class="filter-btn">Islands</a>
            <a href="user side/waterfalls-to-go.php" class="filter-btn">Waterfalls</a>
            <a href="user side/caves-to-go.php" class="filter-btn">Caves</a>
            <a href="user side/churches-to-go.php" class="filter-btn">Churches</a>
            <a href="user side/festivals-to-go.php" class="filter-btn">Festivals</a>
        </div>       
            </div>
        </div>
    </section>
</body>
</html>
