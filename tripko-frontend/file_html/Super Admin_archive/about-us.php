<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/tripko-system/tripko-backend/check_session.php');

// Redirect logic
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TripKo Pangasinan</title>

  <!-- Icons and Stylesheets -->
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../file_css/userpage.css" />
  <link rel="stylesheet" href="../file_css/navbar.css" />

  <style>
    body {
      margin: 0;
      font-family: 'inconsolata';
      background-color: #f7f9fc;
      color: #333;
      line-height: 1.6;
    }

    .about-container {
      max-width: 900px;
      margin: 40px auto;
      padding: 20px 30px;
      background: #ffffff;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    h1 {
      text-align: center;
      color: #255D84;
      margin-bottom: 20px;
    }    
    
    .intro, .closing {
      font-size: 1.2em;
      color: #255D84;  /* Changed color to match the heading color */
      font-weight: bold;
      margin-top: 10px;
    }

    h2 {
      color: #255D84;
      font-size: 1.2em;
      margin-top: 30px;
    }

    ul {
      padding-left: 20px;
    }

    ul li {
      margin-bottom: 10px;
    }

    @media (max-width: 600px) {
      .about-container {
        padding: 15px;
      }

      h1 {
        font-size: 1.6em;
      }

      .intro, .closing {
        font-size: 1em;
      }
    }

    
  </style>
</head>
<body>

  <?php include_once 'navbar.php'; renderNavbar(); ?>
  <section class="hero_content">
  <div class="about-container">
    <h1>About Us</h1>
    <p class="intro"><strong>Welcome to TripKo Pangasinan – Your Travel Buddy in the Heart of the North!</strong></p>
    
    <p>TripKo Pangasinan is a tourism and travel assistance system designed to help locals and tourists explore the beautiful province of Pangasinan with ease and confidence. Whether you're looking for top tourist destinations, must-try experiences, local food spots, or want to estimate your travel fare — TripKo has got you covered.</p>

    <p>Our mission is to promote Pangasinan’s rich culture, natural wonders, and hidden gems while making travel convenient and accessible. With our user-friendly platform, you can plan your trip, discover exciting locations, and get helpful travel tips — all in one place.</p>

    <h2>What We Offer:</h2>
    <ul>
      <li>Curated list of <strong>Popular Destinations</strong> and tourist spots</li>
      <li><strong>Fixed Fare Estimator</strong> for planning your budget</li>
      <li>Suggested <strong>Itineraries</strong> and updated travel info</li>
      <li><strong>Route Finder</strong> and real-time tracking of transportation</li>
    </ul>

    <p>TripKo Pangasinan is more than just a system — it’s our way of sharing the beauty and culture of our province with the world.</p>

    <p class="closing"><strong>So whether you're a traveler or a local adventurer — tara na, TripKo na ‘to!</strong></p>
  </div>
  </section>
  
</body>
</html>
