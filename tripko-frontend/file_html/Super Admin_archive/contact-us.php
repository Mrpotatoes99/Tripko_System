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
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .contact-section {
      text-align: center;
      padding: 80px 20px 40px;
      background: #255d84;
      color: white;
    }

    .contact-section h1 {
      font-size: 36px;
      margin-bottom: 10px;
    }

    .contact-section p {
      font-size: 16px;
      margin-top: 0;
      opacity: 0.9;
    }

    .contact-cards {
      display: flex;
      justify-content: center;
      gap: 30px;
      padding: 40px 20px;
      flex-wrap: wrap;
      background-color: #f9f9f9;
    }

    .contact-card {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      width: 300px;
      padding: 30px 20px;
      text-align: center;
    }

    .contact-card h3 {
      margin-top: 10px;
      margin-bottom: 10px;
      font-size: 20px;
      color: #333;
    }

    .contact-card p {
      font-size: 14px;
      color: #666;
    }

    .contact-card a {
      display: inline-block;
      margin-top: 15px;
      font-weight: bold;
      color: #255d84;
      text-decoration: none;
    }

    .support-button {
      display: inline-block;
      margin-top: 15px;
      padding: 10px 20px;
      background-color: #ffffff;
      color: white;
      font-weight: bold;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      cursor: pointer;
    }

    .support-button:hover {
      background-color: #ffffff;
    }
  </style>

</head>
<body>

  <?php include_once 'navbar.php'; renderNavbar(); ?>

  <section class="hero_content">
  <section class="contact-section">
    <h1>Get in touch</h1>
    <p>Have questions about traveling in Pangasinan? We're here to help.</p>
  </section>

  <section class="contact-cards">
    <!-- Ask About Tourist Spots -->
    <div class="contact-card">
      <div style="font-size: 30px;">📍</div>
      <h3>Ask About Tourist Spots</h3>
      <p>Looking for must-visit destinations or need custom travel advice in Pangasinan?</p>
      <a href="tel:+639123456789">+63 912 345 6789</a><br>
      <a href="/tripko/contact#tourism">Inquire About Destinations</a>
    </div>

    <!-- Need Help Using TripKo? -->
    <div class="contact-card">
      <div style="font-size: 30px;">🛠️</div>
      <h3>Need Help Using TripKo?</h3>
      <p>Experiencing problems with our system or need assistance using TripKo?</p>
      <a href="/tripko/support" class="support-button">Get Help Now</a>
    </div>
  </section>
  </section>
  
</body>
</html>
