<?php
// navbar.php
// This file contains the HTML code for the navigation bar of the TripKo Pangasinan website.
function renderNavbar() {    echo '<link rel="stylesheet" href="../../file_css/navbar.css">';
    echo '<nav class="navbar">';
    echo '    <div class="nav-content">';
    echo '        <div class="logo">';
    echo '            TripKo Pangasinan';
    echo '        </div>';
    echo '        <div class="nav-links">';
    echo '            <a href="homepage.php"><i class="bx bxs-home"></i> Home</a>';
    echo '            <div class="nav-dropdown">';
    echo '                <a href="#"><i class="bx bxs-map-alt"></i> Places to Go</a>';
    echo '                <div class="nav-dropdown-content">';
    echo '                    <a href="user side/places-to-go.php">Beaches</a>';
    echo '                    <a href="user side/islands-to-go.php">Islands</a>';
    echo '                    <a href="user side/waterfalls-to-go.php">Waterfalls</a>';
    echo '                    <a href="user side/caves-to-go.php">Caves</a>';
    echo '                    <a href="user side/churches-to-go.php">Churches</a>';
    echo '                    <a href="user side/festivals-to-go.php">Festivals</a>';
    echo '                </div>';
    echo '            </div>';
    echo '            <a href="things-to-do.php"><i class="bx bxs-calendar-star"></i> Things to Do</a>';
    echo '            <a href="routeFinder.php"><i class="bx bxs-bus"></i> Route Finder</a>';
    echo '            <a href="https://seepangasinan.com/directory/"><i class="bx bxs-book-content"></i> Directory</a>';
    echo '            <a href="user side/tourist_capacity.php"><i class="bx bxs-check-circle"></i> Tourist Spot Status</a>';            echo '            <a href="about-us.php"><i class="bx bxs-info-circle"></i> About Us</a>';
    echo '            <a href="contact-us.php"><i class="bx bxs-phone"></i> Contact Us</a>';
    echo '            <a href="/tripko-system/tripko-backend/logout.php"><i class="bx bx-log-out"></i> Logout</a>';
    echo '        </div>';
    echo '        <div class="menu-btn">';
    echo '            <i class="bx bx-menu"></i>';
    echo '        </div>';
    echo '    </div>';
    echo '</nav>';
}
?>